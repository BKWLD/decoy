<?php

use Bkwld\Decoy\Controllers\Base;
use Bkwld\Decoy\Routing\Ancestry;
use Bkwld\Decoy\Routing\Wildcard;
use Illuminate\Http\Request;
use \Mockery as m;

class TestRoutingAncestry extends PHPUnit_Framework_TestCase {
	
	private function buildController($path, $verb = 'GET', $options = array()) {
		
		// Build config dependency
		$config = m::mock('Config');
		$config->shouldReceive('get')->with('decoy::dir')->andReturn('admin');
		$config->shouldReceive('get')->with('decoy::layout')->andReturn('layouts.default');
		
		// Build mock ancestery for controller.  This seems weird and recursive
		// to be doing this from the class i'm testing...
		$ancestry = m::mock('Ancestry');
		if (empty($options['parent_controller'])) {
			$ancestry->shouldReceive('isChildRoute')->andReturn(false);
		} else {
			$ancestry->shouldReceive('isChildRoute')->andReturn(true);
			$ancestry->shouldReceive('deduceParentController')->andReturn($options['parent_controller']);
			$ancestry->shouldReceive('deduceParentRelationship');
			$ancestry->shouldReceive('deduceChildRelationship');
		}
		
		// Build controller
		$controller = new Base;
		$controller->injectDependencies(array(
			'config' => $config,
			'ancestry' => $ancestry,
			'route' => m::mock("Router")->shouldReceive('currentRouteAction')->andReturn(null)->getMock(),
			'url' => '', // Not used my any tests so far
		));
		$controller->simulate($path);

		return $controller;
		
	}	

	private function build($path, $verb = 'GET', $options = array()) {
		
		// Build controller
		$controller = $this->buildController($path, $verb, $options);
		
		// Build wildcard dependency
		if (empty($options['path'])) {
			$wildcard = new Wildcard('admin', $verb, $path);
		} else {
			$wildcard = new Wildcard('admin', $verb, $options['path']);
		}
	
		// Mock input
		$input = m::mock('Symfony\Component\HttpFoundation\Request');
		if (empty($options['parent_controller'])) {
			$input->shouldReceive('has')->with('parent_controller')->andReturn(false);
			$input->shouldReceive('get')->with('parent_controller')->andReturn(false);
		} else {
			$input->shouldReceive('has')->with('parent_controller')->andReturn(true);
			$input->shouldReceive('get')->with('parent_controller')->andReturn($options['parent_controller']);
		}
		
		// Return ancestery instance
		return new Ancestry($controller, $wildcard, $input);
	}

	public function testActionIsChild() {
		$this->assertFalse($this->build('admin/news')->requestIsChild());
		$this->assertFalse($this->build('admin/news/create')->requestIsChild());
		$this->assertFalse($this->build('admin/news/2/edit')->requestIsChild());
		$this->assertFalse($this->build('admin/news/autocomplete')->requestIsChild());
		$this->assertFalse($this->build('admin/news', 'POST')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/create')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/40/edit')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/autocomplete')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos', 'POST')->requestIsChild());
	}
	
	// Though this path ('admin/base') would never be used, this allows the test to pass because of the 
	// class_exists() requirement within isRouteController() (since decoy has a controller called 'base')
	
	public function testIsRouteController() {
		$this->assertFalse($this->build('admin/base', 'GET', array('path' => 'admin/news'))->isRouteController());
		$this->assertFalse($this->build('admin/base/2/edit', 'GET', array('path' => 'admin/news'))->isRouteController());
		$this->assertTrue($this->build('admin/base')->isRouteController());
		$this->assertTrue($this->build('admin/base/2/edit')->isRouteController());
	}

	public function testParentIsInInput() {
		$this->assertFalse($this->build('admin/base')->parentIsInInput());
		$this->assertFalse($this->build('admin/busters', 'GET', array('parent_controller' => 'Admin\ParentIsInInputTest'))->parentIsInInput());
		$this->assertTrue($this->build('admin/base', 'GET', array('parent_controller' => 'Admin\ParentIsInInputTest'))->parentIsInInput());
	}
	
	public function testIsActingAsRelated() {
		$this->assertFalse($this->build('admin/base')->isActingAsRelated());
		$this->assertFalse($this->build('admin/base/2/edit')->isActingAsRelated());
		$this->assertTrue($this->build('admin/base', 'GET', array('path' => 'admin/news/2/edit'))->isActingAsRelated());
		$this->assertFalse($this->build('admin/base', 'GET', array('path' => 'admin/news'))->isActingAsRelated());
		$this->assertTrue($this->build('admin/base', 'GET', array('path' => 'admin/news/2/edit'))->isActingAsRelated());
		$this->assertTrue($this->build('admin/base/autocomplete', 'GET', array('path' => 'admin/news/2/edit'))->isActingAsRelated());
	}
	
	public function testDeduceParentControllerRequest() {
		$this->assertEquals(false, $this->build('admin/base')->deduceParentController());
		$this->assertEquals('Bkwld\Decoy\Controllers\Base', $this->build('admin/base/2/slides/40/edit')->deduceParentController());
		$this->assertEquals('Bkwld\Decoy\Controllers\Base', $this->build('admin/base/2/slides')->deduceParentController());
		$this->assertEquals('Bkwld\Decoy\Controllers\Base', $this->build('admin/base/2/slides/create')->deduceParentController());
	}
	
	public function testDeduceParentControllerInput() {
		$this->assertEquals(false, $this->build('admin/busters', 'GET', array('parent_controller' => 'Admin\DeduceParentControllerInputTest'))->deduceParentController());
		$this->assertEquals('Admin\DeduceParentControllerInputTest', $this->build('admin/base', 'GET', array('parent_controller' => 'Admin\DeduceParentControllerInputTest'))->deduceParentController());
	}
	
	// As in a related list of base on an admin edit page
	public function testDeduceParentControllerRelated() {
		$this->assertEquals(false, $this->build('admin/news')->deduceParentController());
		$this->assertEquals(false, $this->build('admin/news', 'GET', array('path' => 'admin/base'))->deduceParentController());
		$this->assertEquals('Bkwld\Decoy\Controllers\Admins', $this->build('admin/base', 'GET', array('path' => 'admin/admins/2/edit'))->deduceParentController());
		$this->assertEquals('Bkwld\Decoy\Controllers\Admins', $this->build('admin/base/autocomplete', 'GET', array('path' => 'admin/admins/2/edit'))->deduceParentController());
	}
	
}