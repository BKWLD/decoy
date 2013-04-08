<?php

// Imports
use Decoy\Breadcrumbs;

// Take breadcrumbs data passed to the view and massage it.  Or
// make defaults if none exists
View::composer('decoy::layouts._breadcrumbs', function($view) {
	
	// Make default breadcrumbs if none are set
	if (empty($view->breadcrumbs)) $view->breadcrumbs = Breadcrumbs::defaults();

	// Get the back button URL
	$view->back = Breadcrumbs::back($view->breadcrumbs);

	// Count the total breadcrumbs 
	$view->breadcrumb_count = count($view->breadcrumbs);

	// Set the page title
	Section::inject('title', Breadcrumbs::title($view->breadcrumbs));
	
});