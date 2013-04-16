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
		$this->assertEquals('admin/news/2/edit', $generator->relative('edit', 2, 'Admin\NewsController'));
		$this->assertEquals('admin/news/2/destroy', $generator->relative('destroy', 2));
		$this->assertEquals('admin/news/2/photos', $generator->relative('index', 2, 'photos'));
		$this->assertEquals('admin/news/2/photos/create', $generator->relative('create', 2, 'photos'));
		$this->assertEquals('admin/news/2/photos', $generator->relative('index', 2, 'Admin\PhotosController'));
		$this->assertEquals('admin/news/2/photo-parties/create', $generator->relative('create', 2, 'Admin\PhotoPartiesController'));
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
		$this->assertEquals('admin/news/2/photos/4/users', $generator->relative('index', 4, 'Admin\UsersController'));
		$this->assertEquals('admin/news/2/photos/4/user-dudes/create', $generator->relative('create', 4, 'Admin\UserDudesController'));
	}
	
	public function testController() {
		$generator = $this->path('admin/admins');
		$this->assertEquals('admin/admins', $generator->controller('Bkwld\Decoy\Controllers\Admins'));
		$this->assertEquals('admin/admins', $generator->controller('Bkwld\Decoy\Controllers\Admins@index'));
		$this->assertEquals('admin/admins/create', $generator->controller('Bkwld\Decoy\Controllers\Admins@create'));
		$this->assertEquals('admin/admins/2/edit', $generator->controller('Bkwld\Decoy\Controllers\Admins@edit', 2));
		$this->assertEquals('admin/articles', $generator->controller('Admin\ArticlesController'));
		$this->assertEquals('admin/articles/create', $generator->controller('Admin\ArticlesController@create'));
		$this->assertEquals('admin/articles-and-more/2/edit', $generator->controller('Admin\ArticlesAndMoreController@edit', 2));
	}
	
}