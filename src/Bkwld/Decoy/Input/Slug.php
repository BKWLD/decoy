<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Str;

class Slug {
	
	/**
	 * Given a model, try and make a slug for the input and add it to
	 * the input (so it's catchable for validation)
	 * @param Bkwld\Decoy\Model\Base $item An instance of a Decoy model
	 */
	public function merge($item) {

		// Determine the model by getting the class of the item
		$model = get_class($item);

		// If we're on an edit view, update the unique condition on the rule
		// (if it exists) to be unique but not for the current row
		$id = $item->getKey();
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
		if (!empty($item->slug)) return;
		
		// Model must have rules and they must have a slug
		if (empty($model::$rules) || !in_array('slug', array_keys($model::$rules))) return;
		
		// Loop through source column names and use the first one that has a value
		// as the source of the slug
		$sources = array('name', 'title');
		if (!empty($model::$title_column)) array_unshift($sources, $model::$title_column);
		foreach($sources as $column) {
			if (!empty($item->$column)) {
				$item->slug = Str::slug(strip_tags($item->$column));
				break;
			}
		}
		
	}
	
}