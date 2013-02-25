<?php namespace Decoy;

// Imports
use BKWLD\Utils\File;
use BKWLD\Utils\Collection;
use Laravel\Request;
use Laravel\Database\Eloquent\Model as Eloquent;
use Laravel\Database as DB;
use Laravel\Input;
use Laravel\Config;
use Laravel\Event;
use Laravel\Log;
use Laravel\Bundle;
use Laravel\Str;
use Croppa;

abstract class Base_Model extends Eloquent {
	
	//---------------------------------------------------------------------------
	// Overrideable properties
	//---------------------------------------------------------------------------
	
	// Auto populate timestamps
	static public $timestamps = true;
	
	// This should be overridden by Models to store the array of their 
	// Laravel validation rules
	static public $rules = array();
	
	// This is designed to be overridden to store the DB column name that
	// should be used as the source for titles.  Used in the title() function
	// and in autocompletes.
	static public $TITLE_COLUMN;
	
	// This is should be overriden like so to specify crops that the image cropping
	// widget should make UI for
	// array('image' => array('marquee' => '4:3', 'feature'))
	static public $CROPS = array();
	
	//---------------------------------------------------------------------------
	// Model event callbacks
	//---------------------------------------------------------------------------
	// Setup listeners for all of Laravel's built in events that fire our no-op
	// callbacks.
	// 
	// These are defined by overriding the methods that fire them instead of in the
	// constructor so that ALL of instances of a model don't start listening to these
	// events.  For instance, if an instance was created to do some operation without
	// first getting hydrated with data, it doesn't need to handle a save event
	
	// Override events that are happening before saves.  Note these will likely be
	// triggered more often than you'd like, described above
	public function __construct($attributes = array(), $exists = false) {
		parent::__construct($attributes, $exists);
		
		// Add Decoy events
		$events = array('validating', 'validated');
		foreach($events as $event) {
			Event::listen('decoy.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
		
	}
	
	// Override the events that happen on save
	public function save() {
		
		// Standard laravel model events
		$events = array('saving', 'updated', 'created', 'saved');
		foreach($events as $event) {
			Event::listen('eloquent.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
		
		// Add additional pre-events
		Event::listen('eloquent.saving: '.get_class($this), function($self) {
			if ($self->exists) $self->on_updating();
			else $self->on_creating();
		});
		return parent::save();
		
		// Add Decoy events
		$events = array('attaching', 'attached', 'removing', 'removed');
		foreach($events as $event) {
			Event::listen('decoy.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
	}
	
	// Override the events that happen on delete
	public function delete() {
		$events = array('deleting', 'deleted');
		foreach($events as $event) {
			Event::listen('eloquent.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
		return parent::delete();
	}
	
	// No-op callbacks.  They all get passed a reference to the object that fired
	// the event.  They have to be defined as public because they are invoked externally, 
	// from Laravel's event system.
	public function on_saving() {}
	public function on_saved() {}
	public function on_validating($input) {}
	public function on_validated($input) {}
	public function on_creating() {}
	public function on_created() {}
	public function on_updating() {}
	public function on_updated() {}
	public function on_deleting() {}
	public function on_deleted() {}
	public function on_attaching() {}
	public function on_attached() {}
	public function on_removing() {}
	public function on_removed() {}
	
		
	//---------------------------------------------------------------------------
	// Overrideable methods
	//---------------------------------------------------------------------------
	
	// Return the title for the row for the purpose of displaying
	// in admin list views and breadcrumbs.  It looks for columns
	// that are named like common things that would be titles
	public function title() {
		$title = '';
		
		// Add a thumbnail to the title if there is an "image" field
		if (method_exists($this, 'image') && $this->image()) $title .= '<img src="'.Croppa::url($this->image(), 40, 40).'"/> ';
		elseif (!method_exists($this, 'image') && !empty($this->image)) $title .= '<img src="'.Croppa::url($this->image, 40, 40).'"/> ';
		
		// Convert to an array so I can test for the presence of values.
		// As an object, it would throw exceptions
		$row = $this->to_array();
		if (!empty(static::$TITLE_COLUMN)) $title .=  $row[static::$TITLE_COLUMN];
		else if (isset($row['name'])) $title .=  $row['name']; // Name before title to cover the case of people with job titles
		else if (isset($row['title'])) $title .= $row['title'];
		else if (Request::route()->controller_action == 'edit')  $title .= 'Edit';
		
		// Return the finished title
		return $title;

	}
	
	// Save out an image or file given the field name.  They are saved
	// to the directory specified in the bundle config
	static public function save_image($input_name = 'image') { return self::save_file($input_name); }
	static public function save_file($input_name = 'file') {
		$path = File::organize_uploaded_file(Input::file($input_name), Config::get('decoy::decoy.upload_dir'));
		$path = File::public_path($path);
		return $path;
	}
	
	// Many models will override this to create custom methods for getting
	// a list of rows
	static public function ordered() {
		return static::order_by(self::table_name().'.created_at', 'desc');
	}
	
	// Get an ordered list of only rows that are marked as visible
	static public function ordered_and_visible() {
		return static::ordered()->where('visible', '=', '1');
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	// Randomize the results in the DB.  This shouldn't be used for large datasets
	// cause it's not very performant
	static public function randomize() {
		return static::order_by(DB::raw('RAND()'));
	}
	
	// Find by the slug.  Like "find()" but use the slug column instead
	static public function find_slug($slug) {
		return static::where(self::table_name().'.slug', '=', $slug)->first();
	}
	
	// Figure out the current table name but allow it to be called statically
	static protected function table_name() {
		$model = get_called_class();
		$model = new $model;
		return $model->table();
	}
	
	// The pivot_id may be accessible at $this->pivot->id if the result was fetched
	// through a relationship OR it may be named pivot_id out of convention (something
	// currently done in Decoy_Base_Controller->get_index_child()).  This function
	// checks for either
	public function pivot_id() {
		if (!empty($this->pivot->id)) return $this->pivot->id;
		else if (!empty($this->pivot_id)) return $this->pivot_id;
		else return null;
	}
	
	// Form a croppa URL, taking advantage of being able to set more columns null.  Also,
	// provides an easier way to inform the source crops
	public function croppa($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		
		// Check if the image field has crops
		if ($crop_style && !array_key_exists($field, static::$CROPS)) {
			throw new \Exception("A crop style was passed for $field but no crops are defined for that field.");
		}
		
		// Check if the crop style is valid
		if ($crop_style && !Collection::key_or_val_exists($crop_style, static::$CROPS[$field])) {
			throw new \Exception("Crop style '$crop_style' is not defined for the field: $field");
		}
		
		// Default crop style is 'default'
		if (!$crop_style && !empty(static::$CROPS[$field]) && Collection::key_or_val_exists('default', static::$CROPS[$field])) {
			$crop_style = 'default';
		}
		
		// Get the image src path
		if (method_exists($this, $field)) $src = call_user_func(array($this, $field));
		else $src = $this->$field;
		if (empty($src)) return;
		
		// If there is a crop value, add it to the options
		if ($crop_style) {
			$crops = json_decode($this->{$field.'_crops'});
			
			// Check if a crop style was set in the admin for this crop style
			if (!empty($crops->$crop_style)) {
				if (!is_array($options)) $options = array();
				
				// Add the trim instructions to the croppa options
				$options = array_merge($options, array(
					'trim_perc' => array(
						round($crops->$crop_style->x1,4),
						round($crops->$crop_style->y1,4),
						round($crops->$crop_style->x2,4),
						round($crops->$crop_style->y2,4),
					),
				));
			}
		}
		
		// Return the Croppa URL
		return Croppa::url($src, $width, $height, $options);
		
	}
	
	// Get the admin controller name-path
	static public function admin_controller() {
		return Bundle::option('decoy', 'handles').'.'.strtolower(Str::plural(get_called_class()));
	}
	
}