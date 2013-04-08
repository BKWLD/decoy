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
	
	// Convert the item to an array so I can test for values
	$test_row = $item->to_array();
	
	// If the object has a method defined with the column vaue, use it
	if (method_exists($item, $column)) {
		return call_user_func(array($item, $column));
	
	// Else if the column is a property, echo it
	} elseif (array_key_exists($column, $test_row)) {

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
HTML::macro('image_upload', function($id = null, $label = null, $help = null, $crops = null) {
	
	// Defaults
	if ($id === null) $id = 'image';
	$block_help = '';
	$image = Former::getValue($id);
	if (!$label) $label = BKWLD\Utils\String::title_from_key($id);
	
	// Add the passed help text
	if ($help) $block_help .= '<span class="image-help">'.$help.'</span>';
		
	// On a create / new form, so just show a simple file input field
	if (empty($image)) {
		return '<div class="image-upload">'.Former::file($id, $label)->accept('image')->blockHelp($block_help).'</div>';	
	}
	
	// Else if on an edit view, show the old image and a delete button.  Also, store the old filename
	// in a hidden field with the passed $id 
		
	// Make the HTML for the old image
	$img_class = 'img-polaroid';
	if (!$help) $img_class .= ' no-help'; // This style adjusts margins
	
	// For each crop, make add a new image instance so we have a unique cropper instance.  Note: 
	// all of the markup is spans because I'm rendering this all inside the .help-block, which is
	// a p tag
	if (!empty($crops)) {
		$block_help .= '<span class="crops">';
		
		// Add the tabs
		$block_help .= '<span class="tabs" data-js-view="crop-styles">';
		$active = 'active';
		foreach($crops as $key => $val) {
			$crop = is_numeric($key) ? $val : $key;
			$block_help .= '<span class="'.$active.'">'.BKWLD\Utils\String::title_from_key($crop).'</span>';
			$active = null;
		}
		$block_help .= '</span>';
		
		// Add fullscreen button
		$block_help .= '<i class="icon-fullscreen fullscreen-toggle"></i>';
		
		// Add the images
		$block_help .= '<span class="imgs">';
		foreach($crops as $key => $val) {
			
			// Figure out the raio and crop name
			if (is_numeric($key)) {
				$style = $val;
				$ratio = null;
			} else {
				$style = $key;
				$ratio = $val;
			}
			
			// Create the HTML
			$block_help .= '<a href="'.$image.'"><img src="'.Croppa::url($image, 570).'" class="'.$img_class.'" data-ratio="'.$ratio.'" data-style="'.$style.'" data-js-view="crop"></a>';
	
		}
		$block_help .= '</span></span>';
	
	// There were no crops defined, so add a single image
	} else $block_help .= '<a href="'.$image.'"><img src="'.Croppa::url($image, 570).'" class="'.$img_class.' fullscreen-toggle"></a>';
	
	// Create a hidden field for the crops if they were defined.  Former requires this happens
	// before the file element is created
	$crop_field = !empty($crops) ? (string) Former::hidden($id.'_crops') : '';
	
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
	
	// Make the file field.  We're setting a class of required rather than actually setting the field
	// to required so that Former doesn't tell the DOM to the required attribute.  We don't want the
	// browser to enforce requirement, we just want the icon to indicate that it is required.
	$file = Former::file(UPLOAD_REPLACE.$id, $label)->accept('image')->blockHelp($block_help);
	if ($is_required) $file = $file->class('required');
	
	// Check for errors registered to the "real" form element
	$errors = Former::getErrors($id);
	if (!empty($errors)) $file = $file->state('error')->inlineHelp($errors);
	
	// Assemble all the elements
	$file = (string) $file;
	return '<div class="image-upload" data-js-view="image-fullscreen">'.$file.$hidden.$crop_field.'</div>';
	
});

/**
 * Make a file upload field.  It shows a download link for already uploaded files
 */
HTML::macro('file_upload', function($id = null, $label = null, $help = null) {
	
	// Defaults
	if ($id === null) $id = 'file';
	$block_help = '';
	$file = Former::getValue($id);
	if (!$label) $label = BKWLD\Utils\String::title_from_key($id);

	// Add the passed help text
	if ($help) $block_help .= '<span class="image-help">'.$help.'</span>';
	
	// If on a create / new form, so just show a simple file input field
	if (empty($file)) {
		return '<div class="file-upload">'.Former::file($id, $label)->blockHelp($block_help).'</div>';
	}
	
	// If on an edit view, show the old image and a delete button.  Also, store the old filename
	// in a hidden field with the passed $id
	
	// Figure out if the field should be required
	$rules = Former::getRules($id);
	$is_required = !empty($rules) && array_key_exists('required', $rules);
	
	// Add delete checkbox
	if (!$is_required) {
		$block_help .= '<label for="delete" class="checkbox file-delete">
			<input id="'.UPLOAD_DELETE.$id.'" type="checkbox" name="delete-'.$id.'" value="1">Delete 
			<a href="'.$file.'"><code><i class="icon-file"></i>'.basename($file).'</code></a></label>';
	
	// Else display a link to download the file
	} else {
		$block_help .= '<label class="download">
			Currently <a href="'.$file.'">
			<code><i class="icon-file"></i>'.basename($file).'</code></a>
			</label>';
	}
	
	// Change the id of the form input field and create the hidden field with the original id
	// and with the value of the image path.  (string) foreces it to render.
	$hidden = (string) Former::hidden(UPLOAD_OLD.$id)->value($file);
	
	// Check for errors registered to the "real" form element
	$errors = Former::getErrors($id);
	
	// Make the file field.  We're setting a class of required rather than actually setting the field
	// to required so that Former doesn't tell the DOM to the required attribute.  We don't want the
	// browser to enforce requirement, we just want the icon to indicate that it is required.
	$file = Former::file(UPLOAD_REPLACE.$id, $label)->blockHelp($block_help);
	if ($is_required) $file = $file->class('required');
	if (!empty($errors)) $file = $file->state('error')->inlineHelp($errors);
	return '<div class="file-upload">'.$file.$hidden.'</div>';
	
});

/**
 * Render the UI that the JS expecting to render a datalist style autocomplete menu.
 * A belongs_to takes a key value pair from the server and when the user chooses
 * an option, stores the choice in a hidden input field.
 * 
 * - $id - The id/name of the input field
 * - $route - The GET route that will return data for the autocomplete.
 *   The response should be an array of key / value pairs where the key is
 *   what will get submitted and the value is the title that is displayed to users.
 * - $options - An associative array that supports:
 *     - label - The label for the field.  If undefined, uses the id
 *     - title - The title of the old value.  This would be used if $old is an int like a foreign_id.
 *     - create - A boolean, if true, allows the user to enter values not in autocomplete
 */
HTML::macro('belongs_to', function($id, $route, $options = array()) {
	
	// Start data array
	$data = array(
		'id' => $id,
		'route' => $route,
	);
	
	// Default options
	if (empty($options['label']))   $options['label'] = ucfirst($id);
	if (empty($options['title']))   $options['title'] = null;
	if (empty($options['create']))  $options['create'] = false;
	
	// Allow New isn't supported yet
	if ($options['create']) throw new Exception('allow_new is not supported yet');
	
	// Render the view
	$data = array_merge($data, $options);
	return render('decoy::shared.form.relationships._belongs_to', $data);
	
});
/**
 * Form an URL to a new page
 */
HTML::macro('new_route', function($route, $parent_id = null) {
	return empty($parent_id) ? route($route.'@new') : route($route.'@new_child', $parent_id);
});

/**
 * Form a URL to an edit page, populating route variables by extracting items from URL segments
 */
HTML::macro('edit_route', function($route, $is_many_to_many = false, $id = null) {
	$action = '@edit';	
	
	// If a many to many, make the route straight to the controller
	if ($is_many_to_many) return route($route.$action, $id);
	
	// Get all the ids from the route
	$uri = URI::current();
	$segments = explode('/', $uri);
	$ids = array();
	for ($i=2; $i < count($segments); $i += 2) {
		$ids[] = $segments[$i];
	}
	
	// If there are any ids, found in the URL, then this a link to a child
	if (count($ids)) $action .= '_child';
	
	// If an id was passed, add it to the end
	if ($id) $ids[] = $id;
	
	// Form the route using all the ids
	return route($route.$action, $ids);
	
});

/**
 * Make a control group that doesn't show an input.  Like where a field might have been rendered as
 * disabled, we're just showing the value as text.  Only makes sense on edit views, really.
 * $key - The key that the value is associated with in former
 */
HTML::macro('inputless_field', function($key, $label = null, $value = null) {
	
	// Get defaults
	if (empty($label)) $label = BKWLD\Utils\String::title_from_key($key);
	if (empty($value)) $value = Former::getValue($key);
	
	// Render the elemnent
	return '<div class="control-group inputless '.$key.'"><label for="'.$key.'" class="control-label">'.$label.'</label><div class="controls">'.$value.'</div></div>';
	
}); 

/**
 * This renders a date selection box
 */
HTML::macro('date', function($id, $label = null) {
	
	// Defaults
	if (empty($label)) $label = BKWLD\Utils\String::title_from_key($id);
	$value = date("m/d/Y");
	
	// Make the element
	$field = Former::text($id, $label)
		->class('date span2')
		->maxlength(10)
		->placeholder('mm/dd/yyyy')
		->value($value)
		->append('<i class="icon-calendar"></i>')
		->id(null); // We don't want to conflict on the id
		
	// If there is a value, we assume it's in MYSQL time format, so
	// make it human and force it
	if ($former_value = Former::getValue($id)) {
		$value = strtotime($former_value);
		$field = $field->forceValue(date("m/d/Y", $value));
	}
	
	// I must render this field before adding a new one
	$field = (string) $field;
	
	// Now, add a hidden field that will contain the value in the MySQL prefered
	// format and is updated via JS
	$value = date(BKWLD\Utils\Constants::MYSQL_DATE, strtotime($value));
	return $field.(Former::hidden($id)->id($id)->value($value)); // id not added by default
});


/**
 * This renders a CKEditor Implementation
 */
HTML::macro('ckeditor', function($id, $label = null) {
	
	// Defaults
	// if (empty($label)) $label = BKWLD\Utils\String::title_from_key($id);
	
	// Make the element
	$field = Former::textarea($id, $label)
		->class('span6')
		->value(Former::getValue($id))
		->id(null); // We don't want to conflict on the id
		
	// I must render this field before adding a new one
	$field = (string) $field."<script>
	CKEDITOR.replace( '".$id."', {
        enterMode : CKEDITOR.ENTER_BR,
		toolbar :
		[
		{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
		{ name: 'links', items : [ 'Link','Unlink','Anchor' ] },
			{ name: 'image', items : [ 'Image' ] },
			{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },

			{ name: 'source', items : [ 'Source' ] },
		],
    	filebrowserImageBrowseUrl: '/bundles/decoy/ckfinder/ckfinder.html?Type=Images',
    	filebrowserImageUploadUrl: '/bundles/decoy/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
	});
	</script>";
	
	// Now, add a hidden field that will contain the value in the MySQL prefered
	// format and is updated via JS
	return $field; // id not added by default
});