<?php

use Bkwld\Decoy\Breadcrumbs;

class TestBreadcrumbs extends PHPUnit_Framework_TestCase {
	
	public function testLogin() {
		$crumbs = array();
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin'));
	}
	
	public function testIndex() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles'));
		$this->assertEquals(false, Breadcrumbs::back($crumbs));
		$this->assertEquals(false, Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Articles', Breadcrumbs::title($crumbs));
	}
	
	public function testCreate() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
			'/admin/articles/create' => 'Create',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles/create'));
		$this->assertEquals('/admin/articles', Breadcrumbs::back($crumbs));
		$this->assertEquals('/admin/articles', Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Articles - Create', Breadcrumbs::title($crumbs));
	}
	
	public function testEdit() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles/2/edit'));
		$this->assertEquals('/admin/articles', Breadcrumbs::back($crumbs));
		$this->assertEquals('/admin/articles', Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Articles - Edit', Breadcrumbs::title($crumbs));
	}
	
	public function testChildIndex() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles/2/slides'));
		$this->assertEquals('/admin/articles/2/edit', Breadcrumbs::back($crumbs));
		$this->assertEquals('/admin/articles/2/edit', Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Slides', Breadcrumbs::title($crumbs));
	}
	
	public function testChildCreate() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
			'/admin/articles/2/slides/create' => 'Create',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles/2/slides/create'));
		$this->assertEquals('/admin/articles/2/slides', Breadcrumbs::back($crumbs));
		$this->assertEquals('/admin/articles/2/edit', Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Slides - Create', Breadcrumbs::title($crumbs));
	}
	
	public function testChildEdit() {
		$crumbs = array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
			'/admin/articles/2/slides/40/edit' => 'Edit',
		);
		$this->assertEquals($crumbs, Breadcrumbs::defaults('admin/articles/2/slides/40/edit'));
		$this->assertEquals('/admin/articles/2/slides', Breadcrumbs::back($crumbs));
		$this->assertEquals('/admin/articles/2/edit', Breadcrumbs::smartBack($crumbs));
		$this->assertEquals('Slides - Edit', Breadcrumbs::title($crumbs));
	}
	
}