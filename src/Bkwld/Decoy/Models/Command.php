<?php namespace Bkwld\Decoy\Models;

// Dependencies
use App;

/**
 * Adds some shared functionality to taks as well as informs the Decoy
 * admin interface.  Also functions as a sort of model.
 */
class Command {
	
	// Descriptive properties
	const MAX_EXECUTION_TIME = 600; // How long to allow a command to run for
	
	//---------------------------------------------------------------------------
	// Queries
	//---------------------------------------------------------------------------
	
	// Get all the commands
	public static function all() {
		
		// Add custom ones
		$commands = self::allCustom();
		
		// Add Laravel ones
		$commands['Laravel']['Migrate'] = App::make('command.migrate');
		$commands['Laravel']['Seed'] = App::make('command.seed');
		$commands['Laravel']['Cache clear'] = App::make('command.cache.clear');
		$commands['Laravel']['Clear compiled classes'] = App::make('command.clear-compiled');
		$commands['Laravel']['Optimize classes'] = App::make('command.optimize');
		
		// Return matching commands
		return $commands;
		
	}
	
	// Scan commands directory for custom commands
	public static function allCustom() {
		
		// Response array
		$commands = array();
		
		// Loop through PHP files
		$dir = app_path().'/commands';
		$files = scandir($dir);
		foreach($files as $file) {
			if (!preg_match('#\w+\.php#', $file)) continue;
			
			// Get properties of the command
			$path = $dir.'/'.$file;
			$class = basename($path, '.php');
			
			// Validate command
			require_once($path);
			$command = new $class;
			if (!is_a($command, 'Illuminate\Console\Command')) continue;
			
			// Get namespace
			$name = $command->getName();
			if (strpos($name, ':')) list($namespace, $name) = explode(':', $name);
			else $namespace = 'misc';
			
			// Group commands by namespace
			if (!array_key_exists('namespace', $commands)) $commands[ucfirst($namespace)] = array();
			$commands[ucfirst($namespace)][ucfirst($name)] = $command;
		}
		
		// Return
		return $commands;
		
	}
	
	// Get a specific command
	// @param $command i.e. "Seed", "FeedCommand"
	public static function find($search_command) {
		
		// Get all the commands
		$commands = self::all();
		
		// Loop through them to find the passed one
		foreach($commands as $subcommands) {
			foreach($subcommands as $command) {
				if ($search_command == $command->getName()) return $command;
			}
		}
		return false;
	}
	
}