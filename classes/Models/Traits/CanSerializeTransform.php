<?php

namespace Bkwld\Decoy\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Allows the registering of transforming callbacks that get applied when the
 * class is serialized with toArray() or toJson().
 */
trait CanSerializeTransform
{
    /**
     * Stores pending serialization transformations
     *
     * @var array
     */
    protected $serialize_transforms = [];

    /**
     * Add a serialization transformer.  The transformer should return the model
     * instance like a mapping function would.  Or, if it returns null, that item
     * will be removed from the collection.
     *
     * @param  callable $func
     * @return $this
     */
    public function serializeTransform(callable $func)
    {
        $this->serialize_transforms[] = $func;

        return $this;
    }

    /**
     * Override toArray() to fire transforms before serialization
     *
     * @return array
     */
    public function toArray()
    {
        $this->runSerializeTransforms();

        return parent::toArray();
    }

    /**
     * Override toArray() to fire transforms before serialization
     *
     * @param  int   $options
     * @return array
     */
    public function toJson($options = 0)
    {
        // Models' toJson() calls their toArray() function.  We don't want this to
        // happen twice
        if (!is_a($this, Model::class)) {
            $this->runSerializeTransforms();
        }

        // Continue execution
        return parent::toJson($options);
    }

    /**
     * Apply all registered transforms to the items in the collection. If a
     * transform returns falsey, that model will be removed from the collection
     *
     * @return void
     */
    public function runSerializeTransforms()
    {
        // If the class using this trait is a collection, replace the items in the
        // collection with the transformed set
        if (is_a($this, Collection::class)) {
            $this->items = $this->runSerializeTransformsOnCollection($this);

        // Otherwise, if the class is a model, act directly on it
        } elseif (is_a($this, Model::class)) {
            $this->runSerializeTransformsOnModel($this);
        }
    }

    /**
     * Run transforms on every model in a collection
     *
     * @return Collection
     */
    protected function runSerializeTransformsOnCollection(Collection $collection)
    {
        // Loop through the collection and transform each model
        return $collection->map(function ($item) {

            // If collection item isn't a model, don't do anything
            if (!is_a($item, Model::class)) return $item;

            // Serialize the model
            return $this->runSerializeTransformsOnModel($item);

        // Remove all the models whose transforms did not return a value. Then
        // convert back to an array.
        })->filter()->all();
    }

    /**
     * Run transforms on a single model
     *
     * @param  Model      $model
     * @return Model|void
     */
    protected function runSerializeTransformsOnModel(Model $model)
    {
        // Loop through all the transform functions
        foreach ($this->serialize_transforms as $transform) {

            // Call the transform and if nothing is retuned, return nothing. This will
            // be used by runSerializeTransformsOnCollection() to filter out models.
            if (!$model = call_user_func($transform, $model)) {
                return;
            }
        }

        // Return the transformed model
        return $model;
    }
}
