<?php

/*
This partial is used to generate the most common list type views
found in the admin.  It expects the following variables to have been set
as part of when the view was created.  As in View::make()->with()

	- title : The title of this page.
	
	- listing : The data that is being iterated over.  This may or may
		not be paginated
		
	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- columns (optional) : An array of key value pairs.  The keys are the title of
		the column.  The values are the database column or method to call
		to find the data.  For instance, when creating a news listing, 
		you might expect columns to look like:
		
			array('Title'=>'title', 'Abstract'=>'create_abstract')
		
		Where "create_abstract" would be a method defined on the model that
		truncates the body of the news article.
		
		Also, remember that 'title' may invoke a the Decoy\Base_Model method 
		of title().  This in turn looks for columns named using common title names.
		
	- auto_link ['first' (default), 'all', false] : Surround columns
	  in link tags
	  	
	- convert_dates ['date' (default), 'datetime', 'time', false] : Convert
	  columns that look like dates into readable versions
	  
	- sidebar [false (default), true] : Determines whether to adjust the layout
	  for the list appearing in a sidebar, as in related data
	
	- parent_id (optional) : When using the sidebar layout, this informs
	  how to create the new link.  If not defined, it is pulled from the last
	  segment of the current URL
	  
  - parent_controller (optional) - When using the sidebar layout, informs logic needed
    for many to many forms.  Generally this is calculated automatically
	  
	- description (optional) : A description for the view
	
	- many_to_many [false (default), true] : Makes the list view have an
	  autocomplete in place of the normal "New" link.  This forms a relationship
	  from the controller hosting this list and the item that is selected in the
	  pulldown.  Part of this mojo involves a backbone js view.  You can let this
	  set itself for the most part
	  
	- tags [false (default), true] : Lets the user create new rows from the listing
	  view.  Tags means the content is very simple, there is only a single field the
	  user needs to input.  This should typically be allowed to set it itself automatically.
	  
	- search : The $SEARCH array passed through from the controller config

	  
*/
View::composer('decoy::shared.list._standard', function($view) {
	
	// Required fields
	$required = array('title', 'listing', 'controller');
	foreach($required as $field) {
		if (!isset($view->$field)) throw new Exception('Standard listing field is not set: '.$field);
	}
	
	// Make an instance of the controller so values that get in the constructor can be inspected
	$controller = new $view->controller;
	
	// Default settings
	$defaults = array(
		'columns'       => array('Title' => 'title'),
		'auto_link'     => 'first',
		'convert_dates' => 'date',
		'sidebar'       => false,
		'parent_id'     => Request::segment(3), // This spot always holds it
		'parent_controller' => $controller->parent_controller(),
		'many_to_many'  => $controller->is_child_in_many_to_many(),
		'tags'          => is_subclass_of($controller->model(), 'Bkwld\Decoy\Models\Tag') ? true : false,
	);
	
	// Apply defaults
	foreach($defaults as $key => $val) {
		if (!isset($view->$key)) $view->$key = $val;
	}
	
	// Massage the shorthand search config options
	if (isset($view->search)) $view->search = Bkwld\Decoy\Search::longhand($view->search);
	
	// Set a common variable for both types of lists that get passed to the view
	if (isset($view->listing->results)) $view->iterator = $view->listing->results;
	else $view->iterator = $view->listing;
	
	// Make the link to the child listing, which is dependent on the current URL. I can't
	// straight up use a route() because those aren't able to distinguish between controllers
	// that are children to multiple parents
	if ($view->many_to_many) {
		$handles = Config::get('decoy::dir');
		$controller_name = substr($view->controller, strlen($handles)+1);
		$view->child_route = route($view->parent_controller).'/'.$view->parent_id.'/'.$controller_name;
		
	// Else, this list has a parent, create a link to the child listing
	} elseif (!$view->many_to_many && $view->parent_id) {
		$view->child_route = route($view->controller.'@child', $view->parent_id);
	}
	
});