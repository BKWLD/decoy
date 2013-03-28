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
	
	// Get the name of the class
	protected function name() {
		preg_match('#^(.+)_Task$#', get_class($this), $matches);
		return $matches[1];
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
			
			// Check if the tasks should be ignored
			if (property_exists($class, 'IGNORE')) continue;
			
			// Return this task
			$tasks[] = (object) array(
				'name' => $name,
				'file' => $file,
				'class' => $class,
			);
		}
		
		// Return matching tasks
		return $tasks;
		
	}
	
	// Make the class name from the task name
	private static function class_name($name) {
		return str_replace(' ', '_', ucwords(str_replace('_', ' ', $name))).'_Task';
	}
	
}