<?php

namespace Bkwld\Decoy\Observers;

use Event;
use Illuminate\Support\Str;

/**
 * Call no-op classes on models for all event types.  This just simplifies
 * the handling of model events for models.
 */
class ModelCallbacks
{
    /**
     * Handle all model events, both Eloquent and Decoy
     *
     * @param  string $event
     * @param  array $payload Contains:
     *    - Bkwld\Decoy\Models\Base $model
     * @return void
     */
    public function handle($event, $payload)
    {
        list($model) = $payload;

        // Get the action from the event name
        preg_match('#\.(\w+)#', $event, $matches);
        $action = $matches[1];

        // If there is matching callback method on the model, call it, passing
        // any additional event arguments to it
        $method = 'on'.Str::studly($action);
        if (method_exists($model, $method)) {
            return call_user_func_array([$model, $method], array_slice($payload, 1));
        }
    }
}
