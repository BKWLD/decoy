<?php

// Imports
use BKWLD\Utils\File;

abstract class Decoy_Base_Model extends Eloquent {
	
	// Auto populate timestamps
	static public $timestamps = true;
	
	// This should be overridden by Models to store the array of their 
	// Laravel validation rules
	static public $rules = array();
	
	// This is designed to be overridden to store the DB column name that
	// should be used as the source for titles.  Used in the title() function
	// and in autocompletes.
	static public $TITLE_COLUMN;
	
	// Return the title for the row for the purpose of displaying
	// in admin list views and breadcrumbs.  It looks for columns
	// that are named like common things that would be titles
	public function title() {
		$title = '';
		
		// Add a thumbnail to the title if there is an "image" field
		if (method_exists($this, 'image') && $this->image()) $title .= '<img src="'.Croppa::url($this->image(), 40, 40).'"/> ';
		elseif (isset($this->image)) $title .= '<img src="'.Croppa::url($this->image, 40, 40).'"/> ';
		
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
	static public function save_image($input_name = 'image') { self::save_file($input_name); }
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
	
}