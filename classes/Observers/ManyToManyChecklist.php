<?php

namespace Bkwld\Decoy\Observers;

use Event;
use Request;

/**
 * Take input from a Many to Many Checklist and commit it to the db,
 * updating the relationships
 */
class ManyToManyChecklist
{
    /**
     * @var string The form element prefix
     */
    const PREFIX = '_many_to_many_';

    /**
     * Take input from a Many to Many Checklist and commit it to the db. Called
     * on model saved.
     *
     * @param  string $event
     * @param  array $payload Contains:
     *    - Bkwld\Decoy\Models\Base $model
     * @return void
     */
    public function handle($event, $payload)
    {
        list($model) = $payload;
        
        // Check for matching input elements
        foreach (Request::input() as $key => $val) {
            if (preg_match('#^'.self::PREFIX.'(.+)#', $key, $matches)) {
                $this->updateRelationship($model, $matches[1]);
            }
        }
    }

    /**
     * Process a particular input instance
     *
     * @param Bkwld\Decoy\Models\Base $model        A model instance
     * @param string                  $relationship The relationship name
     */
    private function updateRelationship($model, $relationship)
    {
        // Make sure the relationship exists on the model.  This also prevents the
        // wrong model (who might also have an `saved` callback) from trying to have
        // this data saved on it
        if (!method_exists($model, $relationship)) {
            return;
        }

        // Strip all the "0"s from the input.  These exist because push checkboxes
        // is globally set for all of Decoy;
        $ids = request(self::PREFIX.$relationship);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        // Allow a single listener to transform the list of ids to, for instance,
        // add pivot data.
        $prefix = 'decoy::many-to-many-checklist.';
        if ($mutated = Event::until($prefix."syncing: $relationship", [$ids])) {
            $ids = $mutated;
        }

        // Attach just the ones mentioned in the input.  This blows away the
        // previous joins
        $model->$relationship()->sync($ids);

        // Fire completion event
        Event::fire($prefix."synced: $relationship");
    }
}
