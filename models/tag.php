<?php namespace Decoy;

// Dependencies
use Laravel\Str;
use Laravel\Database as DB;

/**
 * A tag-like class is one that has a single field that the user edits.
 * The standard schema looks like this:
 * 
 * CREATE TABLE `tags` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `type` varchar(200) NOT NULL,
 *   `value` varchar(200) NOT NULL,
 *   `slug` varchar(200) NOT NULL,
 *   `created_at` datetime NOT NULL,
 *   `updated_at` datetime NOT NULL,
 *   PRIMARY KEY (`id`),
 *   KEY `tags_value_type_index` (`value`,`type`),
 *   KEY `tags_slug_type_index` (`slug`,`type`),
 *   KEY `tags_type_value_index` (`type`,`value`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=latin1;
 * 
 * The type field lets you have different types of tags for a project but
 * have them all stored in the same table.  Each type would be a different
 * model class that inherits from this class.
 * 
 * This abstract class would be inherrited by models in the application.
 * Not only do you get methods for free but the tagging interface in the
 * UI is enabled by checking that the application model extends from this
 * class.  The UI lets you generate new tags in the same many to many
 * interface you use to attach records.
 */
abstract class Tag extends Base_Model {
	
	// Because different tag types extend this, this tells Laravel what the actual table name is
	public static $table = 'tags';
	
	// Validation rules
	public static $rules = array(
		'type' => 'required',
		'value' => 'required',
		'slug' => 'required|unique:tags',
	);
	
	// Where to get the title from
	static public $TITLE_COLUMN = 'value';
	
	// Create a new instance if one doesn't exist.  If one does exist,
	// return that instance
	static public function find_or_create($value, $type = null) {
		
		// Figure out the type based on called model's name
		if (!$type) $type = self::type();
		
		// The tag exists
		$slug = Str::slug($value);
		if ($tag = self::where('slug', '=', $slug)->where('type', '=', $type)->first()) return $tag;
		
		// The tag does not exist
		return static::create(array(
			'type' => $type,
			'value' => $value,
			'slug' => $slug,
		));
		
	}
	
	// Override the count function to filter by the type
	public static function count() {
		return self::typed()->count();
	}
	
	// Override the ordered function to add a filter by type
	public static function ordered() {
		return self::typed()->order_by('value', 'asc');
	}
	
	// Get a random tag
	public static function randomize() {
		return self::typed()->order_by(DB::raw('RAND()'));
	}
	
	// Get all tags of the type calcualted by the model's name
	public static function typed() {
		return self::where('type', '=', self::type());
	}
	
	// Figure out the tag type based on the called model's name
	public static function type() {
		return strtolower(get_called_class());
	}
	
}