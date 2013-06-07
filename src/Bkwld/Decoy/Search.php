<?php namespace Bkwld\Decoy;

// Dependencies
use Input;
use Log;

/**
 * This class contains logic related to searching from controller
 * index views.  It could have gone in the controller class, but
 * trying to reduce it's bulk
 */
class Search {

	
	// Apply the effect of a search (which is communicated view Input::get('query'))
	static public function apply($query, $config) {
		
		// Do nothing if no query in the input
		if (!Input::has('query')) return $query;
		
		// Deserialize the query and loop through
		$conditions = json_decode(Input::get('query'));
		$field_keys = array_keys($config);
		if (!is_array($conditions)) throw new Exception('Bad query');
		foreach($conditions as $condition) {
			
			// Get the field name by taking the index and looking up which key it corresponds to
			$field_index = $condition[0];
			$field_key = $field_keys[$field_index];
			$field = is_string($field_key) ? $field_key : $config[$field_key];
			
			// Apply the condition to the query
			$comparison = $condition[1];
			$input = $condition[2];
			$query = self::condition($query, $field, $comparison, $input);
			
		}
		
		// Return the agumented query
		return $query;
		
	}
	
	// Make the shorthand options of the search config explicit
	static public function longhand($config) {
		$search = array();
		foreach($config as $key => $val) {
			
			// Not associative assume it's a text field
			if (is_numeric($key)) {
				$search[$val] = array('type' => 'text', 'label' => str_replace('_', ' ', ucwords($val)));
			
			// If value isn't an array, make a default label
			} else if (!is_array($val)) {
				$search[$key] = array('type' => $val, 'label' => str_replace('_', ' ', ucwords($key)));
			
			// Add the meta array
			} else {
				if (empty($val['label'])) $val['label'] = str_replace('_', ' ', ucwords($key));
				$search[$key] = $val;
			}
			
		}
		return $search;
	}
	
	// Add a condition to a query
	static private function condition($query, $field, $comparison, $input) {
		switch ($comparison) {
			
			// Not Like
			case '!%*%':
				$comparison = substr($comparison, 1);
				$input = str_replace('*', $input, $comparison);
				return $query->where($field, 'NOT LIKE', $input);
			
			// Like
			case '*%':
			case '%*':
			case '%*%':
				$input = str_replace('*', $input, $comparison);
				return $query->where($field, 'LIKE', $input);
			
			// Defaults
			default:
				return $query->where($field, $comparison, $input);
		}
	}
	
}