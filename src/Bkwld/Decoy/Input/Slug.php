<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Input;
use Str;

class Slug {
	
	/**
	 * Given a model, try and make a slug for the input and add it to
	 * the input (so it's catchable for validation)
	 * @param string $model The name of a model
	 * @param int $id The id of the model instance
	 */
	public function merge($model, $id = null) {

		// If we're on an edit view, update the unique condition on the rule
		// (if it exists) to be unique but not for the current row
		if ($id && in_array('slug', array_keys($model::$rules)) 
			&& strpos($model::$rules['slug'], 'unique') !== false) {
		
			// Add the row exception to the unique clause.  The regexp works because
			// the \w+ will end at the | that begins the next condition

			// If we're using the unique_with custom validator from the BKWLD bundle
			if (strpos($model::$rules['slug'], 'unique_with')) {
				$model::$rules['slug'] = preg_replace('#(unique_with:\w+,\w+)(,slug)?#i', 
					'$1,slug,'.$id, 
					$model::$rules['slug']);
				
			// Regular slugs
			} else {
				$model::$rules['slug'] = preg_replace('#(unique:\w+)(,slug)?#', 
					'$1,slug,'.$id, 
					$model::$rules['slug']);
			}
		}

		// If a slug is already defined, do nothing		
		if (Input::has('slug')) return;
		
		// Model must have rules and they must have a slug
		if (empty($model::$rules) || !in_array('slug', array_keys($model::$rules))) return;
		
		// If a Model::$TITLE_COLUMN is set, use that input for the slug
		if (!empty($model::$TITLE_COLUMN) && Input::has($model::$TITLE_COLUMN)) {
			Input::merge(array('slug' => Str::slug(strip_tags(Input::get($model::$TITLE_COLUMN)))));
		
		// Else it looks like the model has a slug, so try and set it
		} else if (Input::has('name')) {
			Input::merge(array('slug' => Str::slug(strip_tags(Input::get('name')))));
		} elseif (Input::has('title')) {
			Input::merge(array('slug' => Str::slug(strip_tags(Input::get('title')))));
		}
		
	}
	
}