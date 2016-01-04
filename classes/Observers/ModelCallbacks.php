<?php namespace Bkwld\Decoy\Observers;

// Deps
use Event;
use Illuminate\Support\Str;

/**
 * Call no-op classes on models for all event types.  This just simplifies
 * the handling of model events for models.
 */
class ModelCallbacks {

	/**
	 * Handle all model events, both Eloquent and Decoy
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 */
	public function handle($model) {

		// Get the name of the event.  Examples:
		// - eloquent.saving: Person
		// - decoy::model.validating: Person
		$event = Event::firing();
		
		// Get the action from the event name
		preg_match('#\.(\w+)#', $event, $matches);
		$action = $matches[1];

		// If there is matching callback method on the model, call it, passing
		// any additional event arguments to it
		$method = 'on'.Str::studly($action);
		if (method_exists($model, $method)) {
			return call_user_func_array([$model, $method], array_slice(func_get_args(), 1));
		}
	}

}