<?php namespace Bkwld\Decoy\Controllers;

/**
 * Check the status of workers from the admin
 */
class Workers extends Base {
	
	public $description = "Monitor whether workers are running or not. The logic of a failed worker is still executed regularly, just at a slower interval.";

	// Display all the workers
	public function index() {
		$this->populateView('decoy::workers.index', [
			'workers' => Model::all(),
		]);
	}
	
	// Ajax service that tails the log file for the selected worker
	public function tail($worker) {

		// Form the path to the file
		$file = Model::logPath(urldecode($worker));
		if (!file_exists($file)) throw new Exception('Log file not found');
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