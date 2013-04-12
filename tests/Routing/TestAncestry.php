<?php

use Bkwld\Decoy\Controllers\Base;
use Bkwld\Decoy\Routing\Ancestry;
use Bkwld\Decoy\Routing\Wildcard;
use Illuminate\Http\Request;
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
		
		// Build wildcard dependency
		$wildcard = new Wildcard('admin', $verb, $path);
		
		// Return ancestery instance
		return new Ancestry($controller, $wildcard);
	}

	public function testActionIsChild() {
		$this->assertFalse($this->build('admin/news')->requestIsChild());
		$this->assertFalse($this->build('admin/news/create')->requestIsChild());
		$this->assertFalse($this->build('admin/news/2/edit')->requestIsChild());
		$this->assertFalse($this->build('admin/news/autocomplete')->requestIsChild());
		$this->assertFalse($this->build('admin/news', 'POST')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/create')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/4/edit')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos/autocomplete')->requestIsChild());
		$this->assertTrue($this->build('admin/news/2/photos', 'POST')->requestIsChild());
	}
	
}