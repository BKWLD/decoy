<?php

use Bkwld\Decoy\Routing\UrlGenerator;

class TestRoutingUrlGenerator extends PHPUnit_Framework_TestCase {
	
	private function path($path) {
		return new UrlGenerator($path);
	}
	
	public function testParentIndex() {
		$this->doParentRoutes($this->path('admin/news'));
		
	}
	
	public function testParentCreate() {
		$this->doParentRoutes($this->path('admin/news/create'));
		
	}
	
	public function testParentEdit() {
		$this->doParentRoutes($this->path('admin/news/2/edit'));
	}
	
	private function doParentRoutes($generator) {
		$this->assertEquals('admin/news', $generator->relative());
		$this->assertEquals('admin/news', $generator->relative('index'));
		$this->assertEquals('admin/news/create', $generator->relative('create'));
		$this->assertEquals('admin/news/2/edit', $generator->relative('edit', 2));
		$this->assertEquals('admin/news/2/destroy', $generator->relative('destroy', 2));
		$this->assertEquals('admin/news/2/photos', $generator->relative('index', 2, 'photos'));
		$this->assertEquals('admin/news/2/photos/create', $generator->relative('create', 2, 'photos'));
	}
	
	public function testChildIndex() {
		$this->doChildRoutes($this->path('admin/news/2/photos'));
		
	}
	
	public function testChildCreate() {
		$this->doChildRoutes($this->path('admin/news/2/photos/create'));
	}
	
	public function testChildEdit() {
		$this->doChildRoutes($this->path('admin/news/2/photos/4/edit'));
	}
	
	private function doChildRoutes($generator) {
		$this->assertEquals('admin/news/2/photos', $generator->relative());
		$this->assertEquals('admin/news/2/photos', $generator->relative('index'));
		$this->assertEquals('admin/news/2/photos/create', $generator->relative('create'));
		$this->assertEquals('admin/news/2/photos/4/edit', $generator->relative('edit', 4));
		$this->assertEquals('admin/news/2/photos/4/destroy', $generator->relative('destroy', 4));
		$this->assertEquals('admin/news/2/photos/4/users', $generator->relative('index', 4, 'users'));
		$this->assertEquals('admin/news/2/photos/4/users/create', $generator->relative('create', 4, 'users'));
	}
	
}