<?php

/*
This partial is used to generate the most common list type views
found in the admin.  It expects the following variables to have been set
as part of when the view was created.  As in View::make()->with()

	- title (optional): The title of this page.
	
	- listing : The data that is being iterated over.  This may or may
		not be paginated
		
	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'Admin\NewsController'
		
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
	  
	- layout ['full' (default), 'sidebar', 'control group'] : How to render the listing
	
	- parent_id (optional) : When using the non-full layout, this informs
	  how to create the new link.  If not defined, it is pulled from the last
	  segment of the current URL
	  
  - parent_controller (optional) - When using the non-full layout, informs logic needed
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
	  
	- search : The $search array passed through from the controller config

	  
*/
View::composer('decoy::shared.list._standard', function($view) {
	
	// Required fields
	$required = array('listing', 'controller');
	foreach($required as $field) {
		if (!isset($view->$field)) throw new Exception('Standard listing field is not set: '.$field);
	}
	
	// Make an instance of the controller so values that get in the constructor can be inspected
	if (empty($view->controller_inst)) $view->controller_inst = new $view->controller;

	// Figure out the parent_id, which will be the last numeric segment in the url
	preg_match('#/(\d+)/[a-z-]*$#i', Request::path(), $matches);
	$parent_id = isset($matches[1]) ? $matches[1] : null;

	// Default settings
	$defaults = array(
		'title'             => $view->controller_inst->title(),
		'description'       => $view->controller_inst->description(),
		'columns'           => array('Title' => 'title'),
		'auto_link'         => 'first',
		'convert_dates'     => 'date',
		'layout'            => 'full',
		'parent_id'         => $parent_id,
		'parent_controller' => $view->controller_inst->parentController(),
		'many_to_many'      => $view->controller_inst->isChildInManyToMany(),
		'tags'              => is_a($view->controller_inst->model(), 'Bkwld\Decoy\Models\Tag') ? true : false,
		'count'             => is_a($view->listing, 'Illuminate\Pagination\Paginator') ? $view->listing->getTotal() : $view->listing->count(),
		'paginator_from'    => (Input::get('page', 1)-1) * Input::get('count', Bkwld\Decoy\Controllers\Base::$per_page),
	);

	// Apply defaults
	foreach($defaults as $key => $val) {
		if (!isset($view->$key)) $view->$key = $val;
	}
	
	// Massage the shorthand search config options
	if (isset($view->search)) {
		$search = new Bkwld\Decoy\Input\Search();
		$view->search = $search->longhand($view->search);
	}

	
});