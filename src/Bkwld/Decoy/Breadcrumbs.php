<?php namespace Bkwld\Decoy;

// Imports
use \URL;
use \Request;
use \Config;
use \Bundle;
use \Log;

// This class has shared methods that assist in the generation of breadcrumbs
class Breadcrumbs {
	
	/**
	 * Make some default breadcrumbs that are based on URL segments
	 * @param $string path The path part of a URL
	 */
	static public function defaults($path = null) {
		
		// If no URL is defined, use the current
		if (!$path) $path = Request::path();
		$path = preg_replace('#^/#', '', $path); // Strip opening slash
		
		// Break apart the url
		$breadcrumbs = array();
		$parts = explode('/', $path);
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
	static public function back($breadcrumbs = null) {
		
		// Optional argument
		if (!$breadcrumbs) $breadcrumbs = self::defaults();
		
		// If there aren't enough breadcrumbs for a back URL, report false
		if (count($breadcrumbs) < 2) return false;
		
		// Get the URL from two back from the end in the breadcrumbs array
		$back = array_keys($breadcrumbs);
		$back = $back[count($back)-2];
		return $back;
	}
	
	// * If we are on a one level deep detail page, this goes back to the listing.
	// * If we are on a two level deep listing page, go back to the one level deep detail
	// * If we are on a two level deep detail pages, go back to the first level detail page
	// * If we are on a three level deep listing page, go back to the second level detail
	// * If we are on a three level deep detail page, go back to the second level detail page
	// Basically, the nuance here is so if you are editing the slides of a news page, when
	// you go "back", it's back to the news page and not the listing of the news slides
	static public function smart_back($breadcrumbs = null) {
		
		// Optional argument
		if (!$breadcrumbs) $breadcrumbs = self::defaults();
		
		// If we are on a listing page (an odd length), do the normal stuff
		// http://stackoverflow.com/a/9153969/59160
		if (count($breadcrumbs) & 1) return self::back($breadcrumbs);
		
		// If we're on the first level detail page, do normal stuff
		if (count($breadcrumbs) === 2) return self::back($breadcrumbs);
		
		// Otherwise, skip the previous (the listing) and go direct to the previous detail
		$back = array_keys($breadcrumbs);
		$back = $back[count($back)-3];
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
		$path = Request::path();
		$segments = explode('/', $path);
		
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
			
			// On a "new" page
			if ($id == 'new') {
				$url .= '/' . $id;
				$breadcrumbs[$url] = 'New';
				
			} elseif (is_numeric($id)) {
				$url .= '/' . $id;
				$model = $controller->model();
				$item = call_user_func($model.'::find', $id);
				$breadcrumbs[$url] = $item->title();
			}
		}
		
		// Return the full list
		return $breadcrumbs;
	}
}