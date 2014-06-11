<?php namespace Bkwld\Decoy\Models;

// Dependencies
use Bkwld\Library;
use Bkwld\Library\Utils\File;
use Config;
use Exception;
use Input;
use Lang;
use Str;

class Fragment extends \Illuminate\Database\Eloquent\Model {
	
	// Fragments don't need timestamps
	public $timestamps = false;
	
	// Language files to ignore.  "admin" is in there because that is a convention
	// that I use to store block help lines.
	private static $ignore = array('pagination', 'reminders', 'validation', 'admin');
	
	// Use the key as the primary key
	public $primaryKey = 'key';
	
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
			$title = ucwords(Library\Utils\String::titleFromKey($title));
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
					$section = Library\Utils\String::titleFromKey(trim($section));
				} else $section = 'General';
				
				// Format the key
				$key = Library\Utils\String::titleFromKey($key);
				
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
				if (self::unchangedImage($input_name, self::value($title.'.'.$key))) {
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
	static private $pairs;
	static private $db_checked = false;
	public static function value($key) {
		
		// Get all pairs to reduce DB lookups
		if (!self::$db_checked) {
			self::$pairs = self::all();
			self::$db_checked = true;
			
			// Add untyped versions of pairs to the array so that items can be looked
			// up even if their type isn't included.  This is another peformance hit.
			foreach(self::$pairs as $pair) {
				if (Str::contains($pair->key,',')) {
					$clone = $pair->replicate();
					$clone->key = preg_replace('#,.*$#', '', $pair->key);
					self::$pairs->add($clone);
				}
			}			
		}
		
		// Check if the key is in the db
		if (self::$pairs->contains($key)) return self::$pairs->find($key)->value;
		
		// Else return the value from the config file
		else if (Lang::has($key)) return self::massageLangValue(Lang::get($key));
		
		// Check for types.  This just exists to make the Decoy::frag() helper
		// easier to use.  It does have a performance impact, though.
		else {
			foreach(array('textarea', 'wysiwyg', 'image', 'file') as $type) {
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
		if (Str::is('/uploads/*', $value)) $value = 'All fragment images must be stored in the img directory';
		if (!Str::is('/img/*', $value)) return $value;
		
		// Check if the image already exists in the uploads directory
		$uploads = File::publicPath(Config::get('decoy::upload_dir'));
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
		if (self::unchangedImage($input_name, $val)) return true;
		
		// Do a string comparison after simplifying all whitespace
		return self::clean(Lang::get(self::confkey($input_name))) === self::clean($val);
	}
	
	/**
	 * Test if an field is for an image and if it's unchanged
	 */
	public static function unchangedImage($input_name, $val) {
		return Str::endsWith($input_name, array(',image', ',file')) 
			&& Str::is('/uploads/fragments/*', $val);
	}
	
	/**
	 * Do CRUD operations on a fragment
	 */
	public static function store($input_name, $value) {
		
		// Clean input
		$key = self::confKey($input_name);
		
		// Save out a file if there was one
		if (Input::hasFile($input_name)) {
			$value = File::publicPath(File::organizeUploadedFile(Input::file($input_name), Config::get('decoy::upload_dir')));
		}
				
		// See if a row already exists
		if ($row = self::find($key)) {
			
			// Update the row if there is a value that is different
			// than one in a config file
			if ($value && !self::unchanged($input_name, $value)) {
				return $row->update(array('value' => $value));
				
			// Delete the row
			} else return $row->delete();
		
		// The row didn't exist, so create it
		} else if ($value && !self::unchanged($input_name, $value)) {
			return self::create(array('key' => $key, 'value' => $value));
		}
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