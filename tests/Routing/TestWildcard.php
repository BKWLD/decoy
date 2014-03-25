<?php

use Bkwld\Decoy\Routing\Wildcard;

class TestRoutingWildcard extends PHPUnit_Framework_TestCase {
	
	
	public function testIndex() {
		
		$router = new Wildcard('admin', 'GET','admin/articles');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('index', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/user-dudes');
		$this->assertEquals('UserDudes', $router->detectControllerClass());
		$this->assertEquals('indexChild', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertTrue($router->detectIfChild());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/40/roles');
		$this->assertEquals('Roles', $router->detectControllerClass());
		$this->assertEquals('indexChild', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertTrue($router->detectIfChild());
	}
	
	public function testCreate() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/create');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('create', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/create');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('create', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertTrue($router->detectIfChild());

	}
	
	public function testStore() {
		
		$router = new Wildcard('admin', 'POST','admin/articles');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('store', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'POST','admin/articles/create');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('store', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/users');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('store', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertTrue($router->detectIfChild());
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/users/create');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('store', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertTrue($router->detectIfChild());
	}
	
	public function testEdit() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/edit');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('edit', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/40/edit');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('edit', $router->detectAction());
		$this->assertEquals(40, $router->detectId());
		$this->assertTrue($router->detectIfChild());
	}
	
	public function testUpdate() {
		
		$router = new Wildcard('admin', 'PUT','admin/articles/2');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('update', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'POST','admin/articles/2');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('update', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'PUT','admin/articles/2/users/40');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('update', $router->detectAction());
		$this->assertEquals(40, $router->detectId());
		$this->assertTrue($router->detectIfChild());
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/users/40');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('update', $router->detectAction());
		$this->assertEquals(40, $router->detectId());
		$this->assertTrue($router->detectIfChild());
	}
	
	public function testDestroy() {
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('destroy', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/destroy');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('destroy', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/users/40');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('destroy', $router->detectAction());
		$this->assertEquals(40, $router->detectId());
		$this->assertTrue($router->detectIfChild());
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/users/40/destroy');
		$this->assertEquals('Users', $router->detectControllerClass());
		$this->assertEquals('destroy', $router->detectAction());
		$this->assertEquals(40, $router->detectId());
		$this->assertTrue($router->detectIfChild());
	}
	
	public function testAttach() {
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/attach');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('attach', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());

	}
	
	public function testRemove() {
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/remove');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('remove', $router->detectAction());
		$this->assertEquals(2, $router->detectId());
		$this->assertFalse($router->detectIfChild());

	}
	
	public function testAutocomplete() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/autocomplete');
		$this->assertEquals('Articles', $router->detectControllerClass());
		$this->assertEquals('autocomplete', $router->detectAction());
		$this->assertEquals(false, $router->detectId());
		$this->assertFalse($router->detectIfChild());

	}
	
	public function testDetectParentId() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/slides/40/edit');
		$this->assertEquals(2, $router->detectParentId());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/slides/create');
		$this->assertEquals(2, $router->detectParentId());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/slides');
		$this->assertEquals(2, $router->detectParentId());
		
		$router = new Wildcard('admin', 'GET','admin/articles/create');
		$this->assertEquals(false, $router->detectParentId());
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/edit');
		$this->assertEquals(false, $router->detectParentId());
		
	}
	
}