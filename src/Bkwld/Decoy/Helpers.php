<?php namespace Bkwld\Decoy;

// Dependencies
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Models\Fragment;
use Bkwld\Library;
use Config;
use Croppa;
use Former;
use Request;
use Session;
use Str;
use View;

/**
 * These function like the Laravel `Html` view helpers.  This class is bound
 * to the App IoC container as "decoy".  Thus, Decoy::helperName() can be
 * used to invoke them from views.
 */
class Helpers {
	
	/**
	 * Generate title tags based on section content
	 */
	public function title() {
		
		// If no title has been set, try to figure it out based on
		// default breadcrumbs
		$title = View::yieldContent('title');
		if (empty($title)) $title = Breadcrumbs::title(Breadcrumbs::defaults());
		
		// Get the site name
		$site = Config::get('decoy::site.name');

		// Set the title
		return '<title>' . ($title ? "$title | $site" : $site) . '</title>';
	}

	/**
	 * Add the controller and action as CSS classes on the body tag
	 */
	public function bodyClass() {
		$path = Request::path();
		$classes = array();

		// Special condition for the elements
		if (strpos($path, '/elements/field/') !== false) return 'elements field';

		// Special condition for the reset page, which passes the token in as part of the route
		if (strpos($path, '/reset/') !== false) return 'login reset';

		// Tab-sidebar views support deep links that would normally affect the
		// class of the page.
		if (strpos($path, '/fragments/') !== false) return 'fragments index';
		if (strpos($path, '/elements/') !== false) return 'elements index';

		// Get the controller and action from the URL
		preg_match('#/([a-z-]+)(?:/\d+)?(?:/(create|edit))?$#i', $path, $matches);
		$controller = empty($matches[1]) ? 'login' : $matches[1];
		$action = empty($matches[2]) ? 'index' : $matches[2];
		array_push($classes, $controller, $action);

		// Add the admin roles
		$roles = app('decoy.auth')->role();
		if ($roles && (is_array($roles) || class_implements($roles, 'Illuminate\Support\Contracts\ArrayableInterface'))) {
			foreach($roles as $role) {
				array_push($classes, 'role-'.$role);
			}
		}

		// Return the list of classes
		return implode(' ', $classes);
	}

	/**
	 * Formats the data in the standard list shared partial.
	 * - $item - A row of data from a Model query
	 * - $column - The field name that we're currently displaying
	 * - $conver_dates - A string that matches one of the date_formats
	 *
	 * I tried very hard to get this code to be an aonoymous function that was passed
	 * to the view by the view composer that handles the standard list, but PHP
	 * wouldn't let me.
	 */
	public function renderListColumn($item, $column, $convert_dates) {
		
		// Date formats
		$date_formats = array(
			'date'     => FORMAT_DATE,
			'datetime' => FORMAT_DATETIME,
			'time'     => FORMAT_TIME,
		);
		
		// Convert the item to an array so I can test for values
		$attributes = $item->getAttributes();

		// Get values needed for static array test
		$class = get_class($item);

		// If the column is named, locale, convert it to its label
		if ($column == 'locale') {
			$locales = Config::get('decoy::site.locales');
			if (isset($locales[$item->locale])) return $locales[$item->locale];

		// If the object has a method defined with the column value, use it
		} elseif (method_exists($item, $column)) {
			return call_user_func(array($item, $column));
		
		// Else if the column is a property, echo it
		} elseif (array_key_exists($column, $attributes)) {

			// Format date if appropriate
			if ($convert_dates && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $item->$column)) {
				return date($date_formats[$convert_dates], strtotime($item->$column));
			
			// If the column name has a plural form as a static array or method on the model, use the key
			// against that array and pull the value.  This is designed to handle my convention
			// of setting the source for pulldowns, radios, and checkboxes as static arrays
			// on the model.
			} else if (($plural = Str::plural($column))
				&& (isset($class::$$plural) && is_array($class::$$plural) && ($ar = $class::$$plural) 
					|| (method_exists($class, $plural) && ($ar = forward_static_call(array($class, $plural))))
				)) {

				// Support comma delimited lists by splitting on commas before checking
				// if the key exists in the array
				return join(', ', array_map(function($key) use ($ar, $class, $plural) {
					if (array_key_exists($key, $ar)) return $ar[$key];
					else return $key; 
				}, explode(',', $item->$column)));

			// Just display the column value
			} else {
				return $item->$column;
			}
		}

		// Else, just display it as a string
		return $column;
		
	}
	
	/**
	 * Get the value of a Fragment given it's key then trim any whitespace from it.  The
	 * trim is so that checks can be more easily made for `empty()`.  And it's done in this
	 * helper rather than in the model so that the internal logic that handles "empty" database
	 * records is unaffected.
	 * @param string $key 
	 * @return string The value
	 */
	public function frag($key) {
		if (is_string($val = Fragment::value($key))) return trim($val);
		return $val;
	}

	/**
	 * Get the value of an Element given it's key
	 *
	 * @param  string $key 
	 * @return mixed
	 */
	public function el($key) {
		return app('decoy.elements')->get($key);
	}

	/**
	 * Is Decoy handling the request?  Check if the current path is exactly "admin" or if
	 * it contains admin/*
	 * @return boolean 
	 */
	private $is_handling;
	public function handling() {
		if (!is_null($this->is_handling)) return $this->is_handling;
		$this->is_handling = preg_match('#^'.Config::get('decoy::core.dir').'($|/)'.'#i', Request::path());
		return $this->is_handling;
	}

	/**
	 * Force Decoy to believe that it's handling or not handling the request
	 * @param boolean $bool 
	 * @return void 
	 */
	public function forceHandling($bool) {
		$this->is_handling = $bool;
	}

	/**
	 * Set or return the current locale.  Default to the first key from 
	 * `decoy::site.locale`.
	 *
	 * @param string $locale A key from the `decoy::site.locale` array
	 * @return string 
	 */
	public function locale($locale = null) {

		// Set the locale if a valid local is passed
		$locales = Config::get('decoy::site.locales');
		if ($locale && isset($locales[$locale])) return Session::set('locale', $locale);

		// Return the current locale or default to first one
		return Session::get('locale', $this->defaultLocale());
	}

	/**
	 * Get the default locale, aka, the first locales array key
	 *
	 * @return string 
	 */
	public function defaultLocale() {
		if (($locales = Config::get('decoy::site.locales'))
			&& is_array($locales)) {
			reset($locales);
			return key($locales);
		}
	}

}
