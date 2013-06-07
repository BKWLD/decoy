<?php

// Massage the naigation data into a format that is more usable for
// the nav partial
View::composer('decoy::layouts._nav', function($view) {

	// Get the navigation pages from the config
	$pages = Config::get('decoy::nav');
	
	// Make a "page" object
	$make_page = function($key, $val) {
		
		// Check if it's a divider
		if ($val === '-') {
			return (object) array('divider' => true);
		}
		
		// Create a new page
		$page = array('url' => $val, 'divider' => false);
		
		// If key is a number, create a title for it.  Otherwise,
		// use the key's value
		if (is_int($key)) $page['label'] = ucwords(basename($val));
		else $page['label'] = $key;
		
		// Check if this item is the currently selected one
		$page['active'] = false;
		if (strpos(URL::current(), parse_url($page['url'], PHP_URL_PATH))) {
			$page['active'] = true;
		}
			
		// Return the new page
		return (object) $page;
		
	};
	
	// Loop through the list of pages and massage
	$massaged = array();
	foreach($pages as $key => $val) {
		
		// If val is an array, make a drop down menu
		if (is_array($val)) {
			
			// Create a new page instance that represents the dropdown menu
			$page = array('label' => $key, 'active' => false);
			$page['children'] = array();
			
			// Loop through children (we only support one level deep) and
			// add each as a child
			foreach($val as $child_key => $child_val) {
				$page['children'][] = $make_page($child_key, $child_val);
			}
			
			// See if any of the children are active and set the pulldown to active
			foreach($page['children'] as $child) {
				if (!empty($child->active)) {
					$page['active'] = true;
					break;
				}
			}
			
			// Add the pulldown to the list of pages
			$massaged[] = (object) $page;
		
		// The page is a simple (non pulldown) link
		} else {
			$massaged[] = $make_page($key, $val);
		}
	}
	
	// Pass along the navigation data
	$view->pages = $massaged;
	
});