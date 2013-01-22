<?php namespace Decoy;

// Imports
use Laravel\URI;
use Laravel\Request;
use Laravel\Config;
use Laravel\Bundle;
use Laravel\Log;

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
	
	// Apply smarts to analyzing the URL
	static public function generate_from_url() {
		$breadcrumbs = array();

		// Get the segments
		$uri = URI::current();
		$segments = explode('/', $uri);
		
		// Loop through them in blocks of 2: [list, detail]
		$url = '/'.$segments[0];
		for($i=1; $i<count($segments); $i+=2) {

			// Find the contorller. Check if it's Decoy bundle if it's not found in the application
			$url .= '/' . $segments[$i];
			$controller = \BKWLD\Laravel\Controller::resolve_with_bundle($segments[0].'.'.$segments[$i]);
			if (!$controller) $controller = \BKWLD\Laravel\Controller::resolve_with_bundle('decoy::'.$segments[$i]);
			if (!$controller) continue;
			
			// Add controller to breadcrumbs
			$breadcrumbs[$url] = $controller->title();
			
			// Add a detail if it exists
			if (!isset($segments[$i+1])) break;
			$id = $segments[$i+1];
			if (!is_numeric($id)) continue;
			$url .= '/' . $id;
			$model = $controller->model();
			$item = call_user_func($model.'::find', $id);
			$breadcrumbs[$url] = $item->title();
			
		}
		
		// Return the full list
		return $breadcrumbs;
	}
}