<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Routing\Ancestry;
use Bkwld\Decoy\Routing\Wildcard;
use Bkwld\Decoy\Input\Files;
use Bkwld\Decoy\Input\Position;
use Bkwld\Decoy\Input\ManyToManyChecklist;
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
	const PER_PAGE = 20;

	// Values that get shared by many controller methods.  Default values for these
	// get set in the constructor.
	protected $model;       // i.e. Post
	protected $controller;  // i.e. Admin\PostsController
	protected $title;       // i.e. News Posts
	protected $description; // i.e. Relevant news about the brand
	protected $columns = array('Title' => 'title'); // The default columns for listings
	protected $show_view;   // i.e. admin.news.edit
	protected $search;      // i.e. An array describing the fields to search upon
	
	// More of the same, but these are just involved in relationships
	protected $parent_model;      // i.e. Photo
	protected $parent_controller; // i.e. admin.photos
	protected $parent_to_self;    // i.e. photos
	protected $self_to_parent;    // i.e. post
	
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
	 * @param Ancestry $ancestry
	 * @param Illuminate\Routing\Router $route
	 * @param Bkwld\Decoy\Routing\UrlGenerator $url
	 */
	private $config;
	private $ancestry;
	private $route;
	private $url;
	public function injectDependencies($dependencies = null) {
		
		// Set manually passed dependencies
		if ($dependencies) {
			$this->config = $dependencies['config'];
			$this->ancestry = $dependencies['ancestry'];
			$this->route = $dependencies['route'];
			$this->url = $dependencies['url'];
			return true;
		}
		
		// Set dependencies automatically
		if (class_exists('App')) {
			$this->config = App::make('config');
			$this->url = App::make('decoy.url');
			$request = App::make('request');
			$this->ancestry = new Ancestry($this, App::make('decoy.wildcard'), $request, $this->url);
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
		
		// The rest of the logic has to do with ancestry.  If we're on a URL that was registered
		// explicitly in the router, we assume there is no ancestry at play.  The ancestry class
		// assumes that the route was resolved using the Decoy wildcard router
		if ($this->route->currentRouteAction()) return;
		
		// If the current route has a parent, discover what it is
		if (empty($this->parent_controller) && $this->ancestry->isChildRoute()) {
			$this->parent_controller = $this->ancestry->deduceParentController();
		}

		// If a parent controller was found, proceed to find the parent model, parent
		// relationship, and child relationship
		if (!empty($this->parent_controller)) {

			// Determine it's model, so we can call static methods on that model
			if (empty($this->parent_model)) {
				$this->parent_model = $this->model($this->parent_controller);
			}

			// Figure out what the relationship function to the child (this controller's
			// model) on the parent model
			if (empty($this->parent_to_self) && $this->parent_model) {
				$this->parent_to_self = $this->ancestry->deduceParentRelationship();
			}

			// If the parent is the same as this controller, assume that it's a many-to-many-to-self
			// relationship.  Thus, expect a relationship method to be defined on the model
			// called "RELATIONSHIPAsChild".  I.e. "postsAsChild"
			if ($this->parent_controller == $this->controller && method_exists($this->model, $this->parent_to_self.'AsChild')) {
				$this->self_to_parent = $this->parent_to_self.'AsChild';

			// Figure out the child relationship name, which is typically named the same
			// as the parent model
			} else if (empty($this->self_to_parent) && $this->parent_model) {
				$this->self_to_parent = $this->ancestry->deduceChildRelationship();
			}
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
	 * Figure out the model for a class
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
	 * Return parent model
	 * @return string ex: "Article"
	 */
	public function parentModel() { return $this->parent_model; }
	
	/**
	 * Return controller
	 * @return string ex: Admin\SlidesController
	 */
	public function controller() { return $this->controller; }
	
	/**
	 * Get parent controller
	 * @return string ex: Admin\ArticlesController
	 */
	public function parentController() { return $this->parent_controller; }
	
	/**
	 * Get $self_to_parent relationship name
	 * @return string ex: article
	 */
	public function selfToParent() {
		return $this->self_to_parent;
	}
	
	/**
	 * Pass along this request on ancestry, so we can keep that private for now
	 * @return boolean
	 */
	public function isChildInManyToMany() {
		return $this->ancestry->isChildInManyToMany();
	}
	
	//---------------------------------------------------------------------------
	// Basic CRUD methods
	//---------------------------------------------------------------------------
	
	// Listing page
	public function index() {
		
		// Run the query
		$search = new Search();
		$results = $search->apply(Model::ordered(), $this->search)->paginate($this->perPage());
		$count = $results->getTotal();
		
		// Render the view.  We can assume that Model has an ordered() function
		// because it's defined on Decoy's Base_Model
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			'count'            => $count,
			'listing'          => $results,
			'columns'          => $this->columns,
			'search'           => $this->search,
		));
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
	}
	
	// List page when the view is for a child in a related sense
	public function indexChild() {

		// Make sure the parent is valid
		$parent_id = $this->ancestry->parentId();
		if (!($parent = self::parentFind($parent_id))) return App::abort(404);
			
		// Get the list of rows
		$query = $parent->{$this->parent_to_self}()->ordered();

		// Run the query
		$search = new Search();
		$results = $search->apply($query, $this->search)->paginate($this->perPage());
		$count = $results->getTotal();

		// Render the view
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			'count'            => $count,
			'listing'          => $results,
			'columns'          => $this->columns,
			'parent_id'        => $parent_id,
			'search'           => $this->search,
		));
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
		
	}
	
	/**
	 * Create form
	 */
	public function create() {
		
		// If there is a parent, make sure it's id is valid
		if ($parent_id = $this->ancestry->parentId()) {
			if (!($parent = self::parentFind($parent_id))) return App::abort(404);
		}

		// Pass validation through
		Former::withRules(Model::$rules);
		
		// Return view
		$this->layout->nest('content', $this->show_view, array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			
			// Will never be used in a "new" view, but will keep errors from being thrown 
			// about "undefined property"
			'crops'            => (object) Model::$crops,
		));
		
		// Pass parent_id
		if (isset($parent_id)) $this->layout->content->parent_id = $parent_id;
		
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
		if ($result = $this->validate(Model::$rules, $item)) return $result;

		// Save it.  We don't save through relations becaue the foreign keys were manually
		// set previously.  And many to many relationships are not formed during a store().
		$item->save();

		// Update Decoy::manyToManyChecklist() relationships
		$many_to_many_checklist = new ManyToManyChecklist();
		$many_to_many_checklist->update($item);
		
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
		$position = new Position($item, $this->self_to_parent);
		if ($position->has()) $position->fill(); 
		
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
		if ($result = $this->validate(Model::$rules, $item)) return $result;

		// Save the record
		$item->save();

		// Update Decoy::manyToManyChecklist() relationships
		$many_to_many_checklist = new ManyToManyChecklist();
		$many_to_many_checklist->update($item);

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
			
			// Require parent_id
			if (!Input::has('parent_id')) throw new Exception('You must pass the parent id');
			$parent_id = Input::get('parent_id');
			$parent = $this->parentFind($parent_id);
			
			// See if there is an exact match on what's been entered.  This is useful for many
			// to manys with tags because we want to know if the reason that autocomplete
			// returns no results on an exact match that is already attached is because it
			// already exists.  Otherwise, it would allow the user to create the tag
			if ($parent->{$this->parent_to_self}()
				->where(Model::$title_column, '=', Input::get('query'))
				->count()) {
				return Response::json(array('exists' => true));
			}
			
			// Get the ids of already attached rows through the relationship function.  There
			// are ways to do just in SQL but then we lose the ability for the relationship
			// function to apply conditions, like is done in polymoprhic relationships.
			$siblings = $parent->{$this->parent_to_self}()->get();
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
		if (!Input::has('parent_id')) return Response::json(null, 404);
		if (!($item = Model::find($id))) return Response::json(null, 404);
		
		// Do the attach
		$this->fireEvent('attaching', array($item));
		$item->{$this->self_to_parent}()->attach(Input::get('parent_id'));
		$this->fireEvent('attached', array($item));
		
		// Return the response
		return Response::json('ok');
		
	}
	
	// Remove a relationship.  Very similar to delete, except that we're
	// not actually deleting from the database
	public function remove($id) {

		// Support removing many ids at once
		$ids = Input::has('ids') ? explode(',', Input::get('ids')) : array($id);
		
		// Get the parent id
		$parent_id = Input::get('parent_id');
		
		// Lookup up the parent model so we can bulk remove multiple of THIS model
		$item = $this->parentFind($parent_id);
		$this->fireEvent('removing', array($item, $ids));
		$item->{$this->parent_to_self}()->detach($ids);
		$this->fireEvent('removed', array($item, $ids));
		
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
	 * @param array $rules    A typical rules array
	 * @param Bkwld\Decoy\Model\Base $model The model instance that is being worked on
	 * @param array $messages Special error messages
	 */
	protected function validate($rules, $model = null, $messages = array()) {
		
		// Pull the input including files
		$input = Input::all();

		// If a model instance was passed, merge the input on top of that.  This allows
		// data that may already be set on the model to be validated.  The input will override
		// anything already set on the model.  In particular, this is done so that auto
		// generated fields like the slug can be validated.
		if ($model) $input = array_merge($model->getAttributes(), $input);

		// If an AJAX update, don't require all fields to be present. Pass
		// just the keys of the input to the array_only function to filter
		// the rules list.
		if (Request::ajax() && Request::getMethod() == 'PUT') {
			$rules = array_only($rules, array_keys($input));
		}

		// Fire event
		if ($response = $this->fireEvent('validating', array($model, $input), true)) {
			if (is_a($response, 'Symfony\Component\HttpFoundation\Response')) return $response;
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
	
	// Run the find method on the parent model
	protected function parentFind($parent_id) {
		if (empty($this->parent_model)) return false;
		return call_user_func($this->parent_model.'::find', $parent_id);
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
		$per_page = Input::get('count', self::PER_PAGE);
		if ($per_page == 'all') return 1000;
		return $per_page;
	}

	// Get all the foreign keys and values on the relationship with the parent
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
		if ($this->parent_to_self
			&& ($parent_id = $this->ancestry->parentId()) 
			&& ($parent = self::parentFind($parent_id))) {
			return $parent->{$this->parent_to_self}();
		} else return false;
	}
	
}

/*


abstract class Decoy_Base_Controller extends Controller {
	
	
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------

	// Processing function for handling sorting when the position column is on pivot table.  As
	// would be the case in all many to manys.
	protected function update_pivot_position($id) {
		
		// If there is not a PUT request with a property of position, return false
		// to tell the invoker to continue processing
		if (Request::getMethod() != 'PUT' || !Request::ajax()) return false;
		$input = Input::get();
		if (!isset($input['position'])) return false;
		
		// Update the pivot position
		list($table) = $this->pivot();
		DB::table($table)
			->where('id', '=', $id)
			->update(array('position' => $input['position']));
		
		// Return success
		return Response::json('null');
		
	}


	//---------------------------------------------------------------------------
	// Private support methods
	//---------------------------------------------------------------------------
	
	
	// Get the pivot table name and the child foreign key (the active Model) is probably
	// the child, and the parent foreign key column name
	private function pivot() {
		
		// If the request doesn't know it's child of another class (often because an exeption)
		// this won't work
		if (empty($this->self_to_parent)) throw new Exception('Empty self to parent relationship in pivot');
		if (empty($this->parent_to_self)) throw new Exception('Empty parent to self relationship in pivot');
		
		// Lookup the table and column
		$listing_instance = new Model;
		$parent_instance = new $this->parent_model;
		$pivot_table = $listing_instance->{$this->self_to_parent}()->pivot()->model->table();
		$pivot_child_foreign = $pivot_table.'.'.$listing_instance->{$this->self_to_parent}()->foreign_key();
		$pivot_parent_foreign = $pivot_table.'.'.$parent_instance->{$this->parent_to_self}()->foreign_key();
		return array($pivot_table, $pivot_child_foreign, $pivot_parent_foreign);
	}
	
	// Get the id column of the model
	private function child_key() {
		$listing_instance = new Model;
		return $listing_instance->table().'.'.$listing_instance::$key;
	}

}
*/