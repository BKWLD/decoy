<?php namespace Bkwld\Decoy\Models;

// Dependencies
use Bkwld\Library;
use Exception;
use Lang;

class Fragment extends Base {
	
	// Fragments don't need timestamps
	public $timestamps = false;
	
	// Language files to ignore
	private static $ignore = array('pagination', 'reminders', 'validation');
	
	// Use the key as the primary key
	public $primaryKey = 'key';
	
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
			foreach(Lang::get($title) as $key => $val) {
				$full_key = $title_key.'.'.$key;
				
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
				$output[$key] = self::value($key);
					
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
		if (self::$pairs->contains($key)) return self::$pairs->find($key)->pluck('value');
		
		// Else return the value from the config file
		else if (Lang::has($key)) return Lang::get($key);
		
		// Else it's a bad key
		else throw new Exception('This fragment key has not been added to a language file: '.$key);
		
	}
	
	/**
	 * Return the titles from all applicable language files
	 */
	private static function titles() {
		$output = array();
		foreach(glob(app_path().'/lang/en/*.php') as $file) {
			$title = basename($file, '.php');
			if (in_array($title, self::$ignore)) continue;
			$output[] = $title;
		}
		return $output;
	}
	
}