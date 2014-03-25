<?php namespace Bkwld\Decoy\Models;

// Imports
use App;
use Bkwld\Library\Utils\File;
use Bkwld\Library\Utils\Collection;
use Bkwld\Decoy\Input\Files;
use Config;
use Croppa;
use DB;
use Decoy;
use Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Input;
use Log;
use Request;
use Str;

abstract class Base extends Eloquent {
	
	//---------------------------------------------------------------------------
	// Overrideable properties
	//---------------------------------------------------------------------------
	
	// This should be overridden by Models to store the array of their 
	// Laravel validation rules
	static public $rules = array();
	
	// This is designed to be overridden to store the DB column name that
	// should be used as the source for titles.  Used in the title() function
	// and in autocompletes.
	static public $title_column;
	
	// This is should be overriden like so to specify crops that the image cropping
	// widget should make UI for
	// array('image' => array('marquee' => '4:3', 'feature'))
	static public $crops = array();
	
	/**
	 * Constructor registers events and configures mass assignment
	 */
	public function __construct(array $attributes = array()) {
		
		// Blacklist special columns that aren't intended for the DB
		$this->guarded = array_merge($this->guarded, array(
			'_token', // Part of CSRF protection
			'_wysihtml5_mode',
			'_save', // The submit buttons, tells us which submit button they clicked
			'parent_controller', // Backbone.js sends this with sort updates
			'parent_id', // Backbone.js may also send this with sort
		));
		
		// Remove any hidden/visible settings that may have been set on models if
		// the user is in the admin
		if (Decoy::handling()) $this->visible = $this->hidden = array();

		// Continue Laravel construction
		parent::__construct($attributes);
		
		// Register Laravel model events
		static::registerModelEvents();
	}

	// Disable all mutatators while in Admin by returning that no mutators exist
	public function hasGetMutator($key) { 
		return Decoy::handling() ?  false : parent::hasGetMutator($key);
	}
	public function hasSetMutator($key) { 
		return Decoy::handling() ?  false : parent::hasSetMutator($key);
	}
	
	//---------------------------------------------------------------------------
	// Model event callbacks
	//---------------------------------------------------------------------------
	// Setup listeners for all of Laravel's built in events that fire our no-op
	// callbacks. These listeners are created when the first instance of a model
	// is created.
	
	// Listen for model events and execute no-op callbacks on the model instance
	static private $models_registered_for_events = array();
	static private function registerModelEvents() {
		
		// Only run once per class.  Having to store in an array like this because
		// setting a boolean on the class kept setting it to true for all models that
		// inherit from Base.  I couldn't seem to set the boolean on the child only.
		$class = get_called_class();
		if (in_array($class, self::$models_registered_for_events)) return;
		self::$models_registered_for_events[] = $class;
		
		// Setup a files instance for auto handling of file input
		$files = new Files();
		
		// Built in Laravel model events.  Note the special file handling that happens
		// on save and delete
		self::creating (function($model){ return $model->onCreating(); });
		self::created  (function($model){ return $model->onCreated(); });
		self::updating (function($model){ return $model->onUpdating(); });
		self::updated  (function($model){ return $model->onUpdated(); });
		self::saving   (function($model) use ($files){ 
			$files->delete($model);
			$files->save($model);
			return $model->onSaving(); 
		});
		self::saved    (function($model){ return $model->onSaved(); });
		self::deleting (function($model) use ($files){ 
			$files->delete($model); 
			return $model->onDeleting(); 
		});
		self::deleted  (function($model){ return $model->onDeleted(); });
		
		// Decoy events
		$events = array('validating', 'validated', 'attaching', 'attached', 'removing', 'removed');
		foreach ($events as $event) {
			Event::listen('decoy.'.$event.': '.$class, function($model = null, $options = null) use ($event, $files) {
				
				// It's possible a model wasn't defined
				if (!$model) return;
				
				// Special files behavior
				if ($event == 'validating') $files->preValidate($model);
				
				// Call the appropriate model callback with all other arguments
				$args = array_slice(func_get_args(), 1);
				$callback = 'on'.ucfirst($event);
				return call_user_func_array(array($model, $callback), $args);
			});
		}
		
	}
	
	// The no-op callbacks.  They have to be defined as public because they are invoked 
	// from anonymous functions.
	public function onSaving() {}
	public function onSaved() {}
	public function onValidating() {} // input is passed in first arg
	public function onValidated() {} // input array is passed in first arg
	public function onCreating() {}
	public function onCreated() {}
	public function onUpdating() {}
	public function onUpdated() {}
	public function onDeleting() {}
	public function onDeleted() {}
	public function onAttaching() {}
	public function onAttached() {}
	public function onRemoving() {} // ids are passed in first arg
	public function onRemoved() {} // ids are passed in first arg
	
		
	//---------------------------------------------------------------------------
	// Overrideable methods
	//---------------------------------------------------------------------------
	
	/**
	 * Return the title for the row for the purpose of displaying
	 * in admin list views and breadcrumbs.  It looks for columns
	 * that are named like common things that would be titles
	 */
	public function title() {
		$title = '';
		
		// Add a thumbnail to the title if there is an "image" field
		if (method_exists($this, 'image') && $image = $this->image()) {
			if ($image && Str::startsWith($image, array('//', 'http'))) $title .= '<img src="'.$image.'"/> ';
			else $title .= '<img src="'.Croppa::url($image, 40, 40).'"/> ';
		} elseif (!method_exists($this, 'image') && !empty($this->image)) {
			if (Str::startsWith($this->image, array('//', 'http'))) $title .= '<img src="'.$image.'"/> ';
			else $title .= '<img src="'.$this->croppa(40,40).'"/> ';
		}

		// Append the text portion of the title
		return $title.$this->titleText();

	}

	/**
	 * Deduce the source for the title of the model and return that title
	 * @return string 
	 */
	public function titleText() {

		// Convert to an array so I can test for the presence of values.
		// As an object, it would throw exceptions
		$row = $this->getAttributes();

		// Deduce and return
		if (!empty(static::$title_column)) return $row[static::$title_column];
		else if (isset($row['name'])) return $row['name']; // Name before title to cover the case of people with job titles
		else if (isset($row['title'])) return $row['title'];
		else if (App::make('decoy.router')->action() == 'edit') return 'Edit';
		else return '';
	}

	/**
	 * Save out an image or file given the field name.  They are saved
	 * to the directory specified in the bundle config
	 */
	public function saveImage($field = 'image') { return $this->saveFile($field); }
	public function saveFile($field = 'file') {
		$path = File::organizeUploadedFile(Input::file($field), Config::get('decoy::upload_dir'));
		$path = File::publicPath($path);
		return $path;
	}
	
	//---------------------------------------------------------------------------
	// Scopes
	//---------------------------------------------------------------------------
	
	/**
	 * Default ordering by descending time, designed to be overridden
	 */
	public function scopeOrdered($query) {
		return $query->orderBy($this->getTable().'.created_at', 'desc');
	}
	
	/**
	 * Get visible items
	 */
	public function scopeVisible($query) {
		return $query->where($this->getTable().'.visible', '=', '1');
	}
	
	/**
	 * Get all visible items by the default order
	 */
	public function scopeOrderedAndVisible($query) {
		return $query->ordered()->visible();
	}

	/**
	 * Order a table that has a position value
	 */
	public function scopePositioned($query) {
		return $query->orderBy($this->getTable().'.position', 'asc')
			->orderBy($this->getTable().'.created_at', 'desc');
	}
	
	/**
	 * Randomize the results in the DB.  This shouldn't be used for large datasets
	 * cause it's not very performant
	 */
	public function scopeRandomize($query) {
		return $query->orderBy(DB::raw('RAND()'));
	}
	
	/**
	 * Find by the slug.  Like "find()" but use the slug column instead
	 */
	static public function findBySlug($slug) {
		return static::where('slug', '=', $slug)->first();
	}

	/**
	 * Find by the slug and fail if missing.  Like "findOrFail()" but use the slug column instead
	 */
	static public function findBySlugOrFail($slug) {
		return static::where('slug', '=', $slug)->firstOrFail();
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	/**
	 * A no-op that should return the deep link to this content
	 */
	public function deepLink() {}

	/**
	 * The pivot_id may be accessible at $this->pivot->id if the result was fetched
	 * through a relationship OR it may be named pivot_id out of convention (something
	 * currently done in Decoy_Base_Controller->get_index_child()).  This function
	 * checks for either
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
	 */
	public function croppa($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		
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

	/**
	 * Return an image tag using croppa data
	 */
	public function croppaTag($width = null, $height = null, $crop_style = null, $field = 'image', $options = null) {
		return '<img src="'.$this->croppa($width, $height, $crop_style, $field, $options).'"/>';
	}
	
	/**
	 * Get the admin controller class for this model.  It's assumed to NOT be a decoy controller.
	 * In other words, it's in app/controllers/admin/.
	 * @return string ex: Admin\ArticlesController
	 */
	static public function adminControllerClass() {
		return ucfirst(Config::get('decoy::dir')).'\\'.Str::plural(get_called_class()).'Controller';
	}
	
	/**
	 * Add a field to the blacklist
	 */
	public function blacklist($field) {
		$this->guarded[] = $field;
	}
	
}
