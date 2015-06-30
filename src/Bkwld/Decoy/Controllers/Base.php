<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Breadcrumbs;
use Bkwld\Decoy\Exceptions\Exception;
use Bkwld\Decoy\Exceptions\ValidationFail;
use Bkwld\Decoy\Fields\Listing;
use Bkwld\Decoy\Input\Localize;
use Bkwld\Decoy\Input\Position;
use Bkwld\Decoy\Input\Sidebar;
use Bkwld\Decoy\Input\Search;
use Bkwld\Decoy\Routing\Wildcard;
use Bkwld\Library;
use Bkwld\Library\Utils\File;
use Config;
use Croppa;
use DB;
use Decoy;
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
use View;

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
		$this->layout = $this->config->get('decoy::core.layout');
		
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
		// the parent instance.  These are populated by some AJAX calls like autocomplete
		// on a many to many and the attach method.
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
		$wildcard = new Wildcard($this->config->get('decoy::core.dir'), $verb, $path);
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
	 * Figure out the model for a controller class or return the current model class
	 * 
	 * @param string $class ex: "Admin\SlidesController"
	 * @return string ex: "Slide"
	 */
	public function model($class = null) {
		if ($class) return Decoy::modelForController($class);
		return $this->model;
	}
	
	/**
	 * Give this controller a parent model instance.  For instance, this makes the index
	 * view a listing of just the children of the parent.
	 *
	 * @param Illuminate\Database\Eloquent\Model $parent
	 * @return this 
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

		// If the parent relationship is a polymorphic one-many, then the relationship function
		// on the child model will be the model name plus "able".  For instance, the Link model would
		// have it's relationship to parent called "linkable".
		} elseif (is_a($this->parentRelation(), 'Illuminate\Database\Eloquent\Relations\MorphMany')) {
			$this->self_to_parent = lcfirst($this->model).'able';

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
	 * Determine whether the relationship between the parent to this controller
	 * is a many to many
	 * 
	 * @return boolean
	 */
	public function isChildInManyToMany() {
		return is_a($this->parentRelation(), 
			'Illuminate\Database\Eloquent\Relations\BelongsToMany');

	}

	/**
	 * Get the permission options for the controller.  By default, these are the
	 * stanadard CRUD actions
	 *
	 * @return array An associative array of where the values are descriptions
	 */
	public function getPermissionOptions() {
		return [
			'read' => 'View listing and edit views',
			'create' => 'Create new items',
			'update' => 'Update existing items',
			'publish' => 'Move from "draft" to "published"',
			'destroy' => 'Delete items permanently',
		];
	}
	
	//---------------------------------------------------------------------------
	// Basic CRUD methods
	//---------------------------------------------------------------------------
	
	/**
	 * Show an index, listing page.  Sets view via the layout.
	 * 
	 * @return void
	 */
	public function index() {

		// Look for overriden views
		$this->overrideViews();
		
		// Open up the query. We can assume that Model has an ordered() function
		// because it's defined on Decoy's Base_Model.
		$query = $this->parent ? $this->parentRelation()->ordered() : Model::ordered();

		// Run the query. 
		$search = new Search();
		$results = $search->apply($query, $this->search())->paginate($this->perPage());
		
		// Render the view using the `listing` builder.
		$listing = Listing::createFromController($this, $results);
		if ($this->parent) {
			$listing->parent($this->parent);

			// The layout header may have a many to many autocomplete
			$this->layout->with($this->autocompleteViewVars());
		}

		// Render view
		$this->populateView($listing);
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
	}
	
	/**
	 * Show the create form.  Sets view via the layout.
	 * 
	 * @return void
	 */
	public function create() {

		// Look for overriden views
		$this->overrideViews();

		// Pass validation through
		Former::withRules(Model::$rules);

		// Initialize localization
		with($localize = new Localize)
			->model($this->model)
			->title(Str::singular($this->title));

		// Make the sidebar
		$sidebar = new Sidebar;
		if (!$localize->hidden()) $sidebar->addToEnd($localize);

		// Return view
		$this->populateView($this->show_view, [
			'item' => null,
			'localize' => $localize,
			'sidebar' => $sidebar,
			'crops' => (object) Model::$crops,
		]);
		
		// Pass parent_id
		if ($this->parent) $this->layout->content->parent_id = $this->parent->getKey();
		
		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());
	}
	
	/**
	 * Store a new record
	 * 
	 * @return Symfony\Component\HttpFoundation\Response Redirect to edit view
	 */
	public function store() {
		
		// Create a new object and hydrate
		$item = new Model();
		$item->fill(Library\Utils\Collection::nullEmpties(Input::get()));

		// Validate and save.
		$this->validate($item);
		if ($this->parent) $this->parent->{$this->parent_to_self}()->save($item);
		else $item->save();
		
		// Redirect to edit view
		if (Request::ajax()) return Response::json(array('id' => $item->id));
		else return Redirect::to($this->url->relative('edit', $item->id))
			->with('success', $this->successMessage($item, 'created') );
	}
	
	/**
	 * Show the edit form.  Sets view via the layout.
	 * 
	 * @param  int $id Model key
	 * @return void
	 */
	public function edit($id) {

		// Get the model instance
		if (!($item = Model::find($id))) return App::abort(404);

		// Look for overriden views
		$this->overrideViews();

		// Populate form
		Former::populate($item);
		Former::withRules(Model::$rules);

		// Initialize localization
		with($localize = new Localize)
			->item($item)
			->title(Str::singular($this->title));

		// Make the sidebar
		$sidebar = new Sidebar($item);
		if (!$localize->hidden()) $sidebar->addToEnd($localize);

		// Render the view
		$this->populateView($this->show_view, [
			'item' => $item,
			'localize' => $localize,
			'sidebar' => $sidebar,
			'crops' => (object) Model::$crops,
		]);

		// Figure out the parent_id
		if ($this->parent) $this->layout->content->parent_id = $this->parent->getKey();

		// Inform the breadcrumbs
		$this->breadcrumbs(Breadcrumbs::fromUrl());

	}
	
	/**
	 * Update a record
	 * 
	 * @param  int $id Model key
	 * @return Symfony\Component\HttpFoundation\Response Redirect to edit view
	 */
	public function update($id) {

		// Get the model instance
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
			if (isset($item::$rules['slug'])) {
				$pattern = '#(unique:\w+)(,slug)?(,(NULL|\d+))?#';
				$item::$rules['slug'] = preg_replace($pattern, '$1,slug,'.$id, $item::$rules['slug']);
			}
		}

		// Save the record
		$this->validate($item);
		$item->save();

		// Redirect to the edit view
		if (Request::ajax()) return Response::json('ok');
		else return Redirect::to(URL::current())
			->with('success', $this->successMessage($item) );
		
	}
	
	/**
	 * Destroy a record
	 * 
	 * @param  int $id Model key
	 * @return Symfony\Component\HttpFoundation\Response Redirect to listing
	 */
	public function destroy($id) {
		
		// Find the item
		if (!($item = Model::find($id))) return App::abort(404);
		
		// Delete row (this should trigger file attachment deletes as well)
		$item->delete();
	
		// As long as not an ajax request, go back to the parent directory of the referrer
		if (Request::ajax()) return Response::json('ok');
		else return Redirect::to($this->url->relative('index'))
			->with('success', $this->successMessage($item, 'deleted') );
	}

	/**
	 * Duplicate a record
	 *
	 * @param  int $id Model key
	 * @return Symfony\Component\HttpFoundation\Response Redirect to new record
	 */
	public function duplicate($id) {

		// Find the source item
		if (!($src = Model::find($id))) return App::abort(404);
		$file_attributes = $src->file_attributes;

		// Get the options
		$options = Input::get('options') ?: [];

		// If inlcuding text, start by cloning the model.  A number of columns are
		// excepted by the duplciate, including all of the file attributes.
		if (in_array('text', $options)) {
			$new = $src->replicate(array_merge([
				$src->getKeyName(),
				$src->getCreatedAtColumn(),
				$src->getUpdatedAtColumn(),
				'visible',
			], $file_attributes));
		
		// Otherwise, create a blank instance of the model
		} else $new = $src->newInstance();

		// Duplicate all files and associate new paths
		if (in_array('files', $options)) {
			$uploads_dir = Config::get('decoy::core.upload_dir');
			foreach ($file_attributes as $field) {
				if (empty($src->$field)) continue;

				// Copy the file
				$tmp = $uploads_dir.'/'.basename($src->$field);
				$copy = copy(public_path().$src->$field, $tmp);

				// Move it save the new path
				$new->$field = File::publicPath(File::organizeFile($tmp, $uploads_dir));
			}
		}

		// Set localization options on new instance
		if ($locale = Input::get('locale')) {
			$new->locale = $locale;
			if (isset($src->locale_group)) $new->locale_group = $src->locale_group;
		}

		// Save the new record and redirect to its edit view
		$new->save();
		return Redirect::to($this->url->relative('edit', $new->getKey()))
			->with('success', $this->successMessage($new, 'copied') );
	}
	
	//---------------------------------------------------------------------------
	// Many To Many CRUD
	//---------------------------------------------------------------------------
	
	/**
	 * List as JSON for autocomplete widgets
	 * 
	 * @return Illuminate\Http\Response JSON
	 */
	public function autocomplete() {
		
		// Do nothing if the query is too short
		if (strlen(Input::get('query')) < 1) return Response::json(null);
		
		// Get an instance so the title attributes can be found.  If none are found,
		// then there are no results, so bounce
		if (!$model = Model::first()) {
			return Response::json($this->formatAutocompleteResponse([]));
		}

		// Get data matching the query
		$query = Model::titleContains(Input::get('query'))
			->ordered()
			->take(15); // Note, this is also enforced in the autocomplete.js
		
		// Don't return any rows already attached to the parent.  So make sure the 
		// id is not already in the pivot table for the parent
		if ($this->isChildInManyToMany()) {
			
			// See if there is an exact match on what's been entered.  This is useful 
			// for many to manys with tags because we want to know if the reason that 
			// autocomplete returns no results on an exact match that is already 
			// attached is because it already exists.  Otherwise, it would allow the 
			// user to create the tag
			if ($this->parentRelation()
				->titleContains(Input::get('query'), true)
				->count()) {
				return Response::json(array('exists' => true));
			}
			
			// Get the ids of already attached rows through the relationship function.  
			// There are ways to do just in SQL but then we lose the ability for the 
			// relationship function to apply conditions, like is done in polymoprhic 
			// relationships.
			$siblings = $this->parentRelation()->get();
			if (count($siblings)) {
				$sibling_ids = array();
				foreach($siblings as $sibling) $sibling_ids[] = $sibling->id;	
				
				// Add condition to query
				$model = new Model;
				$query = $query->whereNotIn($model->getQualifiedKeyName(), $sibling_ids);
			}
		}
		
		// Return result
		return Response::json($this->formatAutocompleteResponse($query->get()));
		
	}

	/**
	 * Return key-val pairs needed for view partials related to many to many
	 * autocompletes
	 *
	 * @return array key-val pairs
	 */
	public function autocompleteViewVars() {
		if (!$this->parent) return [];
		$parent_controller = new $this->parent_controller;
		return [
			'parent_id' => $this->parent->getKey(),
			'parent_controller' => $this->parent_controller,
			'parent_controller_title' => $parent_controller->title(),
			'parent_controller_description' => $parent_controller->description(),
			'many_to_many' => $this->isChildInManyToMany(),
		];
	}
	
	/**
	 * Attach a model to a parent_id, like with a many to many style
	 * autocomplete widget
	 * 
	 * @param int $id The id of the parent model
	 * @return Illuminate\Http\Response JSON
	 */
	public function attach($id) {
		
		// Require there to be a parent id and a valid id for the resource
		if (!($item = Model::find($id))) return Response::json(null, 404);
		
		// Do the attach
		$this->fireEvent('attaching', array($item, $this->parent));
		$item->{$this->self_to_parent}()->attach($this->parent);
		$this->fireEvent('attached', array($item, $this->parent));
		
		// Return the response
		return Response::json('ok');
		
	}
	
	/**
	 * Remove a relationship.  Very similar to delete, except that we're
	 * not actually deleting from the database
	 * @param  mixed $id One or many (commaa delimited) parent ids
	 * @return Illuminate\Http\Response Either a JSON or Redirect response
	 */
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
	
	// 
	/**
	 * All actions validate in basically the same way.  This is shared logic for that
	 * 
	 * @param Bkwld\Decoy\Model\Base $model The model instance that is being worked on
	 * @param array A Laravel rules array. If null, will be pulled from model
	 * @param array $messages Special error messages
	 * @throws Bkwld\Decoy\Exception\ValidationFail
	 * @return void
	 */
	protected function validate($model = null, $rules = null, $messages = array()) {

		// Pull the input including files.  We manually merge files in because 
		// Laravel's Input::all()	does a recursive merge which results in file 
		// fields containing BOTH the string version of the previous file plus the 
		// new File instance when the user is replacing a file during	an update.  
		// The array_filter() is there to strip out empties from the files array.  
		// This prevents empty file fields from overriding the contents of the 
		// hidden field that stores	the previous file name.		
		$input = array_replace_recursive(Input::get(), array_filter(Input::file()));

		// Get the rules if they were not passed in
		if ($model && empty($rules)) $rules = $model::$rules;
		
		// If an AJAX update, don't require all fields to be present. Pass just the 
		// keys of the input to the array_only function to filter the rules list.
		if (Request::ajax() && Request::getMethod() == 'PUT') {
			$rules = array_only($rules, array_keys($input));
		}

		// If a model instance was passed, merge the input on top of that.  This 
		// allows data that may already be set on the model to be validated.  The 
		// input will override anything already set on the model.  In particular, 
		// this is done so that auto generated fields like the slug can be 
		// validated.  This intentionally comes after the AJAX conditional so that 
		// we still only validate fields that were present in the AJAX request.
		if ($model && method_exists($model, 'getAttributes')) {
			$input = array_merge($model->getAttributes(), $input);
		}

		// Build the validation instance and fire the intiating event.
		$validation = Validator::make($input, $rules, $messages);
		if ($model) $this->fireEvent('validating', array($model, $validation));

		// Run the validation.  If it fails, throw an exception that will get handled
		// by Routing\Filters.
		if ($validation->fails()) throw new ValidationFail($validation);
		
		// Fire completion event
		$this->fireEvent('validated', array($model, $validation));
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
	 * Fire an event.  Actually, two are fired, one for the event and one that 
	 * mentions the model for this controller
	 * 
	 * @param $string  event The name of this event
	 * @param $array   args  An array of params that will be passed to the handler
	 * @param $boolean until Whether to fire an "until" event or not
	 * @return object 
	 */
	protected function fireEvent($event, $args = null) {
		
		// Create the event name
		$event = "decoy::model.{$event}: ".$this->model;
		
		// Fire event
		return Event::fire($event, $args);
	}
	
	/**
	 * Format the results of a query in the format needed for the autocomplete 
	 * responses
	 * 
	 * @param  array $results 
	 * @return array
	 */
	public function formatAutocompleteResponse($results) {
		$output = array();
		foreach($results as $row) {
			
			// Only keep the id and title fields
			$item = new stdClass;
			$item->id = $row->getKey();
			$item->title = $row->getAdminTitleAttribute();
			
			// Add properties for the columns mentioned in the list view within the
			// 'columns' property of this row in the response.  Use the same logic
			// found in Decoy::renderListColumn();
			$item->columns = array();
			foreach($this->columns() as $column) {
				if (method_exists($row, $column)) $item->columns[$column] = call_user_func(array($row, $column));
				elseif (isset($row->$column)) {
					if (is_a($row->$column, 'Carbon\Carbon')) $item->columns[$column] = $row->$column->format(FORMAT_DATE);
					else $item->columns[$column] = $row->$column;
				} else $item->columns[$column] = null;
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
	 * Run the parent relationship function for the active model, returning the Relation
	 * object. Returns false if none found.
	 * 
	 * @return Illuminate\Database\Eloquent\Relations\Relation | false
	 */
	private function parentRelation() {
		if ($this->parent
			&& method_exists($this->parent, $this->parent_to_self)) 
			return $this->parent->{$this->parent_to_self}();
		else return false;
	}

	/**
	 * Tell Laravel to look for view files within the app admin views so that,
	 * on a controller-level basis, the app can customize elements of an admin
	 * view through it's partials.
	 *
	 * @return void 
	 */
	protected function overrideViews() {
		app('view.finder')->prependNamespace('decoy', app_path()
			.'/views/admin/'
			.Str::snake($this->controllerName())
		);
	}

	/**
	 * Pass controller properties that are used by the layout and views through
	 * to the view layer
	 *
	 * @param mixed $content string view name or an HtmlObject / View object
	 * @param array $vars Key value pairs passed to the content view
	 * @return void 
	 */
	protected function populateView($content, $vars = []) {

		// The view
		if (is_string($content)) $this->layout->content = View::make($content);
		else $this->layout->content = $content;

		// Set vars
		$this->layout->title = $this->title;
		$this->layout->description = $this->description();
		View::share('controller', $this->controller);
		
		// Make sure that the content is a Laravel view before applying vars.  
		// to it.  In the case of the index view, `content` is a Fields\Listing 
		// instance, not a Laravel view
		if (is_a($this->layout->content, 'Illuminate\View\View')) {
			$this->layout->content->with($vars);
		}

	}

	/**
	 * Creates a success message for CRUD commands
	 * 
	 * @param  Bkwld\Decoy\Model\Base|string $title The model instance that is being worked on 
	 *                                              or a string containing the title
	 * @param  string $verb  (Default is 'saved') Past tense CRUD verb (created, saved, etc)
	 * @return  string The CRUD success message string
	 */
	protected function successMessage($input = '', $verb = 'saved') {

		// Figure out the title and wrap it in quotes
		$title = $input;
		if (is_a($input, '\Bkwld\Decoy\Models\Base')) $title = $input->getAdminTitleAttribute();
		if ($title && is_string($title)) $title =  '"'.$title.'"';

		// Render the message
		$message = "The <b>".Str::singular($this->title)."</b> {$title} was successfully {$verb}.";

		// Add extra messaging if the creation was begun from the localize UI
		if ($verb == 'copied' && is_a($input, '\Bkwld\Decoy\Models\Base') && !empty($input->locale)) {
			$message .= " You may begin localizing it for <b>".Config::get('decoy::site.locales')[$input->locale].'</b>.';
		}

		// Return message
		return $message;
	}
}
