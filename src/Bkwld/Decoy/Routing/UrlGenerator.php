<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use \Config;
use Illuminate\Http\Request;

/**
 * This class exists to help make links between pages in Decoy, which is
 * complicated because none of the routes are explicitly defined.  All of
 * the relationships and breadcrumbs are created through controller, models,
 * and reading the current URL
 */
class UrlGenerator {
	
	// DI properties
	private $request;
	
	// Possible actions in path that would process a view that would be generating URLs
	private $actions = array('edit', 'create');
	
	/**
	 * Inject dependencies
	 * @param Illuminate\Http\Request $request
	 */
	public function __construct(Request $request) {
		$this->request = $request;
	}
	
	/**
	 * Construct a route that takes into account the current url path as well
	 * as the function arguments
	 * @param string $action The action we're linking to: index/edit/etc
	 * @param integer $id Optional id that we're linking to.  Required for actions like edit
	 * @param string $child The name of a child controller of the current path: 'slides'
	 */
	public function relative($action = 'index', $id = null, $child = null) {
		
		// Get the current path
		$path = $this->request->path();
		
		// Get the URL up to and including the last controller, but without id or action,
		// by stripping those extra stuffs from the end.  Any trailing slashes are removed
		$pattern = '#(/\d)?(/('.implode('|', $this->actions).'))?/?$#i';
		$path = preg_replace($pattern, '', $path);	
	
		// If there is an id, add it now
		if ($id) $path .= '/'.$id;
		
		// If there is a child controller, add that now
		if ($child) $path .= '/'.$child;
		
		// Now, add actions (except for index, which is implied by the lack of an action)
		if ($action && $action != 'index') $path .= '/'.$action;
		
		// Done, return it
		return $path;
		
	}
	
	
}