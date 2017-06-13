<?php namespace Bkwld\Decoy\Models;

// Dependencies
use App;
use Bkwld\Library;
use Bkwld\Library\Utils\File;
use Config;
use Exception;
use Input;
use Lang;
use Str;

class Fragment extends Base {

	// Incorporate the encodable trait because video encoders are acceptable
	use Traits\Encodable;
	
	// Fragments don't need timestamps
	public $timestamps = false;
	
	// Language files to ignore.  "admin" is in there because that is a convention
	// that I use to store block help lines.
	private static $ignore = array('pagination', 'reminders', 'validation', 'admin');
	
	// Allow mass assignment
	protected $fillable = array('key', 'value');
	
	/**
	 * Organize all the key value pairs from all language files
	 */
	public static function organized() {
		
		// Loop though all lanaguage files
		$output = array();
		foreach(self::titles() as $title) {
			
			// Format title and add as a node
			$title_key = $title; // Preserve for use with the full_key
			$title = ucwords(Library\Utils\Text::titleFromKey($title));
			$output[$title] = array();
			
			// Break the keys for all the pairs up by section
			foreach(Lang::get($title_key) as $key => $val) {
				
				// Make the key that will be used as the id and name in the form.  
				$full_key = self::inputName($title_key.'.'.$key);
				
				// Figure out the field type
				$parts = explode(',', $key);
				$key = trim($parts[0]);
				$type = isset($parts[1]) ? trim($parts[1]) : 'text';				
				
				// Allow the section to be undefined or have a bunch of periods
				if (substr_count($key, '.') === 1) {
					list($section, $key) = explode('.', $key);
					$section = Library\Utils\Text::titleFromKey(trim($section));
				} else $section = 'General';
				
				// Format the key
				$key = Library\Utils\Text::titleFromKey($key);
				
				// Add the pair to the list
				if (!isset($output[$title][$section])) $output[$title][$section] = array();
				$output[$title][$section][$key] = (object) array('type' => $type, 'value' => $val, 'key' => $full_key);
				
			}
		}
		
		// Return compiled output
		return $output;
	}
	
	/**
	 * Set form rules
	 */
	public static function rules() {
		$rules = array();
		foreach(self::titles() as $title) {
			foreach(Lang::get($title) as $key => $val) {
				$input_name = self::inputName($title.'.'.$key);
				
				// Make all files that are unchanged required so that there is no
				// delete checkbox shown in the form
				if (self::unchangedFile($input_name, self::value($title.'.'.$key))) {
					$rules[$input_name] = 'required';
				}
				
			}
		}
		return $rules;
	}
	
	/**
	 * Get all language fields and then merge any overrides from the DB in. 
	 */
	public static function values() {
		
		// Loop though all lanaguage files
		$output = array();
		foreach(self::titles() as $title) {
			foreach(Lang::get($title) as $key => $val) {
					
				// Assemble the key like it's stored in the db
				$key = $title.'.'.$key; // Append file name
				
				// Call the regular lookup function
				$output[self::inputName($key)] = self::value($key);
					
			}
		}
		
		// Return compiled output
		return $output;
	}
	
	/**
	 * Get a value given a key from the DB but falling back to a language file
	 */
	static private $pairs; // Array
	static private $db_checked = false;
	public static function value($key) {
		
		// Get all pairs to reduce DB lookups
		if (!self::$db_checked) {
			self::$pairs = self::lists('value', 'key');
			self::$db_checked = true;
			
			// Add untyped versions of pairs to the array so that items can be looked
			// up even if their type isn't included.  This is another peformance hit.
			foreach(self::$pairs as $pair_key => $pair_val) {
				if (Str::contains($pair_key, ',')) {
					$pair_key = preg_replace('#,.*$#', '', $pair_key);
					self::$pairs[$pair_key] = $pair_val;
				}
			}			
		}
		
		// Check if the key is in the db
		if (array_key_exists($key, self::$pairs)) {

			// If the key is for a video encoder, add the rendered video tag to the array.
			// First get the key without the type suffix and see if there is a video-encoder row
			// in the database
			$base_key = ($i = strpos($key, ',')) ? substr($key, 0, $i) : $key;
			if (!\Decoy::handling()
				&& array_key_exists($base_key.',video-encoder', self::$pairs)) {

				// See if we've already generate the tag
				$tag_key = $base_key.',video-tag';
				if (array_key_exists($tag_key, self::$pairs)) return self::$pairs[$tag_key];

				// Else, generate the tag and return it.
				else return (self::$pairs[$tag_key] = self::where('key', '=', $base_key.',video-encoder')
					->firstOrFail()->encoding('value')->tag);
			}

			// Return the value for the key
			return self::$pairs[$key];
		}
		
		// Else return the value from the config file
		else if (Lang::has($key)) return self::massageLangValue(Lang::get($key));
		
		// Check for types.  This just exists to make the Decoy::frag() helper
		// easier to use.  It does have a performance impact, though.
		else {
			foreach(array('textarea', 'wysiwyg', 'image', 'file', 'belongs_to', 'video-encoder') as $type) {
				if (Lang::has($key.','.$type)) {
					if ($type == 'image') return self::massageLangValue(Lang::get($key.','.$type));
					return Lang::get($key.','.$type);
				}
			}
			
			// It couldn't be found
			throw new Exception('This fragment key has not been added to a language file: '.$key);
		}
		
	}
	
	/**
	 * Massage fragment values
	 */
	private static function massageLangValue($value) {
		
		// Copy images
		$value = self::copyImages($value);
		
		// Get rid of all tabs
		$value = str_replace("\t", "", $value);
		
		// Done
		return $value;
	}
	
	/**
	 * Check if the value looks like an image.  If it does, copy it to the uploads dir
	 * so Croppa can work on it and return the modified path
	 */
	private static function copyImages($value) {
		
		// All images must live in the /img (relative) directory.  I'm not throwing an exception
		// here because Laravel's view exception handler doesn't display the message.
		if (Str::is('/uploads/*', $value)) $value = 'All fragment images must be stored in the public/img directory';
		if (!Str::is('/img/*', $value)) return $value;
		
		// Check if the image already exists in the uploads directory
		$uploads = File::publicPath(Config::get('decoy::core.upload_dir'));
		$dst = str_replace('/img/', $uploads.'/fragments/', $value);
		$dst_full_path = public_path().$dst;
		if (file_exists($dst_full_path)) return $dst;
		
		// Copy it to the uploads dir
		$dir = dirname($dst_full_path);
		if (!file_exists($dir)) mkdir($dir, 0775, true);
		copy(public_path().$value, $dst_full_path);
		return $dst;
	}
	
	/**
	 * Check if a field in the input is unchanged from what is in the language file
	 */
	public static function unchanged($input_name, $val) {
		
		// If a file was uploaded, it's new
		if (Input::hasFile($input_name)) return false;
		
		// If no file was posted but we're getting a value, then it must be unchanged
		if (self::unchangedFile($input_name, $val)) return true;
		
		// Do a string comparison after simplifying all whitespace
		return self::clean(Lang::get(self::confkey($input_name))) === self::clean($val);
	}
	
	/**
	 * Test if an field is for an file and if it's unchanged
	 */
	public static function unchangedFile($input_name, $val) {
		return static::isFile($input_name) && Str::is('/uploads/fragments/*', $val);
	}

	/**
	 * Test if an input name represents a file
	 *
	 * @param string $input_name 
	 * @return boolean 
	 */
	public static function isFile($input_name) {
		return Str::endsWith($input_name, array(',image', ',file', ',video-encoder'));
	}
	
	/**
	 * Do CRUD operations on a fragment
	 */
	public static function store($input_name, $value) {
		
		// Clean input
		$key = self::confKey($input_name);
		
		// Save out a file if there was one
		if ($has_file = Input::hasFile($input_name)) {
			$value = File::publicPath(File::organizeUploadedFile(Input::file($input_name), Config::get('decoy::core.upload_dir')));

			// Remove it from the input so any sub models (like Encoding) don't
			// try and handle it
			App::make('request')->files->remove($input_name);
		}

		// See if a row already exists
		if ($row = self::where('key', '=', $key)->first()) {

			// Files are managed manually here, don't do the normal Decoy Base Model
			// file handling.  It doesn't work here because it expects the Input to
			// contain fields for a single model instance.  Whereas frags manages many
			// model records at once.
			$row->auto_manage_files = false;
			
			// Update the row if there is a value that is different
			// than one in a config file
			if ($value && !self::unchanged($input_name, $value)) {
				$row->update(array('value' => $value));
				
			// Delete the row.  This will also delete encoding rows thanks to the 
			// encodable trait and this class extending from the Decoy base model.
			} else $row->delete();
		
		// The row didn't exist, so create it
		} else if ($value && !self::unchanged($input_name, $value)) {
			$row = self::create(array('key' => $key, 'value' => $value));
		}

	}

	/**
	 * When updating a row, delete old files
	 *
	 * @return void 
	 */
	public function onUpdating() {
		parent::onUpdating();
		if (static::isFile($this->key) && $this->isDirty('value')) {
			$this->deleteFile($this->getOriginal('value'));
		}
	}

	/**
	 * When saving a row, trigger an encode if necessary
	 *
	 * @return void 
	 */
	public function onSaving() {
		parent::onSaving();
		if (static::isFile($this->key) 
			&& $this->isDirty('value') 
			&& Str::contains($this->key, ',video-encoder')) {
			$this->encodeOnSave('value');
		}
	}

	/**
	 * When deleting a row, delete source file
	 *
	 * @return void 
	 */
	public function onDeleted() {
		if (Str::contains($this->key, ',video-encoder')) $this->deleteEncodings();
		if (static::isFile($this->key)) $this->deleteFile($this->value);
	}
	
	/**
	 * Return the titles from all applicable language files
	 */
	private static function titles() {
		$output = array();
		foreach(glob(app_path().'/lang/en/*.php') as $file) {
			$title = basename($file, '.php');
			if (in_array($title, self::$ignore)) continue;
			if (!Lang::has($title)) continue;
			$output[] = $title;
		}
		return $output;
	}
	
	/**
	 * Convert a key to an input friendly name. Periods are converted
	 * to | because the period isn't allowed in input names in PHP.
	 * See: http://stackoverflow.com/a/68742/59160
	 */
	private static function inputName($key) {
		return str_replace('.', '|', $key);
	}
	
	/**
	 * Convert an input name back into a dot delimited format used by
	 * the language files
	 */
	private static function confKey($input_name) {
		return str_replace('|', '.', trim($input_name));
	}
	
	/**
	 * Remove unnecessary whitespace for comparing CKeditor formatted stuff to what
	 * may be in language files
	 */
	private static function clean($string) {
		$string = trim($string);
		$string = preg_replace('#>\s+<#', '><', $string); // http://stackoverflow.com/a/5362207/59160
		$string = preg_replace('#\s{2,}#', ' ', $string);
		$string = html_entity_decode($string); // Former will decode entities before output forms
		return $string;
	}
	
}