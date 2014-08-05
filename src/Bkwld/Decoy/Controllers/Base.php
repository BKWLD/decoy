<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Routing\Wildcard;
use Bkwld\Decoy\Input\Files;
use Bkwld\Decoy\Input\Position;
use Bkwld\Decoy\Input\Sidebar;
use Bkwld\Decoy\Input\Search;
use Bkwld\Library;
use Config;
use Croppa;
use DB;
use Event;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Former;
use Input;
use Log;
use Redirect;
use Request;
use Response;
use Route;
use stdClass;
use URL;
use Validator;

/**
 * The base controller is gives Decoy most of the magic/for-free mojo
 * It's not abstract because it can't be instantiated with PHPUnit like that
 */
class Base extends Controller {
	
	//---------------------------------------------------------------------------
	// Default settings
	//---------------------------------------------------------------------------
	
	// Constants
	static public $per_page = 20;
	static public $per_sidebar = 6;

	// Values that get shared by many controller methods.  Default values for these
	// get set in the constructor.
	protected $model;       // i.e. Post
	protected $controller;  // i.e. Admin\PostsController
	protected $title;       // i.e. News Posts
	protected $description; // i.e. Relevant news about the brand
	protected $columns = array('Title' => 'title'); // The default columns for listings
	protected $show_view;   // i.e. admin.news.edit
	protected $search;      // i.e. An array describing the fields to search upon
	
	//---------------------------------------------------------------------------
	// Properties that define relationships
	//---------------------------------------------------------------------------

	/**
	 * An instance of the model that is the parent of the controller that is handling
	 * the request
	 * 
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $parent;
	
	/**
	 * Model class name i.e. Photo
	 * 
	 * @var string
	 */
	protected $parent_model;
	
	/**
	 * Controller class name i.e. Admin\PhotosController
	 * 
	 * @var sting
	 */
	protected $parent_controller;
	
	/**
	 * Relationship name on parents i.e. photos
	 * 
	 * @var string
	 */
	protected $parent_to_self;
	
	/**
	 * Relationship name on this controller's model to the parent i.e. post
	 * 
	 * @var string
	 */
	protected $self_to_parent;
	
	//---------------------------------------------------------------------------
	// Constructing
	//---------------------------------------------------------------------------

	// Shared layout for admin view, set in the constructor
	public $layout;
	protected function setupLayout() {
		if (!is_null($this->layout)) $this->layout = \View::make($this->layout);
	}
	
	/**
	 * For the most part, this populates the protected properties.  Constructor
	 * arguments are intentionally avoided so every controller that extends this
	 * doesn't have to include params in constructor definitions.
	 */
	public function __construct() {
		if ($this->injectDependencies()) $this->init();
	}
	
	/**
	 * Inject dependencies.  When used in normal execution, these are pulled automatically
	 * from the facaded App.  When this is not available, say when being run by unit tests,
	 * this method takes the dependencies in this array.
	 * 
	 * Note, not all dependencies are currently injected
	 * 
	 * @param Config $config
	 * @param Illuminate\Routing\Router $route
	 * @param Bkwld\Decoy\Routing\UrlGenerator $url
	 */
	private $config;
	private $route;
	private $url;
	public function injectDependencies($dependencies = null) {
		
		// Set manually passed dependencies
		if ($dependencies) {
			$this->config = $dependencies['config'];
			$this->route = $dependencies['route'];
			$this->url = $dependencies['url'];
			return true;
		}
		
		// Set dependencies automatically
		if (class_exists('App')) {
			$this->config = App::make('config');
			$this->url = App::make('decoy.url');
			$request = App::make('request');
			$this->route = App::make('router');
			return true;
		}
		
		// No dependencies found
		return false;
	}
	
	/**
	 * Populate the controller's protected properties
	 * @param string $class This would only be populated when mocking, ex: Admin\NewsController
	 */
	private function init($class = null) {
		
		// Set the layout from the Config file
		$this->layout = $this->config->get('decoy::layout');
		
		// Store the controller class for routing
		if ($class) $this->controller = $class;
		elseif (empty($this->controller)) $this->controller = get_class($this);
		
		// Get the controller name
		$controller_name = $this->controllerName($this->controller);
		
		// Make a default title based on the controller name
		if (empty($this->title)) $this->title = $this->title($controller_name);
		
		// Figure out what the show view should be.  This is the path to the show view file.  Such
		// as 'admin.news.edit'
		if (empty($this->show_view)) $this->show_view = $this->detailPath($this->controller);
		
		// Try to suss out the model by singularizing the controller
		if (empty($this->model)) {
			$this->model = $this->model($this->controller);
			if (!class_exists($this->model)) $this->model = NULL;
		}
		
		// This allows us to refer to the default model for a controller using the
		// generic term of "Model"
		if ($this->model && !class_exists('Bkwld\Decoy\Controllers\Model')) {
			if (!class_alias($this->model, 'Bkwld\Decoy\Controllers\Model')) throw new Exception('Class alias failed');
		}

		// If the input contains info on the parent, immediately instantiate
		// the parent instance
		if (($parent_id = Input::get('parent_id'))
			&& ($parent_controller = Input::get('parent_controller'))) {
			$parent_model_class = $this->model($parent_controller);
			$this->parent($parent_model_class::findOrFail($parent_id));
		}

	}
	
	/**
	 * Make a new instance of the base class for the purposes of testing
	 * @param string $path A request path, i.e. 'admin/news/create'
	 * @param string $verb i.e. GET,POST
	 */
	public function simulate($path, $verb = 'GET') {
		$wildcard = new Wildcard($this->config->get('decoy::dir'), $verb, $path);
		$class = $wildcard->detectController();
		$this->init($class);
	}
	
	//---------------------------------------------------------------------------
	// Getter/setter
	//---------------------------------------------------------------------------
	
	/**
	 * Get the controller name only, without the namespace (like Admin\) or
	 * suffix (like Controller).
	 * @param string $class ex: Admin\NewsController
	 * @return string ex: News
	 */
	public function controllerName($class = null) {
		$name = $class ? $class : get_class($this);
		$name = preg_replace('#^('.preg_quote('Bkwld\Decoy\Controllers\\').'|'.preg_quote('Admin\\').')#', '', $name);
		$name = preg_replace('#Controller$#', '', $name);
		return $name;
	}
	
	/**
	 * Get the title for the controller based on the controller name.  Basically, it's
	 * a de-studdly-er
	 * @param string $controller_name ex: 'Admins' or 'CarLovers'
	 * @return string ex: 'Admins' or 'Car Lovers'
	 */
	public function title($controller_name = null) {
		if (!$controller_name) return $this->title; // For when this is invoked as a getter for $this->title
		preg_match_all('#[a-z]+|[A-Z][a-z]*#', $controller_name, $matches);
		return implode(" ", $matches[0]);
	}

	/**
	 * Get the description for a controller
	 * @return string
	 */
	public function description() {
		return $this->description;
	}

	/**
	 * Get the columns for a controller
	 * @return array
	 */
	public function columns() {
		return $this->columns;
	}
	
	/**
	 * Get the search settings for a controller
	 * @return array
	 */
	public function search() {
		return $this->search;
	}

	/**
	 * Get the directory for the detail views.  It's based off the controller name.
	 * This is basically a conversion to snake case from studyly case
	 * @param string $class ex: 'Admin\NewsController'
	 * @return string ex: admins.edit or car_lovers.edit
	 */
	public function detailPath($class) {
		
		// Remove Decoy from the class
		$path = str_replace('Bkwld\Decoy\Controllers\\', '', $class, $is_decoy);
		
		// Remove the Controller suffix app classes may have
		$path = preg_replace('#Controller$#', '', $path);
		
		// Break up all the remainder of the class and de-study them (which is what
		// title() does)
		$parts = explode('\\', $path);
		foreach ($parts as &$part) $part = str_replace(' ', '_', strtolower($this->title($part)));
		$path = implode('.', $parts);
		
		// If the controller is part of Decoy, add it to the path
		if ($is_decoy) $path = 'decoy::'.$path;
	
		// Done
		return $path.'.edit';	
	}
	
	/**
	 * Figure out the model for a controller class
	 * @param string $class ex: "Admin\SlidesController"
	 * @return string ex: "Slide"
	 */
	public function model($class = null) {
		if (!$class) return $this->model; // for getters
		
		// Swap out the namespace if decoy
		$model = str_replace('Bkwld\Decoy\Controllers', 'Bkwld\Decoy\Models', $class, $is_decoy);
		
		// Remove the Controller suffix app classes may have
		$model = preg_replace('#Controller$#', '', $model);
		
		// Assume that non-decoy models want the first namespace (aka Admin) removed
		if (!$is_decoy) $model = preg_replace('#^\w+'.preg_quote('\\').'#', '', $model);
		
		// Make it singular
		$model = Str::singular($model);
		return $model;
	}
	
	/**
	 * Return controller
	 * 
	 * @return string ex: Admin\SlidesController
	 */
	public function controller() { return $this->controller; }
	
	/**
	 * Give this controller a parent model instance.  For instance, this makes the index
	 * view a listing of just the children of the parent.
	 *
	 * @param Illuminate\Database\Eloquent\Model $parent
	 */
	public function parent($parent) {

		// Save out the passed reference
		$this->parent = $parent;

		// Save out sub properties that I hope to deprecate
		$this->parent_model = get_class($this->parent);
		$this->parent_controller = 'Admin\\'.Str::plural($this->parent_model).'Controller';

		// Figure out what the relationship function to the child (this controller's
		// model) on the parent model .  It will be the plural version of this model's
		// name.
		$this->parent_to_self = Str::plural(lcfirst($this->model));

		// If the parent is the same as this controller, assume that it's a many-to-many-to-self
		// relationship.  Thus, expect a relationship method to be defined on the model
		// called "RELATIONSHIPAsChild".  I.e. "postsAsChild"
		if ($this->parent_controller == $this->controller && method_exists($this->model, $this->parent_to_self.'AsChild')) {
			$this->self_to_parent = $this->parent_to_self.'AsChild';

		// Save out to self to parent relationship.  It will be singular if the relationship
		// is a many to many.
		} else {
			$relationship = lcfirst(get_class($this->parent));
			$this->self_to_parent = $this->isChildInManyToMany()? 
				Str::plural($relationship): 
				$relationship;
		}

		// Make chainable
		return $this;
	}

	/**
	 * Return parent model
	 * 
	 * @return string ex: "Article"
	 */
	public function parentModel() { return $this->parent_model; }

	/**
	 * Get parent controller
	 * 
	 * @return string ex: Admin\ArticlesController
	 */
	public function parentController() { return $this->parent_controller; }
	
	/**
	 * Determine whether the relationship between the parent to this controller
	 * is a many to many
	 * 
	 * @return boolean
	 */
	public function isChildInManyToMany() {
		return is_a($this->parentRelation(), 
			'Illuminate\Database\Eloquent\Relations\BelongsToMany');

	}
	
	//---------------------------------------------------------------------------
	// Basic CRUD methods
	//---------------------------------------------------------------------------
	
	// Listing page
	public function index() {
		
		// Open up the query. We can assume that Model has an ordered() function
		// because it's defined on Decoy's Base_Model.
		$query = $this->parent ? $this->parentRelation()->ordered() : Model::ordered();

		// Run the query. 
		$search = new Search();
		$results = $search->apply($query, $this->search)->paginate($this->perPage());
		
		// Render the view using the `listing` builder.
		$listing = Former::listing($this->model)
			->controller($this)
			->items($results);
		if ($this->parent) $listing->parent($this->parent);
		$this->layout->content = $listing;
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
	}
	
	/**
	 * Create form
	 */
	public function create() {

		// Pass validation through
		Former::withRules(Model::$rules);
		
		// Return view
		$this->layout->nest('content', $this->show_view, array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			
			// Will never be used in a "new" view, but will keep errors from being thrown 
			// about "undefined property"
			'item'             => null,
			'crops'            => (object) Model::$crops,
		));
		
		// Pass parent_id
		if (isset($this->parent)) $this->layout->content->parent_id = $this->parent->getKey();
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
	}
	
	/**
	 * Store a new submission
	 */
	public function store() {
		
		// Create a new object and hydrate
		$item = new Model();
		$item->fill(Library\Utils\Collection::nullEmpties(Input::get()));
		$slugger = App::make('decoy.slug');
		$slugger->merge($item);

		// Add foreign keys to the model instance manually.  This is done
		// This was added so that the unique_with validator could test slugs that
		// are unqiue across multiple columns.  Those other columns are typically
		// foreign keys.
		foreach ($this->allForeignKeyPairs() as $pair) {
			$item->setAttribute($pair->key, $pair->val);
			$slugger->addWhere($item, $pair->key, $pair->val);
		}

		// Validate
		if ($result = $this->validate($item)) return $result;

		// Save it.  We don't save through relations becaue the foreign keys were manually
		// set previously.  And many to many relationships are not formed during a store().
		$item->save();
		
		// Redirect to edit view
		if (Request::ajax()) return Response::json(array('id' => $item->id));
		else return Redirect::to($this->url->relative('edit', $item->id));
	}
	
	/**
	 * Edit form
	 */
	public function edit($id) {

		// Get the work
		if (!($item = Model::find($id))) return App::abort(404);

		// Populate form
		Former::populate($item);
		Former::withRules(Model::$rules);
		
		// Render the view
		$this->layout->nest('content', $this->show_view, array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			'item'             => $item,
			'crops'            => (object) Model::$crops,
		));

		// If the subclass implements the sidebar no-op, use it's response for
		// the related data.
		if ($listings = $this->sidebar($item)) {
			$sidebar = new Sidebar($listings, $item);
			$this->layout->content->related = $sidebar->render();
		}
		
		// Figure out the parent_id
		if ($this->self_to_parent) {
			$foreign_key = $item->{$this->self_to_parent}()->getForeignKey();
			$this->layout->content->parent_id = $item->{$foreign_key};
		}
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());

	}
	
	// Update an item
	public function update($id) {

		// Load the entry
		if (!($item = Model::find($id))) {
			if (Request::ajax()) return Response::json(null, 404);
			else return App::abort(404);
		}

		// Hydrate for drag-and-drop sorting
		if (Request::ajax() 
			&& ($position = new Position($item, $this->self_to_parent)) 
			&& $position->has()) $position->fill(); 
		
		// ... else hydrate normally
		else {
			$item->fill(Library\Utils\Collection::nullEmpties(Input::get()));
			$slugger = App::make('decoy.slug');
			$slugger->merge($item);

			// If this is a child, add "where" conditions on the slug uniqueness
			foreach ($this->allForeignKeyPairs() as $pair) {
				$slugger->addWhere($item, $pair->key, $pair->val);
			}
		}

		// Validate data
		if ($result = $this->validate($item)) return $result;

		// Save the record
		$item->save();

		// Redirect to the edit view
		if (Request::ajax()) return Response::json('ok');
		else return Redirect::to(URL::current());
		
	}
	
	// Delete a record
	public function destroy($id) {
		
		// Find the item
		if (!($item = Model::find($id))) return App::abort(404);
		
		// Delete row (this should trigger file attachment deletes as well)
		$item->delete();
	
		// As long as not an ajax request, go back to the parent directory of the referrer
		if (Request::ajax()) return Response::json('ok');
		else return Redirect::to($this->url->relative('index'));
	}
	
	//---------------------------------------------------------------------------
	// Many To Many CRUD
	//---------------------------------------------------------------------------
	
	// List as JSON for autocomplete widgets
	public function autocomplete() {
		
		// Do nothing if the query is too short
		if (strlen(Input::get('query')) < 1) return Response::json(null);
		
		// Get data matching the query
		if (empty(Model::$title_column)) throw new Exception($this->model.'::$title_column must be defined');
		$query = Model::ordered()->where(Model::$title_column, 'LIKE', '%'.Input::get('query').'%');
		
		// Don't return any rows already attached to the parent.  So make sure the id is not already
		// in the pivot table for the parent
		if ($this->isChildInManyToMany()) {
			
			// See if there is an exact match on what's been entered.  This is useful for many
			// to manys with tags because we want to know if the reason that autocomplete
			// returns no results on an exact match that is already attached is because it
			// already exists.  Otherwise, it would allow the user to create the tag
			if ($this->parentRelation()
				->where(Model::$title_column, '=', Input::get('query'))
				->count()) {
				return Response::json(array('exists' => true));
			}
			
			// Get the ids of already attached rows through the relationship function.  There
			// are ways to do just in SQL but then we lose the ability for the relationship
			// function to apply conditions, like is done in polymoprhic relationships.
			$siblings = $this->parentRelation()->get();
			if (count($siblings)) {
				$sibling_ids = array();
				foreach($siblings as $sibling) $sibling_ids[] = $sibling->id;	
				
				// Add condition to query
				$query = $query->whereNotIn('id', $sibling_ids);
			}
		}
		
		// Return result
		return Response::json($this->formatAutocompleteResponse($query->get()));
		
	}
	
	// Attach a model to a parent_id, like with a many to many style
	// autocomplete widget
	public function attach($id) {
		
		// Require there to be a parent id and a valid id for the resource
		if (!($item = Model::find($id))) return Response::json(null, 404);
		
		// Do the attach
		$this->fireEvent('attaching', array($item));
		$item->{$this->self_to_parent}()->attach($this->parent);
		$this->fireEvent('attached', array($item));
		
		// Return the response
		return Response::json('ok');
		
	}
	
	// Remove a relationship.  Very similar to delete, except that we're
	// not actually deleting from the database
	public function remove($id) {

		// Support removing many ids at once
		$ids = Input::has('ids') ? explode(',', Input::get('ids')) : array($id);
		
		// Lookup up the parent model so we can bulk remove multiple of THIS model
		$this->fireEvent('removing', array($this->parent, $ids));
		$this->parentRelation()->detach($ids);
		$this->fireEvent('removed', array($this->parent, $ids));
		
		// Redirect.  We can use back cause this is never called from a "show"
		// page like get_delete is.
		if (Request::ajax()) return Response::json('ok');
		else return Redirect::back();
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	// All actions validate in basically the same way.  This is
	// shared logic for that
	/**
	 * Shared validation helper
	 * @param Bkwld\Decoy\Model\Base $model The model instance that is being worked on
	 * @param array A Laravel rules array. If null, will be pulled from model
	 * @param array $messages Special error messages
	 */
	protected function validate($model = null, $rules = null, $messages = array()) {
		
		// Pull the input including files.  We manually merge files in because Laravel's Input::all()
		// does a recursive merge which results in file fields containing BOTH the string version
		// of the previous file plus the new File instance when the user is replacing a file during
		// an update.  The array_filter() is there to strip out empties from the files array.  This
		// prevents empty file fields from overriding the contents of the hidden field that stores
		// the previous file name.
		$input = array_replace_recursive(Input::get(), array_filter(Input::file()));

		// Fire validating event
		if ($model && ($response = $this->fireEvent('validating', array($model, $input), true))) {
			if (is_a($response, 'Symfony\Component\HttpFoundation\Response')) return $response;
		}

		// Pull the rules from the model instance.  This itentionally comes after the validating
		// event is fired, so handlers of that event can modify the rules.
		if ($model && empty($rules)) $rules = $model::$rules;

		// If an AJAX update, don't require all fields to be present. Pass
		// just the keys of the input to the array_only function to filter
		// the rules list.
		if (Request::ajax() && Request::getMethod() == 'PUT') {
			$rules = array_only($rules, array_keys($input));
		}

		// If a model instance was passed, merge the input on top of that.  This allows
		// data that may already be set on the model to be validated.  The input will override
		// anything already set on the model.  In particular, this is done so that auto
		// generated fields like the slug can be validated.  This intentionally comes after the
		// AJAX conditional so that we still only validate fields that were present in
		// the AJAX request.
		if ($model && method_exists($model, 'getAttributes')) {
			$input = array_merge($model->getAttributes(), $input);
		}

		// Validate
		$validation = Validator::make($input, $rules, $messages);
		if ($validation->fails()) {
			
			// Log validation errors
			if (Config::get('app.debug')) Log::debug(print_r($validation->messages(), true));
			
			// Respond
			if (Request::ajax()) {
				return Response::json($validation->messages(), 400);
			} else {
				return Redirect::to(Request::path())
					->withInput()
					->withErrors($validation);
			}
		}
		
		// Fire event
		$this->fireEvent('validated', array($model, $input));
		
		// If there were no errors, return false, which means
		// that we don't need to redirect
		return false;
	}
	
	/**
	 * Take an array of key / value (url/label) pairs and pass it where
	 * it needs to go to make the breadcrumbs
	 * @param $array links
	 */
	protected function breadcrumbs($links) {
		$this->layout->nest('breadcrumbs', 'decoy::layouts._breadcrumbs', array(
			'breadcrumbs' => $links
		));
	}
	
	/**
	 * Fire an event.  Actually, two are fired, one for the event and one that mentions
	 * the model for this controller
	 * @param $string  event The name of this event
	 * @param $array   args  An array of params that will be passed to the handler
	 * @param $boolean until Whether to fire an "until" event or not
	 */
	protected function fireEvent($event, $args = null, $until = false) {
		
		// Setup both events
		$events = array("decoy.{$event}");
		if (!empty($this->model)) $events[] = "decoy.{$event}: ".$this->model;
		
		// Fire them
		foreach($events as $event) {
			if ($until) {
				if ($response = Event::until($event, $args)) return $response;
			} else Event::fire($event, $args, $until);
		}
	}
	
	// Format the results of a query in the format needed for the autocomplete repsonses
	public function formatAutocompleteResponse($results) {
		$output = array();
		foreach($results as $row) {
			
			// Only keep the id and title fields
			$item = new stdClass;
			$item->id = $row->id;
			$item->title = $row->{Model::$title_column};
			
			// Add properties for the columns mentioned in the list view within the
			// 'columns' property of this row in the response.  Use the same logic
			// found in Decoy::renderListColumn();
			$item->columns = array();
			foreach($this->columns as $column) {
				if (method_exists($row, $column)) $item->columns[$column] = call_user_func(array($row, $column));
				elseif (isset($row->$column)) $item->columns[$column] = $row->$column;
				else $item->columns[$column] = null;
			}
			
			// Add the item to the output
			$output[] = $item;
		}
		return $output;
	}
	
	// Return the per_page based on the input
	public function perPage() {
		$per_page = Input::get('count', static::$per_page);
		if ($per_page == 'all') return 1000;
		return $per_page;
	}

	/**
	 * A no-op that can be used to generate the sidebar on an edit page without subclassing
	 * the edit() method.
	 * @param  Illuminate\Database\Eloquent\Model $item The model instance the edit page
	 *         is currently acting upon.
	 * @return Bkwld\Decoy\Fields\Listing | string
	 */
	protected function sidebar($item) {}

	/**
	 * Get all the foreign keys and values on the relationship with the parent
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @return array A list of key-val objects that have the column name and value for
	 * the active relationship
	 */
	private function allForeignKeyPairs($relation = null) {
		$pairs = array();

		// Get the relation if not defined
		if ($relation || ($relation = $this->parentRelation())) {

			// Add standard belongs to foreign key
			if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasOneOrMany')) {
				$pairs[] = (object) array(
					'key' => $relation->getPlainForeignKey(), 
					'val' => $relation->getParentKey()
				);
			}

			// Add polymorphic column
			if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\MorphOneOrMany')) {
				$pairs[] = (object) array(
					'key' => $relation->getPlainMorphType(), 
					'val' => $relation->getMorphClass()
				);
			}

			// Return the pairs
			return $pairs;

		// Relation could not be found, so return nothing
		} else return array();

	}

	/**
	 * Run the parent relationship function for the active model, returning the Relation
	 * object. Returns false if none found.
	 * @return Illuminate\Database\Eloquent\Relations\Relation | false
	 */
	private function parentRelation() {
		if ($this->parent
			&& method_exists($this->parent, $this->parent_to_self)) 
			return $this->parent->{$this->parent_to_self}();
		else return false;
	}
	
}
