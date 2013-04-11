<?php

use Bkwld\Decoy\Routing\Wildcard;

class TestRoutingWildcard extends PHPUnit_Framework_TestCase {
	
	
	public function testIndex() {
		
		$router = new Wildcard('admin', 'GET','admin/articles');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'index');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/user-dudes');
		$this->assertEquals($router->detectControllerClass(), 'UserDudes');
		$this->assertEquals($router->detectAction(), 'indexChild');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/4/roles');
		$this->assertEquals($router->detectControllerClass(), 'Roles');
		$this->assertEquals($router->detectAction(), 'indexChild');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testCreate() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/create');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'create');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/create');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'create');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testStore() {
		
		$router = new Wildcard('admin', 'POST','admin/articles');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'store');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/users');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'store');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testEdit() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/edit');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'edit');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Wildcard('admin', 'GET','admin/articles/2/users/4/edit');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'edit');
		$this->assertEquals($router->detectId(), 4);
	}
	
	public function testUpdate() {
		
		$router = new Wildcard('admin', 'PUT','admin/articles/2');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Wildcard('admin', 'POST','admin/articles/2');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Wildcard('admin', 'PUT','admin/articles/2/users/4');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 4);
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/users/4');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 4);
	}
	
	public function testDestroy() {
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'destroy');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/users/4');
		$this->assertEquals($router->detectControllerClass(), 'Users');
		$this->assertEquals($router->detectAction(), 'destroy');
		$this->assertEquals($router->detectId(), 4);
	}
	
	public function testAttach() {
		
		$router = new Wildcard('admin', 'POST','admin/articles/2/attach');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'attach');
		$this->assertEquals($router->detectId(), 2);

	}
	
	public function testRemove() {
		
		$router = new Wildcard('admin', 'DELETE','admin/articles/2/remove');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'remove');
		$this->assertEquals($router->detectId(), 2);

	}
	
	public function testAutocomplete() {
		
		$router = new Wildcard('admin', 'GET','admin/articles/autocomplete');
		$this->assertEquals($router->detectControllerClass(), 'Articles');
		$this->assertEquals($router->detectAction(), 'autocomplete');
		$this->assertEquals($router->detectId(), false);

	}
	
}