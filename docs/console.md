# Console

## Generator

The Decoy workflow begins with generating a migration for a database table using the [standard Laravel approach](http://laravel.com/docs/migrations).  Then, Decoy provides a generator that creates the controller, model, and view for that table.  Run `php artisan decoy:generate Model` where "Model" is the name of the Model class you intend to create.  This should be the singular form of the table you created.


## Workers

If you make a Laravel command extend from `Bkwld\Decoy\Models\Worker`, the command is embued with some extra functionality.  The following options get added:

- `--worker` - Run command as a worker.  As in not letting the process die.
- `--cron` - Run command as cron.  As in only a single fire per execution.
- `--heartbeat` - Check that the worker is running.  This is designed to be run from cron.

In a standard PagodaBox config, you would put these in your Boxile:

	web1:
		name: app
		cron:
			- "* * * * *": "php artisan <COMMAND> --heartbeat"

	worker1:
		name: worker
		exec: "php artisan <COMMAND> --worker"

In this example, "<COMMAND>" is your command name, like "import:feeds".  With a setup like the above (and the default worker static config options), your command will run every minute on PB.  And if the worker fails, the heartbeat will continue running it, at a rate of every 15 min (because of PB rate limiting).

In addition, by subclassing `Bkwld\Decoy\Models\Worker`, the worker command will show up in a listing in the admin at /admin/workers.  From this interface you can make sure the worker is still running and view logs.
