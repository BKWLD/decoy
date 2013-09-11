<?php namespace Bkwld\Decoy;

// Dependencies
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Library;
use Config;
use Croppa;
use Former;
use Request;
use View;

/**
 * These function like the Laravel `Html` view helpers.  This class is bound
 * to the App IoC container as "decoy".  Thus, Decoy::helperName() can be
 * used to invoke them from views.
 */
class Helpers {
	
	/**
	 * Generate title tags based on section content
	 */
	public function title() {
		
		// If no title has been set, try to figure it out based on
		// default breadcrumbs
		$title = View::yieldContent('title');
		if (empty($title)) $title = Breadcrumbs::title(Breadcrumbs::defaults());
		
		// Get the site name
		$site = Config::get('decoy::site_name');

		// Set the title
		return '<title>' . ($title ? "$title | $site" : $site) . '</title>';
	}

	/**
	 * Add the controller and action as CSS classes on the body tag
	 */
	public function bodyClass() {
		$path = Request::path();
		
		// Get the controller and action from the URL
		preg_match('#/([a-z-]+)(?:/\d+)?(?:/(create|edit))?$#i', $path, $matches);
		$controller = empty($matches[1]) ? 'login' : $matches[1];
		$action = empty($matches[2]) ? 'index' : $matches[2];
		return $controller.' '.$action;
	}

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
	public function renderListColumn($item, $column, $convert_dates) {
		
		// Date formats
		$date_formats = array(
			'date'     => FORMAT_DATE,
			'datetime' => FORMAT_DATETIME,
			'time'     => FORMAT_TIME,
		);
		
		// Convert the item to an array so I can test for values
		$test_row = $item->toArray();
		
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
		
	}

	/**
	 * Make an image upload field.  That is to say, one that displays a sample if an
	 * image has already been uploaded
	 */
	public function imageUpload($id = null, $label = null, $help = null, $crops = null) {
		
		// Defaults
		if ($id === null) $id = 'image';
		$block_help = '';
		$image = Former::getValue($id);
		if (!$label) $label = Library\Utils\String::titleFromKey($id);
		
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
				$block_help .= '<span class="'.$active.'">'.Library\Utils\String::titleFromKey($crop).'</span>';
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
			$block_help .= '<label for="'.$id.'-delete" class="checkbox image-delete">
				<input id="'.$id.'-delete" type="checkbox" name="'.$id.'" value="">Delete 
				<a href="'.$image.'"><code><i class="icon-file"></i>'.basename($image).'</code></a></label>';
		}
		
		// Change the id of the form input field and create the hidden field with the original id
		// and with the value of the image path.  (string) forces it to render.
		$hidden = (string) Former::hidden($id)->value($image);
		
		// Make the file field.  We're setting a class of required rather than actually setting the field
		// to required so that Former doesn't tell the DOM to the required attribute.  We don't want the
		// browser to enforce requirement, we just want the icon to indicate that it is required.
		$file = Former::file($id, $label)->accept('image')->blockHelp($block_help);
		if ($is_required) $file = $file->class('required')->setAttribute('required', null);
		
		// Check for errors registered to the "real" form element
		$errors = Former::getErrors($id);
		if (!empty($errors)) $file = $file->state('error')->inlineHelp($errors);
		
		// Assemble all the elements
		$file = (string) $file;
		return '<div class="image-upload" data-js-view="image-fullscreen">'.$hidden.$file.$crop_field.'</div>';
		
	}

	/**
	 * Make a file upload field.  It shows a download link for already uploaded files
	 */
	public function fileUpload($id = null, $label = null, $help = null) {
		
		// Defaults
		if ($id === null) $id = 'file';
		$block_help = '';
		$file = Former::getValue($id);
		if (!$label) $label = Library\Utils\String::titleFromKey($id);

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
			$block_help .= '<label for="'.$id.'-delete" class="checkbox file-delete">
				<input id="'.$id.'-delete" type="checkbox" name="'.$id.'" value="">Delete 
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
		$hidden = (string) Former::hidden($id)->value($file);
		
		// Check for errors registered to the "real" form element
		$errors = Former::getErrors($id);
		
		// Make the file field.  We're setting a class of required rather than actually setting the field
		// to required so that Former doesn't tell the DOM to the required attribute.  We don't want the
		// browser to enforce requirement, we just want the icon to indicate that it is required.
		$file = Former::file($id, $label)->blockHelp($block_help);
		if ($is_required) $file = $file->class('required')->setAttribute('required', null);
		if (!empty($errors)) $file = $file->state('error')->inlineHelp($errors);
		return '<div class="file-upload">'.$file.$hidden.'</div>';
		
	}

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
	public function belongsTo($id, $route, $options = array()) {
		
		// Start data array
		$data = array(
			'id' => $id,
			'route' => $route,
		);
		
		// Default options
		if (empty($options['label']))   $options['label'] = ucfirst(str_replace('_id', '', $id));
		if (empty($options['title']))   $options['title'] = null;
		if (empty($options['create']))  $options['create'] = false;
		
		// Allow New isn't supported yet
		if ($options['create']) throw new Exception('allow_new is not supported yet');
		
		// Render the view
		$data = array_merge($data, $options);
		return View::make('decoy::shared.form.relationships._belongs_to', $data);
		
	}

	/**
	 * Make a control group that doesn't show an input.  Like where a field might have been rendered as
	 * disabled, we're just showing the value as text.  Only makes sense on edit views, really.
	 * $key - The key that the value is associated with in former
	 */
	public function inputlessField($key, $label = null, $value = null) {
		
		// Get defaults
		if (empty($label)) $label = Library\Utils\String::titleFromKey($key);
		if (empty($value)) $value = Former::getValue($key);
		
		// Render the elemnent
		return '<div class="control-group inputless '.$key.'"><label for="'.$key.'" class="control-label">'.$label.'</label><div class="controls">'.$value.'</div></div>';
		
	}

	/**
	 * This renders a date selection box
	 */
	public function date($id, $label = null) {
		
		// Defaults
		if (empty($label)) $label = Library\Utils\String::titleFromKey($id);
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
			$value = $former_value;
			$field = $field->forceValue(date("m/d/Y", strtotime($value)));
		}
		
		// I must render this field before adding a new one
		$field = (string) $field;
		
		// Now, add a hidden field that will contain the value in the MySQL prefered
		// format and is updated via JS
		$value = date(Library\Utils\Constants::MYSQL_DATE, strtotime($value));
		return $field.(Former::hidden($id)->id($id)->forceValue($value)->class('date')); // id not added by default
	}

	/**
	 * This renders a time selection box
	 */
	public function time($id, $label = null) {
		
		// Defaults
		if (empty($label)) $label = Library\Utils\String::titleFromKey($id);
		$value = date('h:i A');
		
		// Make the time element.
		$field = Former::text($id, $label)
			->class('time span2')
			->maxlength(8)
			->placeholder('hh:mm')
			->value($value)
			->append('<i class="icon-time"></i>')
			->blockHelp('Time is in '.date('T'))
			->id(null); // We don't want to conflict on the id

		// If there is a value, we assume it's in MYSQL time format, so
		// make it human and force it
		if ($former_value = Former::getValue($id)) {
			$value = $former_value;
			$field = $field->forceValue(date('h:i A', strtotime($value)));
		}

		// I must render this field before adding a new one
		$field = (string) $field;
		
		// Now, add a hidden field that will contain the value in the MySQL prefered
		// format and is updated via JS
		$value = date(Library\Utils\Constants::MYSQL_TIME, strtotime($value));
		return $field.(Former::hidden($id)->id($id)->forceValue($value)->class('time')); // id not added by default
	}

	/**
	 * This renders a date time component.  This works by creating a date AND time one
	 * consecutively, then adding a final hidden field after for the concatenated
	 * date and time value.  JS will combine these tool fields into one element and will
	 * also make sure that datetime input field gets populated on value change.
	 */
	public function datetime($id, $label = null) {
		
		// Get the initial value
		$value = time();
		if ($former_value = Former::getValue($id)) {
			$value = strtotime($former_value);
		}
		
		// Convert to mysql time
		$value = date(Library\Utils\Constants::MYSQL_DATETIME, $value);
		
		// Add UI elements plus the hidden field that will contain the mysql formatted value
		return '<div class="datetime">'
			.$this->date($id, $label)
			.$this->time($id, $label)
			.(Former::hidden($id)->id($id)->forceValue($value)->class('datetime'))
			.'</div>';
	}
	
}