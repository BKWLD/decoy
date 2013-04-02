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
	const MAX_EXECUTION_TIME = 600; // How long to allow a task to run for
	
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
	// @param $task i.e. "Seed", "Feed_Stuff"
	public static function find($task) {
		
		// Load the file
		$file = path('app').'tasks/'.strtolower($task).'.php';
		if (!file_exists($file)) throw new Exception('This task could not be found');
		require_once($file);
		
		// Instantiate
		$class = self::class_name($task);
		$task = new $class();
		if (!is_a($task, '\Decoy\Task')) throw new Exception('Task is not a \Decoy\Task.');
		return $task;
	}
	
	// Make a class name from a task name
	private static function class_name($name) {
		return str_replace(' ', '_', ucwords(str_replace('_', ' ', $name))).'_Task';
	}
	
	//---------------------------------------------------------------------------
	// Properties
	//---------------------------------------------------------------------------
	
	// Get the name of the class.
	// @returns Case sensetive name: Feed_Stuff, Seed, etc
	public function name() {
		preg_match('#^(.+)_Task$#', get_class($this), $matches);
		return $matches[1];
	}
	
	// Get all the methods of only this class and not parents
	public function methods() {
		$methods = get_class_methods($this);
		$parent_methods = get_class_methods(get_parent_class($this));
		return array_diff($methods, $parent_methods);
	}
	
	// Run a method of a task
	// @param $method - The name of a method to run, like "auto_approve"
	public function run($method) {
		
		// Make sure the method is one of the defined ones
		if (array_search($method, $this->methods()) === false) throw new Exception('Task method does not exist');
		
		// Allow a really long execution time
		set_time_limit(self::MAX_EXECUTION_TIME);
		
		// Run the task and supress output
		ob_start();
		\Laravel\CLI\Command::run(array($this->name().':'.$method));
		ob_end_clean();
		
		// Currently we're not outputing the result
		return null;
		
	}
	
}