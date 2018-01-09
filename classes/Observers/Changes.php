<?php

namespace Bkwld\Decoy\Observers;

// Deps
use Bkwld\Decoy\Models;
use Bkwld\Decoy\Models\Change;
use Event;
use Route;

/**
 * Create a log of all model changing events
 */
class Changes
{
    /**
     * Only log the following events
     *
     * @param array
     */
    protected $supported = ['created', 'updated', 'deleted'];

    /**
     * Handle all Eloquent model events
     *
     * @param  string $event
     * @param  array $payload Contains:
     *    - Bkwld\Decoy\Models\Base $model
     */
    public function handle($event, $payload)
    {
        list($model) = $payload;

        // Don't log changes to pivot models.  Even though a user may have initiated
        // this, it's kind of meaningless to them.  These events can happen when a
        // user messes with drag and drop positioning.
        if (is_a($model, \Illuminate\Database\Eloquent\Relations\Pivot::class)) {
            return;
        }

        // Get the action of the event
        preg_match('#eloquent\.(\w+)#', $event, $matches);
        $action = $matches[1];
        if (!in_array($action, $this->supported)) {
            return;
        }

        // Get the admin acting on the record
        $admin = app('decoy.user');

        // If `log_changes` was configed as a callable, see if this model event
        // should not be logged
        if ($check = config('decoy.site.log_changes')) {
            if (is_bool($check) && !$check) {
                return;
            }
            if (is_callable($check)) {
                \Log::error('Callable log_changes have been deprecated');
                if (!call_user_func($check, $model, $action, $admin)) {
                    return;
                }
            }
        } else {
            return;
        }

        // Check with the model itself to see if it should be logged
        if (method_exists($model, 'shouldLogChange')) {
            if (!$model->shouldLogChange($action)) {
                return;
            }

        // Default to not logging changes if there is no shouldLogChange()
        } else {
            return;
        }

        // Log the event
        Change::log($model, $action, $admin);
    }
}
