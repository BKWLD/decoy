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
	
	// Language files to ignore
	private static $ignore = array('pagination', 'reminders', 'validation');
	
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
			$title = Library\Utils\String::titleFromKey($title);
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
		}
		
		// Check if the key is in the db
		$key_without_type = preg_replace('#,.*$#', '', $key);
		if (self::$pairs->contains($key)) return self::$pairs->find($key)->value;
		
		// Else return the value from the config file
		else if (Lang::has($key)) return Lang::get($key);
		
		// Check for types.  This just exists to make the Decoy::frag() helper
		// easier to use.  It does have a performance impact, though.
		else {
			foreach(array('texarea', 'wysiwyg', 'image', 'file') as $type) {
				if (Lang::has($key.','.$type)) return Lang::get($key.','.$type);
			}
			
			// It couldn't be found
			throw new Exception('This fragment key has not been added to a language file: '.$key);
		}
		
	}
	
	/**
	 * Check if a field in the input is unchanged from what is in the language file
	 */
	public static function unchanged($input_name, $val) {
		if (Input::hasFile($input_name)) return false; // If a file was uploaded, it's new
		return Lang::get(self::confkey($input_name)) === $val;
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
			
			// Update the row
			if ($value) return $row->update(array('value' => $value));
				
			// Delete the row
			else return $row->delete();
		
		// The row didn't exist, so create it
		} else if ($value) {
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
	
}