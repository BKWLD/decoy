<?php namespace Bkwld\Decoy\Models;
/*
 * Content is essentially key / value pairs thae canbe organized.
 * FYI, I had to go with 'slug' instead of 'key' because that was a
 * reserved term in eloquent.
 * 
 * Add new content pairs to the table table using migrations.  For instance:
 * 
 * 	public function up() {
 * 		
 * 		// Load the bundle
 * 		Bundle::start('decoy');
 * 
 * 		// Create content options
 * 		Content::add('home.hero_marquee', 'Hero Marquee Vimeo URL', 'Home', 'text');
 * 		Content::add('our_agency.who_we_are', 'Who We Are', 'Our Agency', 'wysiwyg');
 * 		Content::add('our_agency.our_offices', 'Our Offices', 'Our Agency');
 * 		
 * 	}
 */
class Content extends Base {
	static public $table = 'content';
	static public $timestamps = false;
	
	// Validation
	public static $rules = array(
		'type' => 'in:text,textarea,wysiwyg,image,file',
		'label' => 'required',
	);
		
	// Utility method to make adding content pairs easier
	public static function add($slug, $label, $category=null, $type='textarea') {
		
		// There can be no periods in slugs (cause there can't be form fields with 
		// dots in them: http://stackoverflow.com/a/68742/59160)
		$slug = str_replace('-', '.', $slug);
		
		// Create an input array
		$input = array(
			'slug' => $slug,
			'label' => $label,
			'category' => $category,
			'type' => $type,
		);
		
		// Validate
		$validation = Validator::make($input, self::$rules);
		if ($validation->fails()) {
			throw new Exception('Validation failed on Content::add(). See: '.print_r($validation->errors, true));
		}
		
		// Insert
		self::create($input);
	}
	
	// Organize all the content options by category
	public static function organized() {
		
		// Get all the content fields
		$pairs = self::all();
		
		// Loop through and orgnanize into categories
		$organized = array();
		foreach($pairs as $pair) {
			$category = empty($pair->category) ? 'General' : $pair->category;
			$organized[$category][] = (object) $pair->toArray();
		}	
		return $organized;
	}
	
	// Update a content pair if the slug exists.  But don't fatal error or anything
	// if it doesn't.
	public static function update($slug, $value) {
		
		// Check if it exists
		$pair = self::where('slug', '=', $slug)->first();
		if (empty($pair)) return;
		
		// If a file was passed, save it out and use it's path as the file.  Or,
		// if no new image was passed, do nothing.
		if (in_array($pair->type, array('image', 'file'))) {
			
			// Handle delete checkboxes
			if (Input::get(UPLOAD_DELETE.$slug)) {
				if ($pair->value) Croppa::delete($pair->value);
				$pair->value = '';
			}
			
			// Delete the old file and save the new
			if (Input::has_file($slug)) {
				if ($pair->value) Croppa::delete($pair->value);
				$value = $pair->saveFile($slug);
				
			// Or there is no new file, so use the old value
			} else $value = $pair->value;
		}
		
		// Save the value to the db
		$pair->value = $value;
		$pair->save();
	}
	
	// Get a value from the db, given the slug. In doing this, fetch all the pairs
	// at once and cache them to reduce future DB requests
	private static $content;
	public static function value($slug) {
		
		// If content is defined, pull the slug from there
		if (is_array(self::$content)) {
			if (!array_key_exists($slug, self::$content)) {
				throw new Exception('This content slug could not be found: '.$slug);
			}
			return self::$content[$slug];
		}
		
		// Get all rows from the db
		$results = self::select(array('slug', 'value'))->get();
		
		// Loop through results and make a hash out of them
		self::$content = array();
		foreach($results as $pair) {
			self::$content[$pair->slug] = $pair->value;
		}
		
		// Call self again to return the one requested
		return self::value($slug);
		
	}
} 