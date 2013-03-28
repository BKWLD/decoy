<?php namespace Decoy;

// Imports
use \Laravel\Error;
use \Laravel\Bundle;
use \Laravel\Cache;
use \Laravel\Event;
use \Laravel\File;
use \Laravel\Str;
use \Exception;

/**
 * Adds some shared functionality to taks as well as informs the Decoy
 * admin interface.
 * 
 * Like the seed abstract class, you'll need to start the Decoy bundle so it
 * knows where to find this class:
 *   Bundle::start('decoy');
 * 
 */
abstract class Task {

	// Descriptive properties
	protected $TITLE;       // i.e. Feeds
	protected $DESCRIPTION; // i.e. Pulls feeds from external services
	
	// Constructor susses out default properties
	public function __construct() {
		
		// Get the name only, without the suffix (_Task).
		preg_match('#^(.+)_Task$#', get_class($this), $matches);
		$name = $matches[1];
				
		// Make a default title based on the controller name
		if (empty($this->TITLE)) $this->TITLE = str_replace('_', ' ', $name);
		
		// Base the heartbeat cache key off the class name
		if (empty($this->HEARTBEAT_CACHE_KEY)) $this->HEARTBEAT_CACHE_KEY = 'worker-heartbeat-'.$name;
		
	}
	
	//---------------------------------------------------------------------------
	// Getter/setter
	//---------------------------------------------------------------------------
	
	// Get access to protected properties
	public function title() { return $this->TITLE; }
	public function description() { return $this->DESCRIPTION; }
	public function heartbeat_cache_key() { return $this->HEARTBEAT_CACHE_KEY; }
	public function heartbeat_fail_mins() { return $this->HEARTBEAT_FAIL_MINS; }
	
	//---------------------------------------------------------------------------
	// Workers
	// - It's assumed there is only one worker defined per task class
	// - Child classes must define a work() method and probably a worker_init()
	// - For the worker, on Pagoda, the worker instance would have:
	//   exec: "php artisan <TASK>:worker --env=$LARAVEL_ENV"
	// - For the heatbeat, on Pagoda, the Boxfile would have for the app:
	//   cron:
	//      - "* * * * *": "php artisan <TASK>:heartbeat --env=$LARAVEL_ENV"
	//---------------------------------------------------------------------------
	
	// Worker settings
	protected $WORKER_SLEEP_SECS = 60;   // How many seconds to wait before each worker exec
	protected $HEARTBEAT_FAIL_MINS = 60; // The age in after which the worker is deemed failed
	protected $HEARTBEAT_CACHE_KEY;      // The key that the heartbeat is stored as
	
	// A no-op where code that is run pre-worker loop gets executed
	protected function worker_init() {}
	
	// A no-op where the application defines the logic that is run by the worker
	protected function work() {}
	
	// The worker loop.  This method never ends
	public function worker() {
		
		// Bootstrap
		$this->add_worker_logging();
		$this->worker_init();
		
		// Run this stuff as long as the worker is running
		while(true) {
			$this->work();
			Cache::forever($this->HEARTBEAT_CACHE_KEY, time());
			sleep($this->WORKER_SLEEP_SECS);
		}
	}
	
	// A task that runs the worker once, for testing purposes
	public function work_once() {
		$this->add_worker_logging();
		$this->worker_init();
		$this->work();
	}
	
	// This heartbeat function is called by cron to verify that the worker is still running
	public function heartbeat() {
		
		// The worker has died
		if (time() - Cache::get($this->HEARTBEAT_CACHE_KEY) > $this->HEARTBEAT_FAIL_MINS * 60) {
			
			// Log an exception
			if (Bundle::exists('laravel-plus-codebase')) Bundle::start('laravel-plus-codebase');
			Error::log(new Exception('The '.$this->TITLE.' worker has died'));
			
			// Do work
			$this->work_once();
		}
	}
	
	// Log messages to special worker log file
	private function add_worker_logging() {
		
		// Base the log file name after the current class
		preg_match('#^(.+)_Task$#', get_class($this), $matches);
		$name = strtolower($matches[1]);
		$filename = $name.'_worker.log';
		
		// Listen for log events and write the custom worker log
		Event::listen('laravel.log', function($type, $message) use ($filename) {
	    $message = date('Y-m-d H:i:s').' '.Str::upper($type)." - {$message}".PHP_EOL;
			File::append(path('storage').'logs/'.$filename, $message);
		});
		
	}
	
}