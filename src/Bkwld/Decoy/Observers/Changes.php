<?php namespace Bkwld\Decoy\Observers;

// Deps
use Bkwld\Decoy\Models\Change;
use Config;
use Event;

/**
 * Create a log of all model changing events
 */
class Changes {

	/**
	 * Only log the following events
	 *
	 * @param array
	 */
	protected $supported = ['created', 'updated', 'deleted'];

	/**
	 * Handle all Eloquent model events
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 */
	public function handle($model) {

		// Don't log the Change model events
		if (is_a($model, 'Bkwld\Decoy\Models\Change')) return;

		// Get the action of the event
		preg_match('#eloquent\.(\w+)#', Event::firing(), $matches);
		$action = $matches[1];
		if (!in_array($action, $this->supported)) return;

		// Get the admin acting on the record
		$admin = app('decoy.auth')->user();

		// If `log_changes` was configed as a callable, see if this model event
		// should not be logged
		if (($check = Config::get('decoy::site.site.log_changes'))
			&& !is_bool($check) // Avoid more compicated check for callability
			&& is_callable($check)
			&& !call_user_func($check, $model, $action, $admin)) return;

		// Log the event
		Change::log($model, $action, $admin);

	}

}