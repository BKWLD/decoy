<?php namespace Bkwld\Decoy;

// Dependencies
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Models\Fragment;
use Bkwld\Library;
use Croppa;
use Former;
use Request;
use Session;
use Illuminate\Support\Str;
use View;

/**
 * These function like the Laravel `Html` view helpers.  This class is bound
 * to the App IoC container as "decoy".  Thus, Decoy::helperName() can be
 * used to invoke them from views.
 */
class Helpers {
	
	/**
	 * The current locale, cached in memory
	 *
	 * @var string 
	 */
	private $locale;

	/**
	 * Generate title tags based on section content
	 *
	 * @return string
	 */
	public function title() {
		
		// If no title has been set, try to figure it out based on
		// default breadcrumbs
		$title = View::yieldContent('title');
		if (empty($title)) $title = Breadcrumbs::title(Breadcrumbs::defaults());

		// Set the title
		$site = $this->site();
		return '<title>' . ($title ? "$title | $site" : $site) . '</title>';
	}

	/**
	 * Get the site name
	 *
	 * @return string
	 */
	public function site() {
		$site = config('decoy.site.name');
		if (is_callable($site)) $site = call_user_func($site);
		return $site;
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
		if (strpos($path, '/elements/') !== false) return 'elements index';

		// Get the controller and action from the URL
		preg_match('#/([a-z-]+)(?:/\d+)?(?:/(create|edit))?$#i', $path, $matches);
		$controller = empty($matches[1]) ? 'login' : $matches[1];
		$action = empty($matches[2]) ? 'index' : $matches[2];
		array_push($classes, $controller, $action);

		// Add the admin roles
		if (($roles = app('decoy.auth')->roles())
			&& (is_array($roles) || class_implements($roles, 'Illuminate\Support\Contracts\ArrayableInterface'))) {
			foreach($roles as $role) {
				array_push($classes, 'role-'.$role);
			}
		}

		// Return the list of classes
		return implode(' ', $classes);
	}

	/**
	 * Convert a key named with array syntax (i.e 'types[marquee][video]') to one
	 * named with dot syntax (i.e. 'types.marquee.video]').  The latter is how fields
	 * will be stored in the db
	 *
	 * @param string $attribute 
	 * @return string 
	 */
	public function convertToDotSyntax($key) {
		return str_replace(['[', ']'], ['.', ''], $key);
	}

	/**
	 * Do the reverse of convertKeyToDotSyntax()
	 *
	 * @param string $attribute 
	 * @return string 
	 */
	public function convertToArraySyntax($key) {
		if (strpos($key, '.') === false) return $key;
		$key = str_replace('.', '][', $key);
		$key = preg_replace('#\]#', '', $key, 1);
		return $key.']';
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
			$locales = config('decoy.site.locales');
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
	 * Get the value of an Element given it's key
	 *
	 * @param  string $key 
	 * @return mixed
	 */
	public function el($key) {
		return app('decoy.elements')->localize($this->locale())->get($key);
	}

	/**
	 * Is Decoy handling the request?  Check if the current path is exactly "admin" or if
	 * it contains admin/*
	 * @return boolean 
	 */
	private $is_handling;
	public function handling() {
		if (!is_null($this->is_handling)) return $this->is_handling;
		$this->is_handling = preg_match('#^'.config('decoy.core.dir').'($|/)'.'#i', Request::path());
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
		if ($locale 
			&& ($locales = config('decoy.site.locales'))
			&& is_array($locales)
			&& isset($locales[$locale])) return Session::set('locale', $locale);

		// Return the current locale or default to first one.  Store it in a local var
		// so that multiple calls don't have to do any complicated work.  We're assuming
		// the locale won't change within a single request.
		if (!$this->locale) $this->locale = Session::get('locale', $this->defaultLocale());
		return $this->locale;
	}

	/**
	 * Get the default locale, aka, the first locales array key
	 *
	 * @return string 
	 */
	public function defaultLocale() {
		if (($locales = config('decoy.site.locales'))
			&& is_array($locales)) {
			reset($locales);
			return key($locales);
		}
	}

	/**
	 * Get the model class string from a controller class string
	 *
	 * @param  string $controller ex: "Admin\SlidesController"
	 * @return string ex: "Slide"
	 */
	public function modelForController($controller) {

		// Swap out the namespace if decoy
		$model = str_replace('Bkwld\Decoy\Controllers', 'Bkwld\Decoy\Models', $controller, $is_decoy);
		
		// Remove the Controller suffix app classes may have
		$model = preg_replace('#Controller$#', '', $model);
		
		// Assume that non-decoy models want the first namespace (aka Admin) removed
		if (!$is_decoy) $model = preg_replace('#^\w+'.preg_quote('\\').'#', '', $model);
		
		// Make it singular
		$model = Str::singular($model);
		return $model;
	}

}
