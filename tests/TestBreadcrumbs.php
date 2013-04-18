<?php

use Bkwld\Decoy\Breadcrumbs;

class TestBreadcrumbs extends PHPUnit_Framework_TestCase {

	public function testDefaults() {
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
		), Breadcrumbs::defaults('admin/articles'));
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
			'/admin/articles/create' => 'Create',
		), Breadcrumbs::defaults('admin/articles/create'));
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
		), Breadcrumbs::defaults('admin/articles/2/edit'));
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
		), Breadcrumbs::defaults('admin/articles/2/slides'));
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
			'/admin/articles/2/slides/create' => 'Create',
		), Breadcrumbs::defaults('admin/articles/2/slides/create'));
		
		$this->assertEquals(array(
			'/admin/articles' => 'Articles',
			'/admin/articles/2/edit' => 'Edit',
			'/admin/articles/2/slides' => 'Slides',
			'/admin/articles/2/slides/40/edit' => 'Edit',
		), Breadcrumbs::defaults('admin/articles/2/slides/40/edit'));
		
	}
	
}