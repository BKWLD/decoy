<?php namespace Bkwld\Decoy\Models;

// Imports
use App;
use Bkwld\Library\Utils\Collection;
use Bkwld\Decoy\Input\ManyToManyChecklist;
use Config;
use Croppa;
use DB;
use Decoy;
use Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\Validator;
use Input;
use Log;
use Request;
use Session;
use Str;

abstract class Base extends Eloquent {
	
	//---------------------------------------------------------------------------
	// Overrideable properties
	//---------------------------------------------------------------------------
	
	/**
	 * This should be overridden by Models to store the array of their 
	 * Laravel validation rules
	 *
	 * @var array
	 */
	static public $rules = [];
	
	/**
	 * This is designed to be overridden to store the DB column name that
	 * should be used as the source for titles.  Used in the title() function
	 * and in autocompletes.
	 *
	 * @var string
	 */
	static public $title_column;

	/**
	 * This is should be overriden like so to specify crops that the image cropping
	 * widget should make UI for
	 * array('image' => array('marquee' => '4:3', 'feature'))
	 *
	 * @var array
	 */
	static public $crops = [];

	/**
	 * Should this model be localizable in the admin.  If not undefined, will override
	 * the site config "auto_localize_root_models"
	 *
	 * @var boolean
	 */
	static public $localizable;
	
	/**
	 * Constructor registers events and configures mass assignment
	 */
	public function __construct(array $attributes = array()) {
		
		// Blacklist special columns that aren't intended for the DB
		$this->guarded = array_merge($this->guarded, array(
			'_token', // Part of CSRF protection
			'_save', // The submit buttons, tells us which submit button they clicked
			'parent_controller', // Backbone.js sends this with sort updates
			'parent_id', // Backbone.js may also send this with sort
			'select-row', // This is the name of the checkboxes used for bulk delete
		));
		
		// Remove any hidden/visible settings that may have been set on models if
		// the user is in the admin
		if (Decoy::handling()) $this->visible = $this->hidden = array();

		// Continue Laravel construction
		parent::__construct($attributes);
	}

	// Disable all mutatators while in Admin by returning that no mutators exist
	public function hasGetMutator($key) { 
		return Decoy::handling() && array_key_exists($key, $this->attributes) ? false : parent::hasGetMutator($key);
	}
	public function hasSetMutator($key) { 
		return Decoy::handling() && array_key_exists($key, $this->attributes) ? false : parent::hasSetMutator($key);
	}

	/**
	 * No-Op callbacks invoked by Observers\ModelCallbacks.  These allow quick handling
	 * of model event states.
	 *
	 * @return void|false 
	 */
	public function onSaving() {}
	public function onSaved() {}
	public function onValidating(Validator $validation) {}
	public function onValidated(Validator $validation) {}
	public function onCreating() {}
	public function onCreated() {}
	public function onUpdating() {}
	public function onUpdated() {}
	public function onDeleting() {}
	public function onDeleted() {}
	public function onAttaching($parent) {}
	public function onAttached($parent) {}
	public function onRemoving($ids) {}
	public function onRemoved($ids) {}
		
	//---------------------------------------------------------------------------
	// Accessors
	//---------------------------------------------------------------------------
	
	/**
	 * Return the title for the row for the purpose of displaying
	 * in admin list views and breadcrumbs.  It looks for columns
	 * that are named like common things that would be titles
	 *
	 * @return string 
	 */
	public function title() {
		return $this->croppaTag(40, 40).$this->titleText();
	}

	/**
	 * Deduce the source for the title of the model and return that title
	 * 
	 * @return string 
	 */
	public function titleText() {

		// Convert to an array so I can test for the presence of values.
		// As an object, it would throw exceptions
		$row = $this->getAttributes();

		// Deduce and return
		if (!empty(static::$title_column) && !empty($row[static::$title_column])) return $row[static::$title_column];
		else if (isset($row['name'])) return $row['name']; // Name before title to cover the case of people with job titles
		else if (isset($row['title'])) return $row['title'];
		else return 'Untitled';
	}

	/**
	 * A no-op that should return the URI (an absolute path or a fulL URL) to the record
	 *
	 * @return string 
	 */
	public function getUriAttribute() { }

	/**
	 * Get all file fields by looking at Upchuck config and validation rules
	 *
	 * @return array The keys of all the attributes that store file references
	 */
	public function getFileAttributesAttribute() {

		// Get all the file validation rule keys
		$attributes = array_keys(array_filter(static::$rules, function($rules) {
			return preg_match('#file|image|mimes|video#i', $rules);
		}));

		// Get all the model attributes from upchuck
		if (in_array('Bkwld\Upchuck\SupportsUploads', class_uses($this))) {
			$attributes = array_unique(array_merge($attributes, 
				array_values($this->getUploadMap())));
		}

		// Return array of attributes
		return $attributes;
	}

	//---------------------------------------------------------------------------
	// Scopes
	//---------------------------------------------------------------------------
	
	/**
	 * Default ordering by descending time, designed to be overridden
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeOrdered($query) {
		return $query->orderBy($this->getTable().'.created_at', 'desc');
	}
	
	/**
	 * Get visible items
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeVisible($query) {
		return $query->where($this->getTable().'.visible', '1');
	}
	
	/**
	 * Get all visible items by the default order
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeOrderedAndVisible($query) {
		return $query->ordered()->visible();
	}

	/**
	 * Order a table that has a position value
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopePositioned($query) {
		return $query->orderBy($this->getTable().'.position', 'asc')
			->orderBy($this->getTable().'.created_at', 'desc');
	}
	
	/**
	 * Randomize the results in the DB.  This shouldn't be used for large datasets
	 * cause it's not very performant
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  mixed $seed Providing a seed keeps the order the same on subsequent queries
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeRandomize($query, $seed = false) {
		if ($seed === true) $seed = Session::getId();
		if ($seed) return $query->orderBy(DB::raw('RAND("'.$seed.'")'));
		return $query->orderBy(DB::raw('RAND()'));
	}

	/**
	 * Filter by the current locale
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  string  $locale
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeLocalize($query, $locale = null) {
		return $query->where('locale', $locale ?: Decoy::locale());
	}

	/**
	 * Get localized siblings of this model
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeOtherLocalizations($query) {
		return $query->where('locale_group', $this->locale_group)
			->where($this->getKeyName(), '!=', $this->getKey());
	}
	
	/**
	 * Find by the slug.  Like "find()" but use the slug column instead
	 *
	 * @param string $string 
	 * @return Illuminate\Database\Eloquent\Model | false
	 */
	static public function findBySlug($slug) {
		return static::where('slug', $slug)->first();
	}

	/**
	 * Find by the slug and fail if missing.  Like "findOrFail()" but use the slug column instead
	 *
	 * @param string $string 
	 * @return Illuminate\Database\Eloquent\Model
	 * @throws Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	static public function findBySlugOrFail($slug) {
		return static::where('slug', $slug)->firstOrFail();
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------

	/**
	 * The pivot_id may be accessible at $this->pivot->id if the result was fetched
	 * through a relationship OR it may be named pivot_id out of convention (something
	 * currently done in Decoy_Base_Controller->get_index_child()).  This function
	 * checks for either
	 * 
	 * @return integer 
	 */
	public function pivotId() {
		if (!empty($this->pivot->id)) return $this->pivot->id;
		else if (!empty($this->pivot_id)) return $this->pivot_id;
		else return null;
	}
	
	/**
	 * Form a croppa URL, taking advantage of being able to set more columns null.  Also,
	 * provides an easier way to inform the source crops
	 * 
	 * @param int $width 
	 * @param int $height
	 * @param string $crop_style A key from the $crops property of the model
	 * @param string $field Where to find the source image.  May be a method name, defined on the model or a simple
	 *                      string of the column name in the database
	 * @param array $options Croppa-style options
	 * @return string A croppa URL
	 */
	public function croppa($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		
		// Get the image src path
		if (method_exists($this, $field)) $src = call_user_func(array($this, $field));
		else $src = $this->getOriginal($field); // Get un-mutated value
		if (empty($src)) return;

		// Check if the image field has crops
		if ($crop_style && !array_key_exists($field, static::$crops)) {
			throw new \Exception("A crop style was passed for $field but no crops are defined for that field.");
		}
		
		// Check if the crop style is valid
		if ($crop_style && !Collection::keyOrValueExists($crop_style, static::$crops[$field])) {
			throw new \Exception("Crop style '$crop_style' is not defined for the field: $field");
		}
		
		// Default crop style is 'default'
		if (!$crop_style && !empty(static::$crops[$field]) && Collection::keyOrValueExists('default', static::$crops[$field])) {
			$crop_style = 'default';
		}
		
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

	/**
	 * Return an image tag using croppa data
	 * 
	 * @param int $width 
	 * @param int $height
	 * @param string $crop_style A key from the $crops property of the model
	 * @param string $field Where to find the source image.  May be a method name, defined on the model or a simple
	 *                      string of the column name in the database
	 * @param array $options Croppa-style options
	 * @return string An image tag
	 */
	public function croppaTag($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		if (!($url = $this->croppa($width, $height, $crop_style, $field, $options))) return;
		return "<img src='{$url}'/>";
	}

	/**
	 * Return inline, background style elements using croppa data
	 *
	 * @param int $width 
	 * @param int $height
	 * @param string $crop_style A key from the $crops property of the model
	 * @param string $field Where to find the source image.  May be a method name, defined on the model or a simple
	 *                      string of the column name in the database
	 * @param array $options Croppa-style options
	 * @return string An image tag
	 */
	public function croppaBkgd($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		if (!($url = $this->croppa($width, $height, $crop_style, $field, $options))) return;
		return "background-image:url('{$url}')";
	}
	
	/**
	 * Get the admin controller class for this model.  It's assumed to NOT be a decoy controller.
	 * In other words, it's in app/controllers/admin/.
	 * 
	 * @return string ex: Admin\ArticlesController
	 */
	static public function adminControllerClass() {
		return ucfirst(Config::get('decoy::core.dir')).'\\'.Str::plural(get_called_class()).'Controller';
	}
	
	/**
	 * Add a field to the blacklist
	 *
	 * @param string $field 
	 */
	public function blacklist($field) {
		$this->guarded[] = $field;
	}
	
}
