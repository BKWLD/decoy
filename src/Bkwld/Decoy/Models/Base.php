<?php namespace Bkwld\Decoy\Models;

// Imports
use App;
use Bkwld\Decoy\Input\ManyToManyChecklist;
use Bkwld\Decoy\Exceptions\Exception;
use Bkwld\Library\Utils\Collection;
use Bkwld\Upchuck\SupportsUploads;
use Config;
use Croppa;
use Cviebrock\EloquentSluggable\SluggableInterface;
use Cviebrock\EloquentSluggable\SluggableTrait;
use DB;
use Decoy;
use DecoyURL;
use Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Input;
use Log;
use Request;
use Session;
use Str;
use URL;

abstract class Base extends Eloquent implements SluggableInterface {

	/**
	 * Adding common traits.  The memory usage of adding additional methods is
	 * negligible.
	 */
	use SupportsUploads, SluggableTrait {
		needsSlugging as traitNeedsSlugging;
	}
	
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
	 * This is should be overriden like so to specify crops that the image 
	 * cropping widget should make UI.  Example:
	 * 
	 * array('image' => array('marquee' => '4:3', 'feature'))
	 *
	 * @var array
	 */
	static public $crops = [];

	/**
	 * Should this model be localizable in the admin.  If not undefined, will 
	 * override the site config "auto_localize_root_models"
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
	public function onValidating($validation) {} // Illuminate\Validation\Validator
	public function onValidated($validation) {} // Illuminate\Validation\Validator
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
	// Slug creation via cviebrock/eloquent-sluggable
	//---------------------------------------------------------------------------

	/**
	 * Tell sluggable where to get the source for the slug and apply other
	 * customizations.
	 * 
	 * @var array
	 */
	protected $sluggable = array(
		'build_from' => 'admin_title',
		'max_length' => 100,
	);

	/**
	 * Check for a validation rule for a slug column
	 * 
	 * @return boolean 
	 */
	protected function needsSlugging() {
		if (!array_key_exists('slug', static::$rules)) return false;
		return $this->traitNeedsSlugging();
	}

	//---------------------------------------------------------------------------
	// Accessors
	//---------------------------------------------------------------------------
	
	/**
	 * Deprecated
	 *
	 * @return string 
	 */
	public function title() {
		return $this->getAdminTitleHtmlAttribute();
	}

	/**
	 * Return the title for the row for the purpose of displaying in admin list 
	 * views and breadcrumbs.  It looks for columns that are named like common 
	 * things that would be titles.
	 *
	 * @return string 
	 */
	public function getAdminTitleHtmlAttribute() {
		return $this->croppaTag(40, 40).$this->getAdminTitleAttribute();
	}

	/**
	 * Deprecated
	 * 
	 * @return string 
	 */
	public function titleText() {
		return $this->getAdminTitleAttribute();
	}

	/**
	 * Deduce the source for the title of the model and return that title
	 * 
	 * @return string 
	 */
	public function getAdminTitleAttribute() {
		return implode(' ', array_map(function($attribute) {
			return $this->$attribute;
		}, $this->titleAttributes())) ?: 'Untitled';
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
		if (method_exists($this, 'getUploadMap')) {
			$attributes = array_unique(array_merge($attributes, 
				array_values($this->getUploadMap())));
		}

		// Return array of attributes
		return $attributes;
	}

	/**
	 * A no-op that can add classes to rows in listing tables in the admin
	 *
	 * @return string 
	 */
	public function getAdminRowClassAttribute() { }

	//---------------------------------------------------------------------------
	// Listing view, action-column accessors
	//---------------------------------------------------------------------------

	/**
	 * Make the markup for the actions column of the admin listing view.  The
	 * indivudal actions are stored in an array that is iterted through in the
	 * view
	 *
	 * @param array $data The data passed to a listing view
	 * @return array 
	 */
	public function makeAdminActions($data) {
		$actions = [];
		if ($html = $this->makeVisibilityAction($data)) $actions['visibility'] = $html;
		if ($html = $this->makeEditAction($data))       $actions['edit'] = $html;
		if ($html = $this->makeViewAction($data))       $actions['view'] = $html;
		if ($html = $this->makeDeleteAction($data))           $actions['delete'] = $html;
		return $actions;
	}

	/**
	 * Make the visibility action
	 *
	 * @param array $data The data passed to a listing view
	 * @return string
	 */
	protected function makeVisibilityAction($data) {
		extract($data);
		
		// Check if this model supports editing the visibility
		if ($many_to_many 
			|| !app('decoy.auth')->can('publish', $controller) 
			|| !array_key_exists('visible', $this->attributes)) return;

		// Create the markup
		$is_visible = $this->getAttribute('visible');
		return sprintf('<a class="visibility js-tooltip" data-placement="left" title="%s">
				<span class="glyphicon glyphicon-eye-%s"></span>
			</a>', 
			$is_visible ? 'Make draft' : 'Publish',
			$is_visible ? 'open' : 'close'
		);

	}

	/**
	 * Make the edit action.
	 *
	 * @param array $data The data passed to a listing view
	 * @return string 
	 */
	protected function makeEditAction($data) {
		extract($data);
		return sprintf('<a href="%s" class="action-edit js-tooltip" 
			data-placement="left" title="Edit in admin">
				<span class="glyphicon glyphicon-pencil"></span>
			</a>', $this->getAdminEditUri($controller, $many_to_many));
	}

	/**
	 * Get the admin edit URL assuming you know the controller and whether it's
	 * being listed as a many to many 
	 *
	 * @param string $controller ex: Admin\ArticlesController
	 * @param boolean $many_to_many
	 * @return string 
	 */
	public function getAdminEditUri($controller, $many_to_many = false) {
		if ($many_to_many) return URL::to(DecoyURL::action($controller, $this->getKey()));
		return URL::to(DecoyURL::relative('edit', $this->getKey(), $controller));
	}

	/**
	 * Make the view action
	 *
	 * @param array $data The data passed to a listing view
	 * @return string 
	 */
	protected function makeViewAction($data) {
		if (!$uri = $this->getUriAttribute()) return;
		return sprintf('<a href="%s" target="_blank" class="action-view js-tooltip" 
			data-placement="left" title="View on site">
				<span class="glyphicon glyphicon-bookmark"></span>
			</a>', $uri);
	}

	/**
	 * Make the delete action
	 *
	 * @param array $data The data passed to a listing view
	 * @return string 
	 */
	protected function makeDeleteAction($data) {
		extract($data);

		// Check if this model can be deleted.  This mirrors code found in the table
		//  partial for generating the edit link on the title
		if (!(app('decoy.auth')->can('destroy', $controller) 
			|| ($many_to_many && app('decoy.auth')->can('update', $parent_controller)))) return;

		// Return markup
		return sprintf('<a class="remove-now js-tooltip" data-placement="left" title="%s">
				<span class="glyphicon glyphicon-%s"></span>
			</a>',
			$many_to_many ? 'Remove relationship' : 'Permanently delete',
			$many_to_many ? 'remove' : 'trash'
		);
	}
	
	//---------------------------------------------------------------------------
	// Scopes
	//---------------------------------------------------------------------------
	
	/**
	 * Search the title (where "title" is the admin definiton of the title) for
	 * the terms.  This is designed for the Decoy autocomplete
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  string $term 
	 * @throws Bkwld\Decoy\Exceptions\Exception
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeTitleContains($query, $term, $exact = false) {

		// Get an instance so the title attributes can be found.
		if (!$model = static::first()) return;

		// Get the title attributes
		$attributes = $model->titleAttributes();
		if (empty($attributes)) throw new Exception('No searchable attributes');

		// Concatenate all the attributes with spaces and look for the term.
		$source = DB::raw('CONCAT('.implode('," ",',$attributes).')');
		return $exact ? 
			$query->where($source, '=', $term) :
			$query->where($source, 'LIKE', "%$term%");
	}

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
	 * Get all visible items by the default order.  This is a good thing to
	 * subclass to define special listing scopes used ONLY on the frontend.  As
	 * compared with scopeOrdered().
	 *
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return Illuminate\Database\Query\Builder
	 */
	public function scopeListing($query) {
		return $query->orderedAndVisible();
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
	 * Find by the slug and fail if missing.  Invokes methods from the
	 * Sluggable trait.
	 *
	 * @param string $string 
	 * @return Illuminate\Database\Eloquent\Model
	 * @throws Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	static public function findBySlugOrFail($slug) {
		if ($item = static::findBySlug($slug)) return $item;
		throw (new ModelNotFoundException)->setModel(get_called_class());
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------

	/**
	 * Deduce the source for the title of the model
	 * 
	 * @return array 
	 */
	public function titleAttributes() {

		// Convert to an array so I can test for the presence of values. As an 
		// object, it would throw exceptions
		$row = $this->getAttributes();

		 // Name before title to cover the case of people with job titles
		if (isset($row['name'])) return ['name'];
		else if (isset($row['first_name']) && isset($row['last_name'])) return ['first_name', 'last_name'];
		else if (isset($row['title'])) return ['title'];
		return [];
	}

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
	 * Get the admin controller class for this model.
	 * 
	 * @return string ex: Admin\ArticlesController
	 */
	static public function adminControllerClass() {
		$class = get_called_class();

		// Decoy controller
		if (strpos($class, 'Bkwld\Decoy\Models') !== false) {
			return Str::plural(str_replace('Bkwld\Decoy\Models', 'Bkwld\Decoy\Controllers', $class));

		// App controller
		} else {
			return ucfirst(Config::get('decoy::core.dir')).'\\'.Str::plural($class).'Controller';
		}
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
