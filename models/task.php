<?php namespace Decoy;

/**
 * Adds some shared functionality to taks as well as informs the Decoy
 * admin interface.  Also functions as a sort of model.
 * 
 * Like the Seed abstract class, you'll need to start the Decoy bundle so it
 * knows where to find this class:
 *   Bundle::start('decoy');
 * 
 */
class Task {
	
	// Descriptive properties
	protected $TITLE;       // i.e. Feeds
	protected $DESCRIPTION; // i.e. Pulls feeds from external services
	
	// Constructor susses out default properties
	public function __construct() {
				
		// Make a default title based on the controller name
		if (empty($this->TITLE)) $this->TITLE = str_replace('_', ' ', $this->name());
		
		// Base the heartbeat cache key off the class name
		if (empty($this->HEARTBEAT_CACHE_KEY)) $this->HEARTBEAT_CACHE_KEY = 'worker-heartbeat-'.$this->name();
		
	}
	
	//---------------------------------------------------------------------------
	// Getter/setter
	//---------------------------------------------------------------------------
	
	// Get access to protected properties
	public function title() { return $this->TITLE; }
	public function description() { return $this->DESCRIPTION; }
	
	//---------------------------------------------------------------------------
	// Queries
	//---------------------------------------------------------------------------
	
	// Get all the tasks
	public static function all() {
		
		// Response array
		$tasks = array();
		
		// Loop through PHP files
		$task_files = scandir(path('app').'tasks');
		foreach($task_files as $task_file) {
			if (!preg_match('#\w+\.php#', $task_file)) continue;
			
			// Get properties of the task
			$file = path('app').'tasks/'.$task_file;
			$name = basename($task_file, '.php');
			$class = self::class_name($name);
			
			// Return this task
			require_once($file);
			$task = new $class();
			if (!is_a($task, '\Decoy\Task')) continue; // We only want to deal with classes that extend from this
			$tasks[] = $task;
		}
		
		// Return matching tasks
		return $tasks;
		
	}
	
	// Get a specific task
	// @param $task i.e. "Seed", "Feeds"
	public static function find($task) {
		
	}
	
	// Make a class name from a task name
	private static function class_name($name) {
		return str_replace(' ', '_', ucwords(str_replace('_', ' ', $name))).'_Task';
	}
	
	//---------------------------------------------------------------------------
	// Properties
	//---------------------------------------------------------------------------
	
	// Get the name of the class
	public function name() {
		preg_match('#^(.+)_Task$#', get_class($this), $matches);
		return $matches[1];
	}
	
	// Get all the methods of the class
	public function methods() {
		
		// Get all the methods of only this class
		$methods = get_class_methods($this);
		$parent_methods = get_class_methods(get_parent_class($this));
		$methods = array_diff($methods, $parent_methods);
		
		// Filter some method names
		// __construct : This is typically only used for bootstrapping
		// worker : This is typicaly used by laravel workers (infinitely long running tasks)
		$methods_to_ignore = array('__construct', 'worker');
		foreach($methods_to_ignore as $method_name) {
			if (($key = array_search($method_name, $methods)) !== false) {
				unset($methods[$key]);
			}
		}
		
		// Return filtered methods
		return $methods;
	}
	
}