<?php namespace Bkwld\Decoy\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Make the Laravel UrlGenerator able to use wildcarded admin controllers
 * using the route() helper
 */
class UrlGenerator extends \Illuminate\Routing\UrlGenerator {
	
	/**
	 * Get the URL to a named route.
	 *
	 * @param  string  $name
	 * @param  mixed   $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	public function route($name, $parameters = array(), $absolute = true) {
		
		// Do regular execution
		try {
			parent::route($name, $parameters, $absolute);
			
		// The route wasn't found, see if it's a wildcarded decoy controller
		} catch (RouteNotFoundException $e) {
			
			// Get the action string
			$action = $this->resolveDecoyWildcardAction($name);
			
			// Get the URL by resolving as an action
			
			die("caught it");
		}
		
	}
	
	/**
	 * Detect admin controller
	 */
	
}