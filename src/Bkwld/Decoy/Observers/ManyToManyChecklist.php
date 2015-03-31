<?php namespace Bkwld\Decoy\Observers;

// Dependencies
use Input;

/**
 * Take input from a Many to Many Checklist and commit it to the db,
 * updating the relationshups
 */
class ManyToManyChecklist {

	/**
	 * @var string The form element prefix
	 */
	const PREFIX = '_many_to_many_';

	/**
	 * Take input from a Many to Many Checklist and commit it to the db
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 */
	public function handle($model) {

		// Check for matching input elements
		foreach(Input::get() as $key => $val) {
			if (preg_match('#^'.self::PREFIX.'(.+)#', $key, $matches)) {
				$this->updateRelationship($model, $matches[1]);
			}
		}

	}

	/**
	 * Process a particular input instance
	 * 
	 * @param Bkwld\Decoy\Models\Base $model A model instance
	 * @param string $relationship The relationship name
	 */
	private function updateRelationship($model, $relationship) {

		// Strip all the "0"s from the input.  These exist because push checkboxes is globally
		// set for all of Decoy;
		$ids = Input::get(self::PREFIX.$relationship);
		$ids = array_filter($ids, function($id) { return $id > 0; });

		// Attach just the ones mentioned in the input.  This blows away the previous joins
		$model->$relationship()->sync($ids);

	}

}