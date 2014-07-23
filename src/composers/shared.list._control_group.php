<?php

/*
	This can take the same arguments as shared.list._standard	  
*/
View::composer('decoy::shared.list._control_group', function($view) {
	
	// Required fields
	$required = array('listing', 'controller');
	foreach($required as $field) {
		if (!isset($view->$field)) throw new Exception('control_group listing field is not set: '.$field);
	}
	
	// Make an instance of the controller so values that get in the constructor can be inspected
	if (empty($view->controller_inst)) $view->controller_inst = new $view->controller;

	// Default settings
	$defaults = array(
		'title'        => $view->controller_inst->title(),
		'description'  => $view->controller_inst->description(),
		'many_to_many' => $view->controller_inst->isChildInManyToMany(),
	);

	// Apply defaults
	foreach($defaults as $key => $val) {
		if (!isset($view->$key)) $view->$key = $val;
	}
		
});