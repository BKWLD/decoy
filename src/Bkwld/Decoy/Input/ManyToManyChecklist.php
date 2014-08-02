<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Bkwld\Library\Utils\String;
use Decoy;
use Former;
use Input;
use Str;

/**
 * Render many to many checklists and process their submittal
 */
class ManyToManyChecklist {

	// The form element prefix
	const PREFIX = '_many_to_many_';

	/**
	 * Take input from a Many to Many Checklist and commit it to the db
	 * @param Bkwld\Decoy\Models\Base $model A model instance
	 */
	public function update($item) {

		// Check for matching input elements
		foreach(Input::get() as $key => $val) {
			if (preg_match('#^'.self::PREFIX.'(.+)#', $key, $matches)) {
				$this->updateRelationship($item, $matches[1]);
			}
		}

	}

	/**
	 * Process a particular input instance
	 * @param Bkwld\Decoy\Models\Base $model A model instance
	 * @param string $relationship The relationship name
	 */
	private function updateRelationship($item, $relationship) {

		// Strip all the "0"s from the input.  These exist because push checkboxes is globally
		// set for all of Decoy;
		$ids = Input::get(self::PREFIX.$relationship);
		$ids = array_filter($ids, function($id) { return $id > 0; });

		// Attach just the ones mentioned in the input.  This blows away the previous joins
		$item->$relationship()->sync($ids);

	}
}


