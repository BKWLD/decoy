<?php 

/**
 * Check the status of workers from the admin
 */
class Decoy_Workers_Controller extends Decoy_Base_Controller {
	
	// Display all the workers
	public function get_index() {
		
		// Render the view
		$this->layout->nest('content', 'decoy::workers.index', array(
			
			// Pass it new instances of worker classes
			'workers' => array_map(function($worker) {
				require_once($worker->file);
				return new $worker->class();
			}, \Decoy\Worker::all())
		));
	}
	
	// Check if any workers are registered
	static public function has_workers() {
		return true;
	}
	
}