<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Routing\Ancestry;
use Bkwld\Library;
use Event;
use Illuminate\Routing\Controllers\Controller;
use Illuminate\Support\Str;
use Input;
use Redirect;
use Request;
use Response;
use URL;
use Validator;

/**
 * The base controller is gives Decoy most of the magic/for-free mojo
 */
abstract class Base extends Controller {
	
	//---------------------------------------------------------------------------
	// Default settings
	//---------------------------------------------------------------------------
	
	// Default pagination settings
	protected $PER_PAGE = 20;
	const PER_PAGE_SIDEBAR = 6;
	
	// Values that get shared by many controller methods.  Default values for these
	// get set in the constructor.
	protected $MODEL;       // i.e. Post
	protected $CONTROLLER;  // i.e. Admin\posts
	protected $TITLE;       // i.e. News Posts
	protected $DESCRIPTION; // i.e. Relevant news about the brand
	protected $COLUMNS = array('Title' => 'title'); // The default columns for listings
	protected $SHOW_VIEW;   // i.e. admin.news.show
	protected $SEARCH;      // i.e. An array describing the fields to search upon
	
	// More of the same, but these are just involved in relationships
	protected $PARENT_MODEL;      // i.e. Photo
	protected $PARENT_CONTROLLER; // i.e. admin.photos
	protected $PARENT_TO_SELF;    // i.e. photos
	protected $SELF_TO_PARENT;    // i.e. post
	
	// Shared layout for admin view, set in the constructor
	public $layout;
	protected function setupLayout() {
		if (!is_null($this->layout)) $this->layout = \View::make($this->layout);
	}
	
	/**
	 * For the most part, this populates the protected properties
	 */
	public function __construct() {

		// Set the layout from the Config file
		$this->layout = App::make('config')->get('decoy::layout');
		
		// Store the controller class for routing
		if (empty($this->CONTROLLER)) $this->CONTROLLER = get_class($this);
		
		// Get the controller name
		$controller_name = $this->controllerName($this->CONTROLLER);
		
		// Make a default title based on the controller name
		if (empty($this->TITLE)) $this->TITLE = $this->title($controller_name);
		
		// Figure out what the show view should be.  This is the path to the show view file.  Such
		// as 'admin.news.edit'
		if (empty($this->SHOW_VIEW)) $this->SHOW_VIEW = $this->detailPath($this->CONTROLLER);
		
		// Try to suss out the model by singularizing the controller
		if (empty($this->MODEL)) {
			$this->MODEL = $this->model($this->CONTROLLER);
			if (!class_exists($this->MODEL)) $this->MODEL = NULL;
		}
		
		// This allows us to refer to the default model for a controller using the
		// generic term of "Model"
		if ($this->MODEL && !class_exists('Model')) {
			if (!class_alias($this->MODEL, 'Model')) throw new Exception('Class alias failed');
		}
		
		// Use Ancestry class to help figure out where this controller stands in relation to
		// others in the request path
		$ancestry = new Ancestry($this);
		
		// Continue processing
		parent::__construct();
		
	}
	
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
		if (!$controller_name) return $this->TITLE; // For when this is invoked as a getter for $this->TITLE
		preg_match_all('#[a-z]+|[A-Z][a-z]*#', $controller_name, $matches);
		return implode(" ", $matches[0]);
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
	 * @param string $class ex: 'Admin\NewsController'
	 */
	public function model($class) {
		if ($this->MODEL) return $this->MODEL; // for getters
		
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
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	// All actions validate in basically the same way.  This is
	// shared logic for that
	/**
	 * Shared validation helper
	 * @param $array rules    A typical rules array
	 * @param $array messages Special error messages
	 */
	protected function validate($rules, $messages = array()) {
		
		// Pull the input from the proper place
		$input = Input::all();
		
		// If an AJAX update, don't require all fields to be present. Pass
		// just the keys of the input to the array_only function to filter
		// the rules list.
		if (Request::ajax() && Request::method() == 'PUT') {
			$rules = array_only($rules, array_keys($input));
		}
		
		// Add messages from BKWLD bundle
		$messages = array_merge(\Bkwld\Library\Laravel\Validator::messages(), $messages);

		// Fire event
		if ($response = $this->fireEvent('validating', array($input), true)) {
			if (is_a($response, '\Response')) return $response;
		}
		
		// Validate
		$validation = Validator::make($input, $rules, $messages);
		if ($validation->fails()) {
			if (Request::ajax()) {
				return Response::json($validation->errors, 400);
			} else {
				return Redirect::to(URL::current())
					->withErrors($validation)
					->withInput();
			}
		}
		
		// Fire event
		$this->fireEvent('validated', array($input));
		
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
		if (!empty($this->MODEL)) $events[] = "decoy.{$event}: ".$this->MODEL;
		
		// Fire them
		foreach($events as $event) {
			if ($until) return Event::until($event, $args);
			else Event::fire($event, $args);
		}
	}
	
	//---------------------------------------------------------------------------
	// Private support methods
	//---------------------------------------------------------------------------
	

	
}

/*


abstract class Decoy_Base_Controller extends Controller {
	
	//---------------------------------------------------------------------------
	// Default settings
	//---------------------------------------------------------------------------

	
	
	// Special constructor behaviors
	function __construct() {

	

		
		// If the current route has a parent, discover what it is
		if (empty($this->PARENT_CONTROLLER) && $this->isChildRoute()) {
			$this->PARENT_CONTROLLER = $this->deduceParentController();
		}
		
		// If a parent controller was found, proceed to find the parent model, parent
		// relationship, and child relationship
		if (!empty($this->PARENT_CONTROLLER)) {
			
			// Instantiate the controller class if different from the current one.  They would be the same
			// in the case of a relationship for related models.  Like if the site required "projects" to have
			// a list of related projects, there would be a many-to-many back to oneself.  Because we're only
			// instatiating the class here to get values set in the construtor, we can use ourself.
			if ($this->PARENT_CONTROLLER == $this->CONTROLLER) $parent_controller_instance = $this;
			else $parent_controller_instance = Controller::resolve(DEFAULT_BUNDLE, $this->PARENT_CONTROLLER);
			
			// Determine it's model, so we can call static methods on that model
			if (empty($this->PARENT_MODEL)) {
				$this->PARENT_MODEL = $parent_controller_instance->MODEL;
			}

			// Figure out what the relationship function to the child (this controller's
			// model) on the parent model
			if (empty($this->PARENT_TO_SELF) && $this->PARENT_MODEL) {
				$this->PARENT_TO_SELF = $this->deduceParentRelationship();
			}
			
			// Figure out the child relationship name, which is typically named the same
			// as the parent model
			if (empty($this->SELF_TO_PARENT) && $this->PARENT_MODEL) {
				$this->SELF_TO_PARENT = $this->deduceChildRelationship();
			}
		}
		
	}
	
	//---------------------------------------------------------------------------
	// Getter/setter
	//---------------------------------------------------------------------------
	
	// Get access to protected properties
	public function model() { return $this->MODEL; }
	public function parent_controller() { return $this->PARENT_CONTROLLER; }
	public function controller() { return $this->CONTROLLER; }
	public function title() { return $this->TITLE; }
	
	//---------------------------------------------------------------------------
	// Basic CRUD methods
	//---------------------------------------------------------------------------
	
	// Listing page
	public function get_index() {
		
		// Run the query
		$results = Decoy\Search::apply(Model::ordered(), $this->SEARCH)->paginate($this->PER_PAGE);
		$count = $results->total;
		
		// Render the view.  We can assume that Model has an ordered() function
		// because it's defined on Decoy's Base_Model
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'count'            => $count,
			'listing'          => $results,
			'columns'          => $this->COLUMNS,
			'search'           => $this->SEARCH,
		));
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Decoy\Breadcrumbs::generate_from_url());
	}	
	
	// List page when the view is for a child in a related sense
	public function get_index_child($parent_id) {

		// Make sure the parent is valid
		if (!($parent = self::parent_find($parent_id))) return Response::error('404');
			
		// Form the query by manually making adding the where condition with the 
		// parent foreign key.  We do this instead of using Laravel's syntax
		// ($parent->{$this->PARENT_TO_SELF}()) so that we can call the
		// ordered() static method
		$foreign_key = $parent->{$this->PARENT_TO_SELF}()->foreign_key();
		$query = Model::ordered()->where($foreign_key, '=', $parent_id);

		// If it's a many to many, we need to join the pivot table because that is
		// what the foreign key is on.  This is done just so we can get the pivot_id
		// for doing things like delete_remove().  But this is, again, only required
		// because of trying to support ordered() in the listing
		if ($this->isChildInManyToMany()) {
			
			// Get references to the listing and pivot tables so we can get the table name
			// and key column names from them.  SELF_TO_PARENT is used because the instance
			// we get with new Model is a child of another model.  So we are trying to get back to
			// our parent, and we do that with SELF_TO_PARENT, which is the reference declared
			// ON the child.
			$child_key = $this->child_key();
			list($pivot_table, $pivot_child_foreign) = $this->pivot();
			
			// Add the join to the pivot table and make the id columns explicit
			$query = $query->join($pivot_table, $child_key, '=', $pivot_child_foreign)
				->select(array('*', $child_key.' AS id', $pivot_table.'.id AS pivot_id'));
		}

		// Run the query
		$results = Decoy\Search::apply($query, $this->SEARCH)->paginate($this->PER_PAGE);
		$count = $results->total;

		// Render the view
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'count'            => $count,
			'listing'          => $results,
			'columns'          => $this->COLUMNS,
			'parent_id'        => $parent_id,
			'search'           => $this->SEARCH,
		));
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Decoy\Breadcrumbs::generate_from_url());
		
	}
	
	// List as JSON for autocomplete widgets
	public function get_autocomplete() {
		
		// Do nothing if the query is too short
		if (strlen(Input::get('query')) < 1) return Response::json(null);
		
		// Get data matching the query
		if (empty(Model::$TITLE_COLUMN)) throw new Exception($this->MODEL.'::$TITLE_COLUMN must be defined');
		$query = Model::ordered()->where(Model::$TITLE_COLUMN, 'LIKE', '%'.Input::get('query').'%');
		
		// Don't return any rows already attached to the parent.  So make sure the id is not already
		// in the pivot table for the parent
		if ($this->isChildInManyToMany()) {
			
			// Require parent_id
			if (!Input::has('parent_id')) throw new Exception('You must pass the parent id');
			$parent_id = Input::get('parent_id');
			$parent = $this->parent_find($parent_id);
			
			// See if there is an exact match on what's been entered.  This is useful for many
			// to manys with tags because we want to know if the reason that autocomplete
			// returns no results on an exact match that is already attached is because it
			// already exists.  Otherwise, it would allow the user to create the tag
			if ($parent->{$this->PARENT_TO_SELF}()
				->where(Model::$TITLE_COLUMN, '=', Input::get('query'))
				->count()) {
				return Response::json(array('exists' => true));
			}
			
			// Get the ids of already attached rows through the relationship function.  There
			// are ways to do just in SQL but then we lose the ability for the relationship
			// function to apply conditions, like is done in polymoprhic relationships.
			// $parent = $this->parent_find($parent_id);
			$siblings = $parent->{$this->PARENT_TO_SELF}()->get();
			if (count($siblings)) {
				$sibling_ids = array();
				foreach($siblings as $sibling) $sibling_ids[] = $sibling->id;	
				
				// Add condition to query
				$parent_id = DB::connection()->pdo->quote($parent_id);
				$query = $query->where_not_in('id', $sibling_ids);
			}
		}
		
		// Return result
		return Response::json($this->format_autocomplete_response($query->get()));
		
	}
	
	// Create form
	public function get_new() {
		
		// There may be a parent id.  This isn't defined in the function definition
		// because I don't want all child classes to have to implement it
		if (is_numeric(Request::segment(3))) {
			$parent_id = Request::segment(3);
			if (!($parent = self::parent_find($parent_id))) return Response::error('404');
		}

		// Pass validation through
		Former::withRules(Model::$rules);
		
		// Return view
		$this->layout->nest('content', $this->SHOW_VIEW, array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			
			// Will never be used in a 'new' view, but will keep errors from being thrown about undfined property
			'crops'            => (object) Model::$CROPS,
		));
		
		// Pass parent_id
		if (isset($parent_id)) $this->layout->content->parent_id = $parent_id;
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Decoy\Breadcrumbs::generate_from_url());
	}
	
	// Create a new one
	public function post_new() {
		
		// There may be a parent id.  This isn't defined in the function definition
		// because I don't want all child classes to have to implement it
		if (is_numeric(Request::segment(3))) {
			$parent_id = Request::segment(3);
			if (!($parent = self::parent_find($parent_id))) return Response::error('404');
		}
		
		// Create default slug
		$this->merge_default_slug();
		
		// Create a new object before validation so that model callbacks on validation
		// get fired
		$item = new Model();
		
		// Validate
		if ($result = $this->validate(Model::$rules)) return $result;

		// Hydrate the model
		$item->fill(BKWLD\Utils\Collection::null_empties(Input::get()));
		self::save_files($item);
		
		// Save it
		if (!empty($parent_id)) {
			$query = $parent->{$this->PARENT_TO_SELF}()->insert($item);
		} else $item->save();
		
		// Redirect to edit view
		if (Request::ajax()) return Response::json(array('id' => $item->id));
		else return Redirect::to(str_replace('new', $item->id, URI::current()));
	}
	
	// Edit form
	public function get_edit($id) {

		// Get the work
		if (!($item = Model::find($id))) return Response::error('404');

		// Populate form
		Former::populate($item);
		Former::withRules(Model::$rules);
		
		// Render the view
		$this->layout->nest('content', $this->SHOW_VIEW, array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'item'             => $item,
			'crops'            => (object) Model::$CROPS,
		));
		
		// Figure out the parent_id
		if ($this->SELF_TO_PARENT) {
			$parent_id = $item->{$this->SELF_TO_PARENT}()->foreign_value();
			$this->layout->content->parent_id = $parent_id;
		}
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Decoy\Breadcrumbs::generate_from_url());

	}
	
	// Update an item
	public function post_edit($id) { return $this->put_edit($id); }
	public function put_edit($id) {

		// Load the entry
		if (!($item = Model::find($id))) {
			if (Request::ajax()) return Response::json(null, 404);
			else return Response::error('404');
		}
		
		// Files in an edit state have a number of supplentary fields.  This
		// prepares the file for validation.
		self::pre_validate_files();
		
		// Create default slug
		$this->merge_default_slug();

		// Validate data
		if ($result = $this->validate(Model::$rules)) return $result;
		
		// Save out files and remove special file related inputs that don't
		// exist as columns in the db (like 'old-image")
		self::delete_files($item);
		self::save_files($item);
		self::unset_file_edit_inputs();
		
		// Remove special key-value pairs that inform logic but won't ever fill() a db row
		$input = BKWLD\Laravel\Input::json_and_input();
		unset($input['parent_controller']); // Backbone may send this with sort updates

		// Update it
		$item->fill(BKWLD\Utils\Collection::null_empties($input));
		$item->save();

		// Redirect to the edit view
		if (Request::ajax()) return Response::json('null');
		else return Redirect::to(URL::current());
		
	}
	
	// Attach a model to a parent_id, like with a many to many style
	// autocomplete widget
	public function post_attach($id) {
		
		// Require there to be a parent id and a valid id for the resource
		if (!Input::has('parent_id')) return Response::json(null, 404);
		if (!($item = Model::find($id))) return Response::json(null, 404);
		
		// Do the attach
		$this->fireEvent('attaching');
		$item->{$this->SELF_TO_PARENT}()->attach(Input::get('parent_id'));
		
		// Get the new pivot row's id
		$pivot_id = DB::connection('mysql')->pdo->lastInsertId();
		$pivot = $item->{$this->SELF_TO_PARENT}()->pivot()->where('id', '=', $pivot_id)->first();
		$this->fireEvent('attached', array($pivot));
		
		// Return the response
		return Response::json(array(
			'pivot_id' => $pivot_id
		));
		
	}
	
	// Delete one or multiple.  This accepts a dash
	// delimited list of ids.  Commas don't appear to be allowed
	// using simple Laravel routing.
	public function get_delete($ids) { return $this->delete_delete($ids); }
	public function post_delete($ids) { return $this->delete_delete($ids); }
	public function delete_delete($ids) {

		// Look for the mentioned rows
		$ids = explode('-',$ids);
		$items = Model::where_in('id', $ids);
		if (empty($items)) return Response::error('404');
		
		// Delete
		foreach($items->get() as $item) {
			
			// Delete images
			if (!method_exists($item, 'image') && !empty($item->image)) Croppa::delete($item->image);
			
			// Delete row.  These are deleted one at a time so that model events will fire.
			$item->delete();
		}
	
		// If the referrer contains the controller route, that would mean that we're
		// redirecting back to the edit page (which no longer exists).  Thus, go to a
		// listing instead.  Otherwise, go back (accoridng to referrer)
		if (Request::ajax()) return Response::json('null');
		else return Redirect::to(Breadcrumbs::smart_back(Breadcrumbs::defaults(parse_url(Request::referrer(), PHP_URL_PATH))));
	}
	
	// Remove a relationship.  Very similar to delete, except that we're
	// not actually deleting from the database
	public function get_remove($pivot_ids) { return $this->delete_remove($pivot_ids); }
	public function delete_remove($pivot_ids) {

		// Look for the mentioned rows
		$pivot_ids = explode('-', $pivot_ids);
		
		// Loop through each item and delete the relationship
		list($pivot_table) = $this->pivot();
		foreach($pivot_ids as $id) {
			
			// Get the pivot row
			$pivot = DB::table($pivot_table)->find($id);
			$this->fireEvent('removing', array($pivot));
			
			// Remove it
			DB::query("DELETE FROM {$pivot_table} WHERE id = ?", $id);
			$this->fireEvent('removed', array($pivot));
		}
		
		// Redirect.  We can use back cause this is never called from a "show"
		// page like get_delete is.
		if (Request::ajax()) return Response::json('null');
		else return Redirect::back();
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------

	// If there is slug mentioned in the validation or mass assignment rules
	// and an appropriate title-like field, make a slug for it.  Then merge
	// that into the inputs, so it can be validated easily
	protected function merge_default_slug() {
		
		// If we're on an edit view, update the unique condition on the rule
		// (if it exists) to be unique but not for the current row
		if (in_array('slug', array_keys(Model::$rules)) 
			&& strpos(Model::$rules['slug'], 'unique') !== false
			&& Request::route()->controller_action == 'edit') {
			$id = Request::route()->parameters[0];
		
			// Add the row exception to the unique clause.  The regexp works because
			// the \w+ will end at the | that begins the next condition

			// If we're using the unique_with custom validator from the BKWLD bundle
			if (strpos(Model::$rules['slug'], 'unique_with')) {
				Model::$rules['slug'] = preg_replace('#(unique_with:\w+,\w+)(,slug)?#i', 
					'$1,slug,'.$id, 
					Model::$rules['slug']);
				
			// Regular slugs
			} else {
				Model::$rules['slug'] = preg_replace('#(unique:\w+)(,slug)?#', 
					'$1,slug,'.$id, 
					Model::$rules['slug']);
			}
			
		}

		// If a slug is already defined, do nothing
		if (Input::has('slug')) return;
		
		// Model must have rules and they must have a slug
		if (empty(Model::$rules) || !in_array('slug', array_keys(Model::$rules))) return;
		
		// If a Model::$TITLE_COLUMN is set, use that input for the slug
		if (!empty(Model::$TITLE_COLUMN) && Input::has(Model::$TITLE_COLUMN)) {
			Input::merge(array('slug' => Str::slug(Input::get(Model::$TITLE_COLUMN))));
		
		// Else it looks like the model has a slug, so try and set it
		} else if (Input::has('name')) {
			Input::merge(array('slug' => Str::slug(Input::get('name'))));
		} elseif (Input::has('title')) {
			Input::merge(array('slug' => Str::slug(Input::get('title'))));
		}
	}
	
	// Convert an eloquent result set into an array
	static protected function eloquent_to_array($query) {
		return array_map(function($m) { return $m->to_array(); }, $query);
	}
	
	// Run the find method on the parent model
	protected function parent_find($parent_id) {
		if (empty($this->PARENT_MODEL)) return false;
		return call_user_func($this->PARENT_MODEL.'::find', $parent_id);
	}
	
	// Processing function for handling sorting when the position column is on pivot table.  As
	// would be the case in all many to manys.
	protected function update_pivot_position($id) {
		
		// If there is not a PUT request with a property of position, return false
		// to tell the invoker to continue processing
		if (Request::method() != 'PUT' || !Request::ajax()) return false;
		$input = BKWLD\Laravel\Input::json_and_input();
		if (!isset($input['position'])) return false;
		
		// Update the pivot position
		list($table) = $this->pivot();
		DB::table($table)
			->where('id', '=', $id)
			->update(array('position' => $input['position']));
		
		// Return success
		return Response::json('null');
		
	}
	
	// Format the results of a query in the format needed for the autocomplete repsonses
	public function format_autocomplete_response($results) {
		$output = array();
		foreach($results as $row) {
			
			// Only keep the id and title fields
			$item = new stdClass;
			$item->id = $row->id;
			$item->title = $row->{Model::$TITLE_COLUMN};
			
			// Add properties for the columns mentioned in the list view within the
			// 'columns' property of this row in the response.  Use the same logic
			// found in Html::render_list_column();
			$item->columns = array();
			foreach($this->COLUMNS as $column) {
				if (method_exists($row, $column)) $item->columns[$column] = call_user_func(array($row, $column));
				elseif (isset($row->$column)) $item->columns[$column] = $row->$column;
				else $item->columns[$column] = null;
			}
			
			// Add the item to the output
			$output[] = $item;
		}
		return $output;
	}


	//---------------------------------------------------------------------------
	// Private support methods
	//---------------------------------------------------------------------------
	
	
	// Get the pivot table name and the child foreign key (the active Model) is probably
	// the child, and the parent foreign key column name
	private function pivot() {
		
		// If the request doesn't know it's child of another class (often because an exeption)
		// this won't work
		if (empty($this->SELF_TO_PARENT)) throw new Exception('Empty self to parent relationship in pivot');
		if (empty($this->PARENT_TO_SELF)) throw new Exception('Empty parent to self relationship in pivot');
		
		// Lookup the table and column
		$listing_instance = new Model;
		$parent_instance = new $this->PARENT_MODEL;
		$pivot_table = $listing_instance->{$this->SELF_TO_PARENT}()->pivot()->model->table();
		$pivot_child_foreign = $pivot_table.'.'.$listing_instance->{$this->SELF_TO_PARENT}()->foreign_key();
		$pivot_parent_foreign = $pivot_table.'.'.$parent_instance->{$this->PARENT_TO_SELF}()->foreign_key();
		return array($pivot_table, $pivot_child_foreign, $pivot_parent_foreign);
	}
	
	// Get the id column of the model
	private function child_key() {
		$listing_instance = new Model;
		return $listing_instance->table().'.'.$listing_instance::$key;
	}

}
*/