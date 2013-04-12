<?php

use Bkwld\Decoy\Controllers\Base;
use Bkwld\Decoy\Routing\Ancestry;
use \Mockery as m;


class TestRoutingAncestry extends PHPUnit_Framework_TestCase {
	
	// Build an ancestry instance
	private function build($path) {
		
		// Build config dependency
		$config = m::mock('Config');
		$config->shouldReceive('get')->with('decoy::dir')->andReturn('admin');
		$config->shouldReceive('get')->with('decoy::layout')->andReturn('layouts.default');
		
		// Build controller
		$controller = new Base;
		$controller->injectDependencies(array(
			'config' => $config,
		));
		$controller->simulate($path);
		return new Ancestry($controller);
	}
	
	public function testGetAction() {
		// $this->assertEquals($this->build('admin/news'));
	}
	
	public function testActionIsChild() {
		$this->assertFalse($this->build('admin/news')->actionIsChild());
		$this->assertFalse($this->build('admin/news/create')->actionIsChild());
		$this->assertFalse($this->build('admin/news/2/edit')->actionIsChild());
		$this->assertFalse($this->build('admin/news/autocomplete')->actionIsChild());
		$this->assertTrue($this->build('admin/news/2/photos')->actionIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/create')->actionIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/4/edit')->actionIsChild());
		$this->assertFalse($this->build('admin/news/2/photos/autocomplete')->actionIsChild());
	}
	
}