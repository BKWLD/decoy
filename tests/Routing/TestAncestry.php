<?php

use Bkwld\Decoy\Controllers\Base;
use Bkwld\Decoy\Routing\Ancestry;
use Bkwld\Decoy\Routing\Wildcard;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use \Mockery as m;

class TestRoutingAncestry extends PHPUnit_Framework_TestCase {
	
	// Build an ancestry instance
	private function build($path, $verb = 'GET', $explicit = false) {
		
		// Build config dependency
		$config = m::mock('Config');
		$config->shouldReceive('get')->with('decoy::dir')->andReturn('admin');
		$config->shouldReceive('get')->with('decoy::layout')->andReturn('layouts.default');
		
		// Build controller
		$controller = new Base;
		$controller->injectDependencies(array(
			'config' => $config,
			'ancestry' => m::mock('Ancestry'),
		));
		$controller->simulate($path);
		
		// Build router dependency using the real router
		$route = new Route($path);
		$route->setMethods(array($verb));
		if ($explicit) $route->setOption('_uses', 'Fake\Controller@explicit');
		$router = new Router();
		if ($explicit) $router->match($verb, $path, 'Fake\Controller@explicit');
		$router->setCurrentRoute($route);
		
		// Build wildcard dependency
		$wildcard = new Wildcard('admin', $verb, $path);
		
		// Return ancestery instance
		return new Ancestry($controller, $router, $wildcard);
	}
	
	public function testGetAction() {
		$this->assertEquals('index', $this->build('admin/news')->getAction());
		$this->assertEquals('create', $this->build('admin/news/create')->getAction());
		$this->assertEquals('store', $this->build('admin/news', 'POST')->getAction());
		$this->assertEquals('explicit', $this->build('admin/admins', 'GET', true)->getAction());
	}
	/*
	
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
	*/
	
}