<?php namespace Decoy;

// Imports
use Laravel\URI;
use Laravel\Request;
use Laravel\Config;
use Laravel\Bundle;

// This class has shared methods that assist in the generation of breadcrumbs
class Breadcrumbs {
	
	// Make some default breadcrumbs that are based
	// on URL segments
	static public function defaults() {
		$breadcrumbs = array();
		$parts = explode('/', URI::current());
		$path = '/'.array_shift($parts); // Remove the first item
		
		// Loop through all url segements and create breadcrumbs out of them
		foreach($parts as $part) {
			$path .= '/'.$part;
			$breadcrumbs[$path] = ucwords(str_replace('_', ' ', $part));
		}
		return $breadcrumbs;
	}
	
	// Get the url for a back button given a breadcrumbs array.  Or
	// return false if there is no where to go back to.
	static public function back($breadcrumbs) {
		
		// If there aren't enough breadcrumbs for a back URL, report false
		if (count($breadcrumbs) < 2) return false;
		
		// Get the URL from two back from the end in the breadcrumbs array
		$back = array_keys($breadcrumbs);
		$back = $back[count($back)-2];
		return $back;
	}
	
	// Use the top most breadcrumb label as the page title.  If the breadcrumbs
	// are at least 2 deep, use the one two back as the category for the title.
	// for instance, this will make a title like "Admins - John Smith | Site Name"
	static public function title($breadcrumbs) {
		$values = array_values($breadcrumbs);
		$title = array_pop($values);
		if (count($breadcrumbs) > 1) $title = array_pop($values).' - '.$title;
		$title = strip_tags($title);
		return $title;
	}
	
	// Try to set the breadcrumbs automatically by looking at the
	// $parent_controllers array
	static public function generate_from_routes() {
		$breadcrumbs = array();
		
		return $breadcrumbs;
		
		
		// Basic info about the request
		$action = Request::route()->controller_action;
		$controller_route = Request::route()->controller;
		$controller = \BKWLD\Laravel\Controller::resolve_with_bundle($controller_route);
		$is_child_route = $controller->is_child_route();
		
		// Add the current controller first (we'll be building in reverse order)
		if ($action == 'edit') {
			$id = URI::segment(3);
			$model = $controller->model();
			$item = call_user_func($model.'::find', $id);
			$breadcrumbs['/'.URI::current()] = $item->title();
		} elseif ($action == 'new') {
			$breadcrumbs['/'.URI::current()] = 'New';
		}
		
		// If were on an index view and that index has a category (in other words
		// a second argument), add the category as it's own breadcrumb.  The category
		// is in a different segment depending on whether this controller is being implmented
		// as a child or not.
		if ($action == 'index') {
			if ($is_child_route && URI::segment(3)) {
				$breadcrumbs['/'.URI::current()] = ucwords(URI::segment(3));
			} elseif (URI::segment(5))  {
				$breadcrumbs['/'.URI::current()] = ucwords(URI::segment(5));
			}
		}
		
		// There are no parent controllers so add the listing and be done with it
		if (!$is_child_route) {
			$breadcrumbs[route($controller->controller())] = $controller->title();
			return array_reverse($breadcrumbs);
		}
						
		// Get the parent row.  Unless the current page is an edit view, we don't
		// have an instance to pull a relationship from, so we must start with the
		// parent
		if ($action == 'edit') {
		// 	$parent_item = $item->{$this->SELF_TO_PARENT};
		} else {
			$parent_id = URI::segment(3);
		// 	$parent_item = self::parent_find($parent_id);
		}
		
		// The current controller is a child of another, so add that child listing link 		
		$breadcrumbs[route($controller_route.'@child', $parent_id)] = $controller->title();
		
		// Get info on the parent
		$parent_controller_route = $controller->parent_controller();
		$parent_controller = \BKWLD\Laravel\Controller::resolve_with_bundle($parent_controller_route);
		
		// Add the parent edit page
		$breadcrumbs[route($parent_controller_route.'@edit', $parent_item->id)] = $parent_item->title();
		
		
		
		return array_reverse($breadcrumbs);
		
		
		
		
		
		
		// The current page was a child
		$breadcrumbs[route($this->CONTROLLER.'@child', $parent_item->id)] = $this->TITLE;
		
		// Loop through all the parent controllers and create breadcrumbs
		foreach(array_reverse($this->parent_controllers) as $i => $controller) {
			
			// Add the detail view
			$breadcrumbs[route($controller.'@edit', $parent_item->id)] = $parent_item->title();
			
			// Create an instance of the parent controller for use in getting the title
			$parent_controller_instance = Controller::resolve(DEFAULT_BUNDLE, $controller);
			
			// Determine the route of the listing view
			if ($i == count($this->parent_controllers)-1) $route = route($controller);
			else {
				
				// Setup the parent item for the next iteration
				$parent_item = $parent_item->{$parent_controller_instance->SELF_TO_PARENT};
				
				// Create the child listing route
				$route = route($controller.'@child', array($parent_item->id));
			}
			
			// Add the listing breadcrumb
			$breadcrumbs[$route] = $parent_controller_instance->TITLE;
			
		}
		
		// Set the breadcrumbs
		return array_reverse($breadcrumbs);
		
	}
	
	// Find the parent controller of a controller
	static private function find_parent_controllers($controllers, $parent = null) {
		$handles = Bundle::option('decoy', 'handles');
		foreach($controllers as $key => $val) {

			// Process items that have an array (that have children)
			$is_many_to_many = false;
			if (is_array($val)) {
				
				// If one of the children of this route has matched, it will be returning
				// that parent in an array.  Prepend the next parent to the array and 
				// pass it along until we're out of the recursive function
				$parents = self::find_parent_controllers($val, $key);
				if (!empty($parents)) {
					if ($parent) array_unshift($parents, $handles.'.'.$parent);
					return $parents;
				}
				
				// Else, see if the key matches, which is a controller itself
				else $controller = $key;
			
			// This item has no children
			} else $controller = $val;
			
			// Does the path to THIS controller match the route we're looking at from the array?
			if (Request::route()->controller == $handles.'.'.$controller) {
				
				// If it is does and we're looking at a child, then return the parent
				if ($parent) return array($handles.'.'.$parent);
				
				// If there is no parent, it was found in the primary depth, so return
				// an empty array to signifiy no parents
				else return array();
			}
		}
		return array(); // None found (this should never probably be reached)
	}
	
}