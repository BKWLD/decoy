<?php

namespace Bkwld\Decoy\Models;

use App;

/**
 * Adds some shared functionality to taks as well as informs the Decoy
 * admin interface.  Also functions as a sort of model.
 */
class Command
{
    /**
     * @var int
     */
    const MAX_EXECUTION_TIME = 600; // How long to allow a command to run for

    //---------------------------------------------------------------------------
    // Queries
    //---------------------------------------------------------------------------

    /**
     * Scan commands directory for custom commands
     *
     * @return array
     */
    public static function all()
    {
        // Add custom ones
        $commands = self::allCustom();

        // Add Laravel ones
        App::register('Illuminate\Foundation\Providers\ConsoleSupportServiceProvider'); // Needed for compile and optimize
        $commands['Laravel']['Migrate'] = App::make('command.migrate');
        $commands['Laravel']['Seed'] = App::make('command.seed');
        $commands['Laravel']['Cache clear'] = App::make('command.cache.clear');
        $commands['Laravel']['Clear compiled classes'] = App::make('command.clear-compiled');

        // Return matching commands
        return $commands;
    }

    /**
     * Scan commands directory for custom commands
     *
     * @return array
     */
    public static function allCustom()
    {
        // Response array
        $commands = [];

        // Loop through PHP files
        $dir = app_path('Console/Commands');
        if (!is_dir($dir)) return [];
        $files = scandir($dir);
        foreach ($files as $file) {
            if (!preg_match('#\w+\.php#', $file)) {
                continue;
            }

            // Build an instance of a command using the service container
            $path = $dir.'/'.$file;
            $class = 'App\Console\Commands\\'.basename($path, '.php');
            $command = app($class);

            // Validate command
            if (!is_a($command, 'Illuminate\Console\Command')) {
                continue;
            }

            // Get namespace
            $name = $command->getName();
            if (strpos($name, ':')) {
                list($namespace, $name) = explode(':', $name);
            } else {
                $namespace = 'misc';
            }

            // Massage name
            $name = str_replace('-', ' ', ucfirst($name));

            // Group commands by namespace
            $namespace = ucfirst($namespace);
            $name = ucfirst($name);
            if (!array_key_exists($namespace, $commands)) {
                $commands[$namespace] = [];
            }
            $commands[$namespace][$name] = $command;
        }

        // Return
        return $commands;
    }

    // Get a specific command
    // @param $command i.e. "Seed", "FeedCommand"
    public static function find($search_command)
    {
        // Get all the commands
        $commands = self::all();

        // Loop through them to find the passed one
        foreach ($commands as $subcommands) {
            foreach ($subcommands as $command) {
                if ($search_command == $command->getName()) {
                    return $command;
                }
            }
        }

        return false;
    }
}
