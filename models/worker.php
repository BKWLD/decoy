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
 * Workers are tasks that define logic designed to be run as a never
 * ending worker routine.  Tasks can and should extend this class.  A task
 * will need to start the decoy bundle first so the autoloader can find this file
 */
class Worker extends Task {
	
	// Worker settings
	protected $WORKER_SLEEP_SECS = 60;   // How many seconds to wait before each worker exec
	protected $HEARTBEAT_FAIL_MINS = 60; // The age in after which the worker is deemed failed
	protected $HEARTBEAT_WORKER_KEY;     // The key that the worker heartbeat is stored as
	protected $HEARTBEAT_CRON_KEY;       // The key that the cron heartbeat is stored as
	
	// Constructor susses out default properties
	public function __construct() {
		parent::__construct();
		
		// Base the cache keys off the class name
		if (empty($this->HEARTBEAT_WORKER_KEY)) $this->HEARTBEAT_WORKER_KEY = 'worker-heartbeat-'.$this->name();
		if (empty($this->HEARTBEAT_CRON_KEY)) $this->HEARTBEAT_CRON_KEY = 'cron-heartbeat-'.$this->name();
		
	}
	
	//---------------------------------------------------------------------------
	// Methods for tasks
	// - Child classes must define a work() method and probably a worker_init()
	// - For the worker, on Pagoda, the worker instance would have:
	//   exec: "php artisan <TASK>:worker --env=$LARAVEL_ENV"
	// - Or, if the host is more traditional, start your work with cron by adding this to
	//   your crontab:
	//   * * * * * php artisan <TASK>:cron --env=<LARAVEL_ENV>
	// - For the heatbeat, on Pagoda, the Boxfile would have for the worker instance:
	//   cron:
	//      - "* * * * *": "php artisan <TASK>:heartbeat --env=$LARAVEL_ENV"
	//---------------------------------------------------------------------------
	
	// The worker loop.  This method never ends.  This is the task method that would be called
	// to start a worker
	public function worker() {
		
		// Bootstrap
		$this->add_worker_logging();
		$this->worker_init();
		
		// Run this stuff as long as the worker is running
		while(true) {
			$this->work();
			Cache::forever($this->HEARTBEAT_WORKER_KEY, time());
			sleep($this->WORKER_SLEEP_SECS);
		}
	}
	
	// Similar to worker(), this runs the worker logic and updates the heartbeat but is designed
	// to be invoked by cron.  Thus, it only runs the work once.
	public function cron() {
		work_once();
		Cache::forever($this->HEARTBEAT_WORKER_KEY, time());
	}
	
	// A no-op where code that is run pre-worker loop gets executed
	protected function worker_init() {}
	
	// A no-op where the application defines the logic that is run by the worker
	protected function work() {}
	
	// A task that runs the worker once
	public function work_once() {
		$this->add_worker_logging();
		$this->worker_init();
		$this->work();
	}
	
	// This heartbeat function is called by cron to verify that the worker is still running
	public function heartbeat() {
		
		// Update the heartbeat
		$last = Cache::get($this->HEARTBEAT_CRON_KEY);
		if (empty($last->interval)) $interval = 'calculating';
		else $interval = time() - $last->time;
		Cache::forever($this->HEARTBEAT_CRON_KEY, (object) array(
			'time' => time(),
			'interval' => $interval,
		));
		
		// The worker has died
		if (!$this->is_running()) {
			
			// Log an exception.  Using an exception instead of a log so the laravel-plus-codebase
			// bundle can forward the error to exception.
			$this->add_worker_logging();
			if (Bundle::exists('laravel-plus-codebase')) Bundle::start('laravel-plus-codebase');
			Error::log(new Exception('The '.$this->TITLE.' worker has died'));
			
			// Do work
			$this->work_once();
		}
	}
	
	// Log messages to special worker log file
	private function add_worker_logging() {
		
		// Base the log file name after the current class
		$name = strtolower($this->name());
		$path = self::log_path($name);
		
		// Listen for log events and write the custom worker log
		Event::listen('laravel.log', function($type, $message) use ($path) {
	    $message = date('Y-m-d H:i:s').' '.Str::upper($type)." - {$message}".PHP_EOL;
			File::append($path, $message);
		});
	}
	
	// Make the path to the log file
	static public function log_path($worker) {
		return path('storage').'logs/'.$worker.'_worker.log';
	}
	
	//---------------------------------------------------------------------------
	// Queries
	//---------------------------------------------------------------------------
	
	// Get all the tasks that have workers
	public static function all() {
		return array_filter(parent::all(), function($task) {
			require_once($task->file);
			return is_a(new $task->class(), '\Decoy\Worker');
		});
		
	}
	
	// Check if we're currently failing or not
	public function is_running() {
		return time() - Cache::get($this->HEARTBEAT_WORKER_KEY) < $this->HEARTBEAT_FAIL_MINS * 60;
	}
	
	// Last time the heartbeat was checked
	public function last_heartbeat_check() { 
		$check = Cache::get($this->HEARTBEAT_CRON_KEY);
		if (empty($check)) return 'never';
		else return date(\BKWLD\Utils\Constants::COMMON_DATETIME.' T', $check->time);
	}
	
	// The last time the worker ran
	public function last_heartbeat() {
		$check = Cache::get($this->HEARTBEAT_WORKER_KEY);
		if (empty($check)) return 'never';
		else return date(\BKWLD\Utils\Constants::COMMON_DATETIME.' T', $check);
	}
	
	// The current interval that heartbeats are running at
	public function current_interval($format = null) {
		
		// Relative time formatting
		$abbreviated = array(
			'pluraling' => false,
			'spacing' => false,
			'labels' => array(
				'now',
				's',
				'm',
				'h',
				'd',
				'm',
				'y'
			),
		);
		
		// Figure stuff out
		if ($this->is_running()) $interval = $this->WORKER_SLEEP_SECS;
		else {
			$check = Cache::get($this->HEARTBEAT_CRON_KEY);
			if (empty($check)) $interval = 'uncertain';
			else $interval = $check->interval;
		}
		
		// Format it
		if (!is_numeric($interval)) return $interval;
		switch($format) {
			case 'raw': return $interval;
			case 'abbreviated': return \BKWLD\Utils\String::time_elapsed(time() - $interval, $abbreviated);
			default: return \BKWLD\Utils\String::time_elapsed(time() - $interval);
		}
	}
	
	
}