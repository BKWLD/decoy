<?php namespace Decoy;

// Imports
use Laravel\URI;

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
		
		// Add the current controller first (we'll be building in reverse order)
		$action = Request::route()->controller_action;
		if ($action == 'edit') {
			$id = URI::segment(3);
			$item = Model::find($id);
			$breadcrumbs['/'.URI::current()] = $item->title();
		} elseif ($action == 'new') {
			$breadcrumbs['/'.URI::current()] = 'New';
		}
		
		// If were on an index view and that index has a category (in other words
		// a second argument), add the category as it's own breadcrumb.  The category
		// is in a different segment depending on whether this controller is being implmented
		// as a child or not.
		if ($action == 'index') {
			if (empty($this->parent_controllers) && URI::segment(3)) {
				$breadcrumbs['/'.URI::current()] = ucwords(URI::segment(3));
			} elseif (URI::segment(5))  {
				$breadcrumbs['/'.URI::current()] = ucwords(URI::segment(5));
			}
		}
		
		// There are no parent controllers so add the listing and be done with it
		if (empty($this->parent_controllers)) {
			$breadcrumbs[route($this->CONTROLLER)] = $this->TITLE;
			$this->breadcrumbs(array_reverse($breadcrumbs));
			return;
		}
		
		// Get the parent row.  Unless the current page is an edit view, we don't
		// have an instance to pull a relationship from, so we must start with the
		// parent
		if ($action == 'edit') {
			$parent_item = $item->{$this->SELF_TO_PARENT};
		} else {
			$parent_id = $id = URI::segment(3);
			$parent_item = self::parent_find($parent_id);
		}
				
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
		$this->breadcrumbs(array_reverse($breadcrumbs));
		
	}
	
}