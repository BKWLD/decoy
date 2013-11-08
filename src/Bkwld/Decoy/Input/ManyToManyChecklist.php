<?php namespace Bkwld\Decoy\Input;

// Dependencies
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
	 * Render the form element
	 * ex: <?= Decoy::manyToManyChecklist($__data, 'events', array('blockHelp' => 'Blah blah')) ?>
	 * @param array $__data A The data passed to the view, to get at the item
	 * @param string $relationship The name of the relationship function on the model
	 * @param array $options Former key-value pairs, where the key is the function name 
	 * @return string HTML
	 */
	public function render($__data, $relationship, $options = array()) {

		// Get all the related items to THE model instance passed to the view
		if (isset($__data['item'])) $joined = $__data['item']->$relationship;

		// This element will get a special name that will get handled specially by the
		// base controller. I think Laravel won't try to save fields that begin with
		// an "_".
		$name = self::PREFIX.$relationship;

		// Get the full list of items
		$query = call_user_func(Str::singular($relationship).'::ordered');

		// Create the data that Former expects
		$boxes = array();
		foreach($query->get() as $row) {

			// Create the row
			$ar = array('value' => $row->getKey(), 'name' => $name.'[]');

			// Check if it should be checked
			if (isset($joined) && $joined->contains($row->getKey())) $ar['checked'] = true;

			// Add it
			$boxes[$row->title()] = $ar;
		}
		
		// Create the form element, applying any extra configuration options
		$el = Former::checkbox($relationship)->checkboxes($boxes);
		foreach($options as $func => $args) $el = call_user_func_array(array($el, $func), (array) $args);
		return $el;

	}

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


