<?php namespace Bkwld\Decoy\Controllers;

/**
 * Check the status of workers from the admin
 */
class Workers extends Base {
	
	// Display all the workers
	public function get_index() {
		
		// Render the view
		$this->layout->nest('content', 'decoy::workers.index', array(
			'workers' => \Decoy\Worker::all(),
		));
	}
	
	// Ajax service that tails the log file for the selected worker
	public function get_tail($worker) {
		
		// Form the path to the file
		$file = \Decoy\Worker::log_path($worker);
		$size = 1024*100; // in bytes to get

		// Read from the end of the file
		clearstatcache();
		$fp = fopen($file, 'r');
		fseek($fp, -$size , SEEK_END);
		$contents = explode("\n", fread($fp, $size));
		fclose($fp);
		
		// Reverse the contents and return
		$contents = array_reverse($contents);
		if (empty($contents[0])) array_shift($contents);
		die(implode("\n", $contents));
		
	}
	
}