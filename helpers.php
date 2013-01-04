<?php

// Imports
use Decoy\Breadcrumbs;

// HTML::title() -- Format title based on section content
HTML::macro('title', function() {
	
	// If no title has been set, try to figure it out based on
	// default breadcrumbs
	$title = Section::yield('title');
	if (empty($title)) $title = Breadcrumbs::title(Breadcrumbs::defaults());
	
	// Get the site name
	$site = Config::get('decoy::decoy.site_name');

	// Set the title
	return '<title>' . ($title ? "$title | $site" : $site) . '</title>';
});


/**
 * Formats the data in the standard list shared partial.
 * - $item - A row of data from a Model query
 * - $column - The field name that we're currently displaying
 * - $conver_dates - A string that matches one of the date_formats
 *
 * I tried very hard to get this code to be an aonoymous function that was passed
 * to the view by the view composer that handles the standard list, but PHP
 * wouldn't let me.
 */
HTML::macro('render_list_column', function($item, $column, $convert_dates) {
	
	// Date formats
	$date_formats = array(
		'date'     => FORMAT_DATE,
		'datetime' => FORMAT_DATETIME,
		'time'     => FORMAT_TIME,
	);
	
	// If the object has a method defined with the column vaue, use it
	if (method_exists($item, $column)) {
		return call_user_func(array($item, $column));
	
	// Else if the column is a property, echo it
	} elseif (isset($item->$column)) {
		
		// Format date if appropriate
		if ($convert_dates && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $item->$column)) {
			return date($date_formats[$convert_dates], strtotime($item->$column));
			
		// Just display the column value
		} else {
			return $item->$column;
		}
	
	// Else, just display it as a string
	} else {
		return $column;
	}
	
});

/**
 * Make an image upload field.  That is to say, one that displays a sample if an
 * image has already been uploaded
 */
HTML::macro('image_upload', function($image, $id = null, $label = null, $help = null) {
	
	// Defaults
	if ($id === null) $id = 'image';
	$block_help = '';
	
	// Add the passed help text
	if ($help) $block_help .= '<span class="image-help">'.$help.'</span>';
	
	// If on an edit view, show the old image and a delete button.  Also, store the old filename
	// in a hidden field with the passed $id 
	if (!empty($image)) {
		
		// Make the HTML for the old image
		$img_class = 'img-polaroid';
		if (!$help) $img_class .= ' no-help'; // This style adjusts margins
		$block_help .= '<a href="'.$image.'"><img src="'.Croppa::url($image, 400).'" class="'.$img_class.'"></a>';
		
		// Figure out if the field should be required
		$rules = Former::getRules($id);
		$is_required = !empty($rules) && array_key_exists('required', $rules);
		
		// Add delete checkbox
		if (!$is_required) {
			$block_help .= '<label for="delete" class="checkbox image-delete">
				<input id="'.UPLOAD_DELETE.$id.'" type="checkbox" name="delete-'.$id.'" value="1">Delete 
				<a href="'.$image.'"><code><i class="icon-file"></i>'.basename($image).'</code></a></label>';
		}
		
		// Change the id of the form input field and create the hidden field with the original id
		// and with the value of the image path.  (string) foreces it to render.
		$hidden = (string) Former::hidden(UPLOAD_OLD.$id)->value($image);
		if (!$label) $label = ucwords($id);
		
		// Check for errors registered to the "real" form element
		$errors = Former::getErrors($id);
		
		// Make the file field.  We're setting a class of required rather than actually setting the field
		// to required so that Former doesn't tell the DOM to the required attribute.  We don't want the
		// browser to enforce requirement, we just want the icon to indicate that it is required.
		$file = Former::file(UPLOAD_REPLACE.$id, $label)->accept('image')->blockHelp($block_help);
		if ($is_required) $file = $file->class('required');
		if (!empty($errors)) $file = $file->state('error')->inlineHelp($errors);
		return $file.$hidden;
		
	// Else, on a create / new form, so just show a simple file input field
	} else return Former::file($id, $label)->accept('image')->blockHelp($block_help);
	
});

/**
 * Render the UI that the JS expecting to render a datalist style autocomplete menu.
 * A datalist style takes a key value pair from the server and when the user chooses
 * an option, stores the choice in a hidden input field.  It's simplest form of
 * autocomplete.
 * 
 * - $id - The id/name of the input field
 * - $route - The GET route that will return data for the autocomplete.
 *   The response should be an array of key / value pairs where the key is
 *   what will get submitted and the value is the title that is displayed to users.
 * - $old - The old value
 * - $options - An associative array that supports:
 *     - label - The label for the field.  If undefined, uses the id
 *     - old_title - The title of the old value.  This would be used if $old is an int like a foreign_id.
 *     - view - A boolean, if true, allows the user to enter values not in autocomplete
 */
HTML::macro('datalist', function($id, $route, $old = null, $options = array()) {
	
	// Start data array
	$data = array(
		'id' => $id,
		'route' => $route,
		'old' => $old,
	);
	
	// Default options
	if (empty($options['label']))     $options['label'] = ucfirst($id);
	if (empty($options['old_title'])) $options['old_title'] = $old;
	if (empty($options['allow_new'])) $options['allow_new'] = false;
	
	// Allow New isn't supported yet
	if ($options['allow_new']) throw new Exception('allow_new is not supported yet');
	
	// Render the view
	$data = array_merge($data, $options);
	return render('decoy::shared.form.autocomplete._datalist', $data);
	
});