<?php

namespace Bkwld\Decoy\Fields\Traits;

/**
 * Misc uitlities that multiple fields may use
 */
trait Helpers
{
    /**
     * Allow passing in of a model when inferring doesn't work
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    private $model;

    /**
     * Set the model
     *
     * @param  Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function model($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the model
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        // Use explicilty set model
        if ($this->model) {
            return $this->model;
        }

        // Get the model instance that was passed to Former by the Base Controller
        $model = app('former.populator')->all();

        // Make sure it's a model instance
        if (is_a($model, 'Illuminate\Database\Eloquent\Model')) {
            return $model;
        }
    }

    /**
     * If there is a span class on the field, return it
     *
     * @return string
     */
    public function span()
    {
        if (!isset($this->attributes['class'])) {
            return;
        }
        preg_match('#span\d#', $this->attributes['class'], $matches);

        return $matches[0];
    }
}
