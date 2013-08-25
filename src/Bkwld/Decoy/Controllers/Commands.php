<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Models\Command;
use Response;
use Illuminate\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

// Run tasks from the admin
class Commands extends Base {
	
	// Props
	const MAX_EXECUTION_TIME = 600; // How long to allow a command to run for
	
	/**
	 * List all the tasks in the admin
	 */
	public function index() {
		$this->layout->nest('content', 'decoy::commands.index', array(
			'commands' => Command::all()
		));
	}
	
	/**
	 * Run one of the commands, designed to be called via AJAX
	 */
	public function execute($command_name) {
		
		// Find it
		if (!($command = Command::find($command_name))) App::abort(404);
		
		// Bootstrap the console app and load the command through it.  Code taken from
		// https://github.com/JN-Jones/web-artisan/blob/master/src/Jones/WebArtisan/Controllers/Cmd.php
		$app = app();
		$app->loadDeferredProviders();
		$artisan = ConsoleApplication::start($app);
		$command = $artisan->find($command_name);
		
		// Do the minimum required for arguments; we only support non-argumented commands
		$arguments = array();
		$arguments['command'] = $command_name;
		$arguments = new ArrayInput($arguments);
		
		// Run it, ignoring all output
		set_time_limit(self::MAX_EXECUTION_TIME);
		ob_start();
		$command->run($arguments, new NullOutput);
		ob_end_clean();
		
		// Return response
		return Response::json('ok');
	}
	
}