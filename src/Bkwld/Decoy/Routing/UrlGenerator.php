<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Config;
use Illuminate\Http\Request;

/**
 * This class exists to help make links between pages in Decoy, which is
 * complicated because none of the routes are explicitly defined.  All of
 * the relationships and breadcrumbs are created through controller, models,
 * and reading the current URL
 */
class UrlGenerator {
	
	// DI properties
	private $path;
	
	// Possible actions in path that would process a view that would be generating URLs
	private $actions = array('edit', 'create', 'destroy');
	
	/**
	 * Inject dependencies
	 * @param string $path A Request::path()
	 */
	public function __construct($path) {
		$this->path = $path;
	}
	
	/**
	 * Construct a route that takes into account the current url path as well
	 * as the function arguments
	 * @param string $action The action we're linking to: index/edit/etc
	 * @param integer $id Optional id that we're linking to.  Required for actions like edit.
	 *                    When specifying a child parameter, this would be the id of the parent
	 *                    row that the child hangs from.
	 * @param string $child The name (or full class) of a child controller 
	 *                      of the current path: 'slides', 'Admin\SlidesController'
	 * @return string '/admin/articles'
	 */
	public function relative($action = 'index', $id = null, $child = null) {
		
		// Get the current path, adding a leading slash that should be missing
		$path = '/'.$this->path;
		
		// Get the URL up to and including the last controller, but without id or action,
		// by stripping those extra stuffs from the end.  Any trailing slashes are removed
		$pattern = '#(/\d+)?(/('.implode('|', $this->actions).'))?/?$#i';
		$path = preg_replace($pattern, '', $path);	
	
		// If there is an id, add it now
		if ($id) $path .= '/'.$id;
		
		// If there is a child controller, add that now
		if ($child) {
			
			// If the child has a backslash, it's a namespaced class name, so convert to just name
			if (strpos($child, '\\') !== false) $child = $this->controllerForUrl($child);
			
			// If the child is the same as the current controller in the path, then don't add the
			// child.  For instance, if you are on an articles controller and the child is for
			// articles, don't form a child link.  This logic exists so we can execute relative()
			// from listing views and pass it the controller of a list item and not worrk about
			// whether we're already on that page or whether the list is for related data.
			if (!preg_match('#'.$child.'(/\d+)?$#i', $path)) $path .= '/'.$child;
			
		}
		
		// Now, add actions (except for index, which is implied by the lack of an action)
		if ($action && $action != 'index') $path .= '/'.$action;
		
		// Done, return it
		return $path;
		
	}
	
	/**
	 * Make a URL given a fully namespaced controller.  This only generates routes
	 * as if the controller is in the root level; as if it has no parents.
	 * @param string $controller ex: Bkwld\Decoy\Controllers\Admins@create
	 * @return string ex: http://admin/admins/create
	 */
	public function controller($controller = null, $id = null) {
		
		// Assume that the current, first path segment is the directory decoy is
		// running in
		preg_match('#[a-z-]+#i', $this->path, $matches);
		$decoy = $matches[0];
		
		// Strip the action from the controller
		$action = '';
		if (preg_match('#@\w+$#', $controller, $matches)) {
			$action = substr($matches[0], 1);
			$controller = substr($controller, 0, -strlen($matches[0]));
		}
		
		// Convert controller for URL
		$controller = $this->controllerForUrl($controller);
		
		// Begin the url
		$path = '/'.$decoy.'/'.$controller;
		
		// If there is an id, add it now
		if ($id) $path .= '/'.$id;
		
		// Now, add actions (except for index, which is implied by the lack of an action)
		if ($action && $action != 'index') $path .= '/'.$action;
		
		// Done, return it
		return $path;
		
	}
	
	/**
	 * Convert a controller to how it is referenced in a url
	 * @param string $controller ex: Admin\ArticlesAreCoolController
	 * @return string ex: articles-are-cool
	 */
	private function controllerForUrl($controller) {
		
		// Get the controller name
		$controller = preg_replace('#^('.preg_quote('Bkwld\Decoy\Controllers\\').'|'.preg_quote('Admin\\').')#', '', $controller);
		$controller = preg_replace('#Controller$#', '', $controller);
		
		// Convert study caps to dashes
		preg_match_all('#[a-z]+|[A-Z][a-z]*#', $controller, $matches);
		$controller = implode("-", $matches[0]);
		$controller = strtolower($controller);
		
		// Done
		return $controller;
		
	}
	
	
}