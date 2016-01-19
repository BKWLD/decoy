<?php namespace Bkwld\Decoy\Input;

// Deps
use Bkwld\Decoy\Exceptions\ValidationFail;
use Bkwld\Decoy\Input\ModelValidator;
use Input;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Check the input during a store or update for nested models in the input and
 * process thos models
 */
class RelatedModels {

	/**
	 * Check the input for related models.
	 *
	 * @param Eloquent\Model $model
	 */
	public function relateTo($model) {

		// Loop through the input, looking for relationships
		foreach(Input::all() as $name => $data) {
			if (!$relation = $this->makeRelation($model, $name, $data)) continue;

			// Loop through the data and create or update model records. A create is
			// detected because the id begins with an underscore (aka, doesn't reflect)
			// a true record in the database.
			foreach($data as $id => $input) {
				if (starts_with($id, '_')) $this->storeChild($relation, $input);
				else $this->updateChild($relation, $id, $input);
			}
		}
	}

	/**
	 * Check if the input is a relation and, if it is, return the relationship
	 * object
	 *
	 * @param Eloquent\Model $model
	 * @param string $name
	 * @param mixed $data
	 * @return false|Relation
	 */
	protected function makeRelation($model, $name, $data) {

		// The input name will begin with an underscore
		if (!starts_with($name, '_')) return false;

		// The data must be an array and must contain arrays
		if (!is_array($data)
			|| empty($data)
			|| count(array_filter($data, function($child) {
				return !is_array($child);
			}))) return false;

		// The input name, after remove the leading underscore, should be a
		// relationship defined on the model.
		$relationship = studly_case(substr($name, 1));
		if (!method_exists($model, $relationship)) return false;

		// Check if the returned object IS aactually a relation
		$relation = $model->$relationship();
		if (!is_a($relation, Relation::class)) return false;

		// Return the relationship object
		return $relation;
	}

	/**
	 * Create a new child record
	 *
	 * @param Relation $relation
	 * @param array $input
	 * @return void
	 */
	protected function storeChild($relation, $input) {
		$child = $relation->getRelated()->newInstance();
		$child->fill($input);
		(new ModelValidator)->validate($child);
		$relation->save($child);
	}

	/**
	 * Update an existing child record
	 *
	 * @param Relation $relation
	 * @param integer $id
	 * @param array $input
	 * @return void
	 */
	protected function updateChild($relation, $id, $input) {
		$child = $relation->getRelated()->findOrFail($id);
		$child->fill($input);
		(new ModelValidator)->validate($child);
		$child->save();
	}

}
