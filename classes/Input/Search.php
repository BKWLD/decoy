<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Bkwld\Decoy\Exceptions\Exception;
use Config;
use DB;
use Input;
use Log;
use Bkwld\Library\Utils\String;

/**
 * This class contains logic related to searching from controller
 * index views.  It could have gone in the controller class, but
 * trying to reduce it's bulk
 */
class Search {

	/**
	 * Utility method to generate a query string that applies the condition
	 * provided in the args
	 *
	 * @param  array $terms An associative array where the keys are "fields" and the
	 *                      values are "inputs"
	 * @return string 
	 */
	public static function query($terms) {
		return 'query='.urlencode(json_encode(array_map(function($input, $field) {
			return [$field, '=', $input];
		}, $terms, array_keys($terms))));
	}

	/**
	 * Apply the effect of a search (which is communicated view Input::get('query'))
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  array $config Search config from the controller class definition
	 * @return Illuminate\Database\Query\Builder
	 */
	public function apply($query, $config) {

		// Do nothing if no query in the input
		if (!Input::has('query')) return $query;

		// Expand the config
		$config = $this->longhand($config);
		
		// Deserialize the query and loop through
		$conditions = json_decode(Input::get('query'));
		if (!is_array($conditions)) throw new Exception('Bad query');
		foreach($conditions as $condition) {
			
			// Get the field name by taking the index and looking up which key it corresponds to
			$field = $condition[0];
			$field_config = $config[$field];

			// Extract vars for query
			$comparison = $condition[1];
			$input = $condition[2];

			// Use an app-defined query ...
			if (isset($config[$field]['query'])) {
				call_user_func($config[$field]['query'], $query, $comparison, $input);

			// ... or one of the simple, standard ones
			} else $this->condition($query, $field, $comparison, $input, $config[$field]['type']);
			
		}
		
		// Return the agumented query
		return $query;
		
	}

	/**
	 * Add a condition to a query
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  string $field The field name from search config
	 * @param  string $comparison The operator string from the search UI
	 * @param  string $input The input for the field
	 * @param  string $type The type of the field
	 * @return Illuminate\Database\Query\Builder
	 */
	private function condition($query, $field, $comparison, $input, $type) {

		// Convert date formats
		if ($type == 'date') {
			$field = DB::raw("DATE($field)");
			$input = date('Y-m-d', strtotime($input));
		}

		// Apply the where
		switch ($comparison) {

			// NULL safe equals and not equals
			// http://stackoverflow.com/a/19778341/59160
			case '=': return $query->whereRaw(sprintf('%s <=> %s',
				is_string($field) ? "`{$field}`" : $field, 
				empty($input) ? 'NULL' : DB::connection()->getPdo()->quote($input)));
			case '!=': return $query->whereRaw(sprintf('NOT(%s <=> %s)',
				is_string($field) ? "`{$field}`" : $field, 
				empty($input) ? 'NULL' : DB::connection()->getPdo()->quote($input)));
			
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
	
	/**
	 * Make the shorthand options of the search config explicit
	 * 
	 * @param  array $config Search config from the controller class definition
	 * @return array
	 */
	public function longhand($config) {
		$search = array();
		foreach($config as $key => $val) {
			
			// Make locale menu
			if ($val == 'locale') {
				$search['locale'] = [
					'type' => 'select',
					'label' => 'Locale',
					'options' => Config::get('decoy.site.locales'),
				];

			// Not associative assume it's a text field
			} else if (is_numeric($key)) {
				$search[$val] = array('type' => 'text', 'label' => String::titleFromKey($val));
			
			// If value isn't an array, make a default label
			} else if (!is_array($val)) {
				$search[$key] = array('type' => $val, 'label' => String::titleFromKey($key));
			
			// Add the meta array
			} else {

				// Make a default label
				if (empty($val['label'])) $val['label'] = String::titleFromKey($key);

				// Support class static method or variable as options for a select
				if (!empty($val['type']) 
					&& $val['type'] == 'select' 
					&& !empty($val['options']) 
					&& is_string($val['options'])) {
					$val['options'] = $this->longhandOptions($val['options']);
				}

				// Apply the meta data
				$search[$key] = $val;
			}
			
		}
		return $search;
	}

	/**
	 * Parse select options, returning a transformed array with static arrays
	 * or callbacks executed
	 * 
	 * @param  array $options 
	 * @return array
	 */
	private function longhandOptions($options) {

		// Call static method.  You don't pass the paranethesis
		// to static calls
		if (preg_match('#::.+\(\)#', $options)) {
			return call_user_func(substr($options, 0, strlen($options) - 2));

		// Return static variable
		} else if (preg_match('#::\$#', $options)) {
			list($class, $var) = explode('::$', $options);
			return $class::$$var;
		
		// Unknown format
		} else throw new Exception('Could not parse option: '.$options);

	}

	
}