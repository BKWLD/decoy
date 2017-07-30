<?php

namespace Bkwld\Decoy\Input;

use Decoy;
use Bkwld\Decoy\Models\Image;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Check the input during a store or update for nested models in the input and
 * process those models.  Nested model data is named in the input like:
 *
 *   For create:
 *   <input name="images[_rand][title]">
 *
 *   For update"
 *   <input name="images[33][title]">
 */
class NestedModels
{
    /**
     * Check the input for related models data.  Remove it from the input if it
     * exists.  And then listen for the model to be saved and write the related
     * models.
     *
     * @param  Eloquent\Model $model
     * @return array          The input with attributes that were relations removed
     */
    public function relateTo($model)
    {
        // Vars
        $relation_attributes = [];
        $input = Decoy::filteredInput();

        // Loop through the input, looking for relationships
        foreach ($input as $name => $data) {
            if (!$relation = $this->makeRelation($model, $name, $data)) {
                continue;
            }
            $relation_attributes[] = $name;

            // Write child data when the model is saved.  Because of how the saved
            // listener works, we need to explicitly make sure the saved model is
            // the one whose data we're parsing.
            $model::saved(function ($saved_model) use ($model, $relation, $name, $data) {
                if ($model != $saved_model) {
                    return;
                }
                $this->writeOnSaved($relation, $name, $data);
            });
        }

        // Returning all input without the related attribtues
        return array_except($input, $relation_attributes);
    }

    /**
     * Check if the input is a relation and, if it is, return the relationship
     * object
     *
     * @param  Model          $model
     * @param  string         $name  The input name, like from <input name="$name">, which
     *                               is also the naem of the relationship function.
     * @param  mixed          $data
     * @return false|Relation
     */
    protected function makeRelation($model, $name, $data)
    {
        // The data must be an array and must contain arrays
        if (!is_array($data)
            || empty($data)
            || count(array_filter($data, function ($child) {
                return !is_array($child);
            }))) {
            return false;
        }

        // The input name should be a function defined on the model.
        if (!method_exists($model, $name)) {
            return false;
        }

        // Check if the running the function actually returns a relationship
        $relation = $model->$name();
        if (!is_a($relation, Relation::class)) {
            return false;
        }

        // Return the relationship object
        return $relation;
    }

    /**
     * After the model is saved, write the child model (either a create or update)
     *
     * @param  Relation $relation
     * @param  string   $name     The name of the relationship
     * @param  array    $data     All nested model instances data
     * @return void
     */
    protected function writeOnSaved($relation, $name, $data)
    {
        // Loop through the data and create or update model records. A create is
        // detected because the id begins with an underscore (aka, doesn't reflect)
        // a true record in the database.
        foreach ($data as $id => $input) {
            $prefix = $name.'.'.$id.'.';
            if (starts_with($id, '_')) {
                $this->storeChild($relation, $input, $prefix);
            } else {
                $this->updateChild($relation, $id, $input, $prefix);
            }
        }
    }

    /**
     * Create a new child record
     *
     * @param  Relation $relation
     * @param  array    $input    The data for the nested model
     * @param  string   $prefix   The input name prefix, for validation
     * @return void
     */
    protected function storeChild($relation, $input, $prefix)
    {
        $child = $relation->getRelated()->newInstance();
        $child->fill($input);
        $rules = $this->getRules($relation, $input);
        (new ModelValidator)->validateAndPrefixErrors($prefix, $child, $rules);
        $relation->save($child);
    }

    /**
     * Update an existing child record
     *
     * @param  Relation $relation
     * @param  integer  $id
     * @param  array    $input    The data for the nested model
     * @param  string   $prefix   The input name prefix, for validation
     * @return void
     */
    protected function updateChild($relation, $id, $input, $prefix)
    {
        $child = $relation->getRelated()->findOrFail($id);
        $child->fill($input);
        $rules = $this->getRules($relation, $input);
        (new ModelValidator)->validateAndPrefixErrors($prefix, $child, $rules);
        $child->save();
    }

    /**
     * Get the validation rules.  They are generally on the child except for
     * in the special case of Images
     *
     * @param  Relation $relation
     * @param  array    $input    The data for the nested model
     * @return array
     */
    public function getRules($relation, $input)
    {
        $child = $relation->getRelated();

        // If nested is not an Image, don't do anything special.  This will result
        // in the default validation behavior which gets the rules off of the child.
        if (!is_a($child, Image::class)) {
            return;
        }

        // Check for image rules on the parent
        $parent = $relation->getParent();
        $rules_key = 'images.' . ($input['name'] ?: 'default');
        if (!array_key_exists($rules_key, $parent::$rules)) {
            return;
        }

        // Return the parent rules concatenated on the default rules for an Image
        // (those are essentially hardcoded here but I don't expect them to change)
        return [
            'file' => 'image|' . $parent::$rules[$rules_key],
        ];
    }
}
