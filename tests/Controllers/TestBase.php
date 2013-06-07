<?php

use Bkwld\Decoy\Controllers\Base;
use \Mockery as m;

/**
 * Using mock on the whole class since it's abstract
 */
class TestControllersBase extends PHPUnit_Framework_TestCase {
	
	public function testControllerName() {
		$base = m::mock('Bkwld\Decoy\Controllers\Base[]');
		$this->assertEquals('Admins', $base->controllerName('Bkwld\Decoy\Controllers\Admins'));
		$this->assertEquals('AdminsForLife', $base->controllerName('Bkwld\Decoy\Controllers\AdminsForLife'));
		$this->assertEquals('News', $base->controllerName('Admin\NewsController'));
		$this->assertEquals('NewsTimes', $base->controllerName('Admin\NewsTimesController'));
	}
	
	public function testTitle() {
		$base = m::mock('Bkwld\Decoy\Controllers\Base[]');
		$this->assertEquals('Admins', $base->title('Admins'));
		$this->assertEquals('Admins Who Love Stuff', $base->title('AdminsWhoLoveStuff'));
	}
	
	public function testDetailPath() {
		$base = m::mock('Bkwld\Decoy\Controllers\Base[]');
		$this->assertEquals('decoy::admins.edit', $base->detailPath('Bkwld\Decoy\Controllers\Admins'));
		$this->assertEquals('decoy::admins_stuff.edit', $base->detailPath('Bkwld\Decoy\Controllers\AdminsStuff'));
		$this->assertEquals('admin.news.edit', $base->detailPath('Admin\NewsController'));
		$this->assertEquals('admin.news_stuff.edit', $base->detailPath('Admin\NewsStuffController'));
		$this->assertEquals('admin.category.news_stuff.edit', $base->detailPath('Admin\Category\NewsStuffController'));
	}
	
	public function testModel() {
		$base = m::mock('Bkwld\Decoy\Controllers\Base[]');
		$this->assertEquals('Bkwld\Decoy\Models\Admin', $base->model('Bkwld\Decoy\Controllers\Admins'));
		$this->assertEquals('Bkwld\Decoy\Models\Category\Admin', $base->model('Bkwld\Decoy\Controllers\Category\Admins'));
		$this->assertEquals('News', $base->model('Admins\News'));
		$this->assertEquals('Article', $base->model('Admins\Articles'));
		$this->assertEquals('Pants\Article', $base->model('Admins\Pants\Articles'));
	}
	
}