<?php

use \Mockery as m;

class TestConfig extends PHPUnit_Framework_TestCase {
	
	// Mock parts of Laravel that are referenced by helpers in config
	public function setUp() {
		$app = m::mock('app');
		$app->shouldReceive('make')->with('url')->andReturnUsing(function() {
			return m::mock('url')->shouldReceive('action')->getMock();
		});
		$app->shouldReceive('make')->with('path.public')->andReturn('/public');
		$app->shouldReceive('make')->with('request')->andReturnUsing(function() {
			return m::mock('request')->shouldReceive('root')->andReturn('http://test.dev')->getMock();
		});
		Illuminate\Support\Facades\Facade::setFacadeApplication($app);
	}
	
	public function testAll() {
		$config = require(__DIR__.'/../../src/config/config.php');
		$this->assertEquals($config['site_name'], 'Decoy');
		$this->assertEquals($config['upload_dir'], '/public/uploads');
		$this->assertEquals($config['mail_from_address'], 'postmaster@test.dev');
	}
	
} 