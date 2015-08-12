<?php

// Massage the naigation data into a format that is more usable for
// the nav partial
View::composer('decoy::layouts.sidebar._nav', function($view) {

	// Get the navigation pages from the config
	$pages = config('decoy.site.nav');
	if (is_callable($pages)) $pages = call_user_func($pages);
	
	// Break the icon out of the label, returning an arary of label and icon
	function makeIcon($label_and_icon) {
		$parts = explode(',', $label_and_icon);
		if (count($parts) == 2) return ['label' => $parts[0], 'icon' => $parts[1]];
		else return ['label' => $parts[0], 'icon' => 'default'];
	};

	// Make a "page" object
	function makePage($key, $val) {
		
		// Check if it's a divider
		if ($val === '-') {
			return (object) array('divider' => true);
		}
		
		// Create a new page
		$page = ['url' => $val, 'divider' => false];
		$page = array_merge($page, makeIcon($key));
		
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
			$page = ['active' => false];
			$page = array_merge($page, makeIcon($key));
			$page['children'] = [];

			
			// Loop through children (we only support one level deep) and
			// add each as a child
			foreach($val as $child_key => $child_val) {
				$page['children'][] = makePage($child_key, $child_val);
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
			$massaged[] = makePage($key, $val);
		}
	}
	
	// Pass along the navigation data
	$view->pages = $massaged;
	
});