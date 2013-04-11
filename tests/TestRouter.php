<?php


class TestRouter extends PHPUnit_Framework_TestCase {
	
	
	public function testIndex() {
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'index');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/2/user-dudes');
		$this->assertEquals($router->detectController(), 'Admin\UserDudesController');
		$this->assertEquals($router->detectAction(), 'index');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/2/users/4/roles');
		$this->assertEquals($router->detectController(), 'Admin\RolesController');
		$this->assertEquals($router->detectAction(), 'index');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testCreate() {
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/create');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'create');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/2/users/create');
		$this->assertEquals($router->detectController(), 'Admin\UsersController');
		$this->assertEquals($router->detectAction(), 'create');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testStore() {
		
		$router = new Bkwld\Decoy\Router('admin', 'POST','admin/articles');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'store');
		$this->assertEquals($router->detectId(), false);
		
		$router = new Bkwld\Decoy\Router('admin', 'POST','admin/articles/2/users');
		$this->assertEquals($router->detectController(), 'Admin\UsersController');
		$this->assertEquals($router->detectAction(), 'store');
		$this->assertEquals($router->detectId(), false);
	}
	
	public function testEdit() {
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/2/edit');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'edit');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Bkwld\Decoy\Router('admin', 'GET','admin/articles/2/users/4/edit');
		$this->assertEquals($router->detectController(), 'Admin\UsersController');
		$this->assertEquals($router->detectAction(), 'edit');
		$this->assertEquals($router->detectId(), 4);
	}
	
	public function testUpdate() {
		
		$router = new Bkwld\Decoy\Router('admin', 'PUT','admin/articles/2');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Bkwld\Decoy\Router('admin', 'PUT','admin/articles/2/users/4');
		$this->assertEquals($router->detectController(), 'Admin\UsersController');
		$this->assertEquals($router->detectAction(), 'update');
		$this->assertEquals($router->detectId(), 4);
	}
	
	public function testDelete() {
		
		$router = new Bkwld\Decoy\Router('admin', 'DELETE','admin/articles/2');
		$this->assertEquals($router->detectController(), 'Admin\ArticlesController');
		$this->assertEquals($router->detectAction(), 'destroy');
		$this->assertEquals($router->detectId(), 2);
		
		$router = new Bkwld\Decoy\Router('admin', 'DELETE','admin/articles/2/users/4');
		$this->assertEquals($router->detectController(), 'Admin\UsersController');
		$this->assertEquals($router->detectAction(), 'destroy');
		$this->assertEquals($router->detectId(), 4);
	}
	
}