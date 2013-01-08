<?php

/*
This partial is used to generate the most common list type views
found in the admin.  It expects the following variables to have been set
as part of when the view was created.  As in View::make()->with()

	- title : The title of this page.
	
	- listing : The data that is being iterated over.  This maye or may
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
	  
	- sortable [default:false] : Enable drag and drop sorting
	
	- convert_dates ['date' (default), 'datetime', 'time', false] : Convert
	  columns that look like dates into readable versions
	  
	- sidebar [false (default), true] : Determines whether to adjust the layout
	  for the list appearing in a sidebar, as in related data
	  
	- parent_id (optional) : When using the sidebar layout, this informs
	  how to create the new link.  If not defined, it is pulled from the last
	  segment of the current URL
	  
	- description (optional) : A description for the view
	
	- many_to_many [false (default), true] : Makes the list view have an
	  autocomplete in place of the normal "New" link.  This forms a relationship
	  from the controller hosting this list and the item that is selected in the
	  pulldown.  Part of this mojo involves a backbone js view
	  
	- tags [false (default), true] : Lets the user create new rows from the listing
	  view.  Tags means the content is very simple, there is only a single field the
	  user needs to input.

	  
*/
View::composer('decoy::shared.list._standard', function($view) {
	
	// Required fields
	$required = array('title', 'listing', 'controller');
	foreach($required as $field) {
		if (!isset($view->$field)) throw new Exception('Standard listing field is not set: '.$field);
	}
	
	// Default settings
	$defaults = array(
		'columns'       => array('Title' => 'title'),
		'auto_link'     => 'first',
		'sortable'      => false,
		'convert_dates' => 'date',
		'sidebar'       => false,
		'parent_id'     => URI::segment(3), // This spot always holds it
		'many_to_many'  => false,
		'tags'          => false,
	);

	// Apply defaults
	foreach($defaults as $key => $val) {
		if (!isset($view->$key)) $view->$key = $val;
	}
	
	// Currently, only allow tags for many to manys
	if (!$view->many_to_many && $view->tags) throw new Exception('Currently tags are only allowed for many to many');
	
	// Set a common variable for both types of lists that get passed to the view
	if (isset($view->listing->results)) $view->iterator = $view->listing->results;
	else $view->iterator = $view->listing;
	
});