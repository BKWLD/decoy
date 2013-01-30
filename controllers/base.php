<?php

abstract class Decoy_Base_Controller extends Controller {
	
	//---------------------------------------------------------------------------
	// Default settings
	//---------------------------------------------------------------------------
	
	// Everything should be restful
	public $restful = true;
	
	// Shared layout for admin view, set in the constructor
	public $layout;
	
	// Default pagination settings
	const PER_PAGE = 20;
	const PER_PAGE_SIDEBAR = 6;
	
	// Values that get shared by many controller methods.  Default values for these
	// get set in the constructor.
	protected $MODEL;       // i.e. Post
	protected $CONTROLLER;  // i.e. admin.posts
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
	
	// Special constructor behaviors
	function __construct() {

		// Set the layout from the Config file
		$this->layout = Config::get('decoy::decoy.layout');
		
		// Get the controller name only, without the namespace (like Admin_) or
		// suffix (like _Controller).  I..e, Admin_News_Posts_Controller becomes
		// 'News_Posts'
		preg_match('#^[^_]+_(.+)_[^_]+$#', get_class($this), $matches);
		$controller_name = $matches[1];
				
		// Make a default title based on the controller name
		if (empty($this->TITLE)) {
			$this->TITLE = str_replace('_', ' ', $controller_name);
		}
		
		// Figure out what the controller get should be.
		// i.e. 'Admin_News_Posts_Controller' becomes 'admin.news_posts';
		if (empty($this->CONTROLLER)) {
			$this->CONTROLLER = preg_replace('#_#', '.', strtolower(substr(get_class($this), 0, -11)), 1);
			
			// If it begins with decoy, it should be like decoy::admin instead of decoy.admin
			if (Str::is('decoy*', $this->CONTROLLER)) $this->CONTROLLER = str_replace('decoy.', 'decoy::', $this->CONTROLLER);
		}
		
		// Figure out what the show view should be.  This is the path to the show view file.  Such
		// as 'admin.news.show'
		if (empty($this->SHOW_VIEW)) $this->SHOW_VIEW = $this->CONTROLLER.'.show';
		
		// Try to suss out the model by singularizing the controller
		if (empty($this->MODEL)) {
			$this->MODEL = Str::singular($controller_name);
			if (!class_exists($this->MODEL)) $this->MODEL = NULL;
		}

		// This allows us to refer to the default model for a controller using the
		// generic term of "Model"
		if ($this->MODEL && !class_exists('Model')) {
			if (!class_alias($this->MODEL, 'Model')) throw new Exception('Class alias failed');
		}
		
		// If the current route has a parent, discover what it is
		if (empty($this->PARENT_CONTROLLER) && $this->is_child_route()) {
			$this->PARENT_CONTROLLER = $this->deduce_parent_controller();
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
				$this->PARENT_TO_SELF = $this->deduce_parent_relationship();
			}
			
			// Figure out the child relationship name, which is typically named the same
			// as the parent model
			if (empty($this->SELF_TO_PARENT) && $this->PARENT_MODEL) {
				$this->SELF_TO_PARENT = $this->deduce_child_relationship();
			}
		}
		
		// Continue processing
		parent::__construct();
		
	}
	
	//---------------------------------------------------------------------------
	// Getter/setter
	//---------------------------------------------------------------------------
	
	// Get access to protected properties
	public function model() { return $this->MODEL; }
	public function parent_controller() { return $this->PARENT_CONTROLLER; }
	public function controller() { return $this->CONTROLLER; }
	public function title() { return $this->TITLE; }
	
	// This is set to an instance of whatever model instance is created or fetched
	// by post_new and *_edit calls.
	private $item;
	public function item() { return $this->item; }
	
	//---------------------------------------------------------------------------
	// Basic CRUD methods
	//---------------------------------------------------------------------------
	
	// Listing page
	public function get_index() {
		
		// Run the query
		$results = Decoy\Search::apply(Model::ordered(), $this->SEARCH)->paginate(self::PER_PAGE);
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
		if ($this->is_child_in_many_to_many()) {
			
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
		$results = Decoy\Search::apply($query, $this->SEARCH)->paginate(self::PER_PAGE);
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
		if ($this->is_child_in_many_to_many()) {
			
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
		if (is_numeric(URI::segment(3))) {
			$parent_id = URI::segment(3);
			if (!($parent = self::parent_find($parent_id))) return Response::error('404');
		}

		// Pass validation through
		Former::withRules(Model::$rules);
		
		// Return view
		$this->layout->nest('content', $this->SHOW_VIEW, array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
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
		if (is_numeric(URI::segment(3))) {
			$parent_id = URI::segment(3);
			if (!($parent = self::parent_find($parent_id))) return Response::error('404');
		}
		
		// Create default slug
		$this->merge_default_slug();
		
		// Validate
		if ($result = $this->validate(Model::$rules)) return $result;

		// Create a new object
		$item = new Model();
		$item->fill(BKWLD\Utils\Collection::null_empties(Input::get()));
		self::save_files($item);
		
		// Save it
		if (!empty($parent_id)) {
			$query = $parent->{$this->PARENT_TO_SELF}()->insert($item);
		} else $item->save();
		$this->item = $item;
		
		// Redirect to edit view
		if (Request::ajax()) return Response::json(array('id' => $item->id));
		else return Redirect::to(str_replace('new', $item->id, URI::current()));
	}
	
	// Edit form
	public function get_edit($id) {

		// Get the work
		if (!($item = Model::find($id))) return Response::error('404');
		$this->item = $item;

		// Populate form
		Former::populate($item);
		Former::withRules(Model::$rules);
		
		// Render the view
		$this->layout->nest('content', $this->SHOW_VIEW, array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'item'             => $item,
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
		$this->item = $item;

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
		$item->{$this->SELF_TO_PARENT}()->attach(Input::get('parent_id'));
		return Response::json(array(
			'pivot_id' => DB::connection('mysql')->pdo->lastInsertId()
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
		
		// Delete images if they are defined.
		foreach($items->get() as $item) {
			if (!method_exists($item, 'image') && !empty($item->image)) Croppa::delete($item->image);
		}
		
		// Delete the row
		$items->delete();
		
		// If the referrer contains the controller route, that would mean that we're
		// redirecting back to the edit page (which no longer exists).  Thus, go to a
		// listing instead.  Otherwise, go back (accoridng to referrer)
		if (Request::ajax()) return Response::json('null');
		else {
			if (strpos(Request::referrer(), route($this->CONTROLLER)) === false) return Redirect::to_route('decoy::back');
			else return Redirect::to_route('decoy::back', 2);
		}
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
			DB::query("DELETE FROM {$pivot_table} WHERE id = ?", $id);
		}
		
		// Redirect.  We can use back cause this is never called from a "show"
		// page like get_delete is.
		if (Request::ajax()) return Response::json('null');
		else return Redirect::back();
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	// All actions validate in basically the same way.  This is
	// shared logic for that
	protected function validate($rules, $messages = array()) {
		
		// Pull the input from the proper place
		$input = BKWLD\Laravel\Input::json_and_input();
		$input = array_merge($input, Input::file()); // Validate files too
		
		// If an AJAX update, don't require all fields to be present. Pass
		// just the keys of the input to the array_only function to filter
		// the rules list.
		if (Request::ajax() && Request::method() == 'PUT') {
			$rules = array_only($rules, array_keys($input));
		}
		
		// Validate
		$validation = Validator::make($input, $rules, $messages);
		if ($validation->fails()) {
			if (Request::ajax()) {
				return Response::json($validation->errors, 400);
			} else {
				return Redirect::to(URL::current())
					->with_errors($validation)
					->with_input();
			}
		}
		
		// If there were no errors, return false, which means
		// that we don't need to redirect
		return false;
	}
	
	// Take an array of key / value (url/label) pairs and pass it where
	// it needs to go to make the breadcrumbs
	protected function breadcrumbs($links) {
		$this->layout->nest('breadcrumbs', 'decoy::layouts._breadcrumbs', array(
			'breadcrumbs' => $links
		));
	}
	
	// If there is slug mentioned in the validation or mass assignment rules
	// and an appropriate title-like field, make a slug for it.  Then merge
	// that into the inputs, so it can be validated easily
	protected function merge_default_slug() {
		
		// If we're on an edit view, update the unique condition on the rule
		// (if it exists) to be unique but not for the current row
		if (in_array('slug', array_keys(Model::$rules)) 
			&& strpos(Model::$rules['slug'], 'unique') !== false
			&& Request::route()->controller_action == 'edit') {
			
			// Add the row exception to the unique clause.  The regexp works because
			// the \w+ will end at the | that begins the next condition
			$id = Request::route()->parameters[0];
			Model::$rules['slug'] = preg_replace('#(unique:\w+)(,slug)?#', 
				'$1,slug,'.$id, 
				Model::$rules['slug']);			
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
	
	// Return a boolean for whether the parent relationship represents a many to many
	public function is_child_in_many_to_many() {
		if (empty($this->SELF_TO_PARENT)) return false;
		$model = new $this->MODEL; // Using the 'Model' class alias didn't work, was the parent
		if (!method_exists($model, $this->SELF_TO_PARENT)) return false;
		$relationship = $model->{$this->SELF_TO_PARENT}();
		return is_a($relationship, 'Laravel\Database\Eloquent\Relationships\Has_Many_And_Belongs_To');
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
	
	// Test if the current route is serviced by has many and/or belongs to.  These
	// are only true if this controller is acting in a child role
	public function is_child_route() {
		if (empty($this->CONTROLLER)) throw new Exception('$this->CONTROLLER not set');
		return $this->action_is_child()
			|| $this->parent_in_input()
			|| $this->acting_as_related();
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
			// found in HTML::render_list_column();
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
	// File handling uttlity methods
	//---------------------------------------------------------------------------
	
	// Files in an edit state have a number of supplentary fields.  This
	// prepares the file for validation.
	protected static function pre_validate_files() {
		
		// Only callable during an edit
		if (Request::route()->controller_action != 'edit') return;
		
		// Loop through the input and and look for certain input fields by checking for other inputs
		// with specific suffices.
		foreach(Input::get() as $field => $value) {
			
			// Check if this field is for a file.  All files on edit view have an 'old-*' hidden field
			if (!Str::is(UPLOAD_OLD.'*', $field)) continue;
			$column = substr($field, 4);
			
			// If someone has said to delete a file, do nothing.  Input will look like
			// there is no input for this file.  Required validators can do their thing
			if (Input::has(UPLOAD_DELETE.$column)) continue;
			
			// If someone has uploaded a new file, use it's value as the field and continue.
			if (Input::has_file(UPLOAD_REPLACE.$column)) {
				self::move_replace_file_input($column);
				continue;
			}

			// The user has not specified to delete and has not uploaded a file (these conditions would be
			// caught above and the code wouldn't have reached this point).  If the field
			// is required, there must have been one uploaded originally.  So skip the required
			// validation.  We don't want to just use the old value because the validator would still
			// look for a file possibly and we'd be passing a single string.  Only files have an 'old-*'
			// field so checking for it is equivalent to seeing looking for a file
			if (!empty(Model::$rules)
				&& array_key_exists($column, Model::$rules)
				&& strpos(Model::$rules[$column], 'required') !== false) {
				
				// Delete the required validation and get rid of any double pipes or starting
				// or ending pipes that may have resulted from the str_replace
				Model::$rules[$column] = str_replace('required', '', Model::$rules[$column]);
				Model::$rules[$column] = str_replace('||', '|', Model::$rules[$column]);
				Model::$rules[$column] = trim(Model::$rules[$column], '|');
			}
		}
	}
	
	// On edit pages, the file input for replacing a file is labeld like
	// "replace-COLUMN" (i.e. replace-image) so that it plays nice with 
	// valiations with require and Former.  This function takes the data from
	// the replace-* input and moves it to the more expected, just column name
	// parameter of the FILES array.
	// - $column - The column name (i.e. 'image', not 'replace-image')
	static protected function move_replace_file_input($column) {
		if (!array_key_exists(UPLOAD_REPLACE.$column, $_FILES)) return;
		$_FILES[$column] = $_FILES[UPLOAD_REPLACE.$column];
		unset($_FILES[UPLOAD_REPLACE.$column]);
	}
	
	// Loop through inputs looking for checked-boxes for deleting a file.
	// If found, act.  For some documentation on some of these lines, check out
	// pre_validate_files() which bares similarities
	protected static function delete_files(&$item) {
		foreach(Input::get() as $field => $value) {
			
			// If there is a delete checkbox and it has a value, that means it was clicked
			if (!(Str::is(UPLOAD_DELETE.'*', $field) && Input::get($field))) continue;
			$column = substr($field, 7);
			
			// Remove the file and unset the column in the model instance
			if (!empty($item->$column)) Croppa::delete($item->$column);
			$item->$column = null;
		}
	}
	
	// Loop through all the files in the input and save out the files
	protected static function save_files(&$item) {
		foreach($_FILES as $column => $file_data) {
			if (!Input::has_file($column)) continue;
			
			// Delete the old file, if it exists
			if (!empty($item->$column)) Croppa::delete($item->$column);
			
			// Save the incoming file out
			$item->$column = Model::save_file($column);
		}	
	}
	
	// On edit pages with file inputs, there are some extra fields that should be
	// stripped out of the input so that they don't confuse mass assignment
	static protected function unset_file_edit_inputs() {
		$input = Input::get();
		foreach($input as $field => $value) {
			
			// Check if this field is for a file.  All files on edit view have an 'old-*' hidden field
			if (!(Str::is(UPLOAD_OLD.'*', $field))) continue;
			$column = substr($field, 4);
			
			// Remove the columns that don't exist in the db
			unset($input[UPLOAD_OLD.$column]);
			unset($input[UPLOAD_DELETE.$column]);
			unset($input[UPLOAD_REPLACE.$column]);
		}	
		
		// Replace the Input::get() with the new values
		Input::replace($input);
	}
	
	//---------------------------------------------------------------------------
	// Private support methods
	//---------------------------------------------------------------------------
	
	// Test if the current route is one of the full page has many listings or a new
	// page as a child
	private function action_is_child() {
		return Request::route()->is($this->CONTROLLER.'@child')
			|| Request::route()->is($this->CONTROLLER.'@new_child')
			|| Request::route()->is($this->CONTROLLER.'@edit_child');
	}
	
	// Test if the current route is one of the many to many XHR requests
	private function parent_in_input() {
		// This is check is only allowed if the request is for this controller.  If other
		// controller instances are instantiated (like via Controller::resolve()), they 
		// were not designed to be informed by the input.  Using action[uses] rather than like
		// ->controller because I found that controller isn't always set when I need it.  Maybe
		// because this is all being invoked from the constructor.
		if (strpos(Request::route()->action['uses'], $this->CONTROLLER.'@') === false) return false;
		
		$input = BKWLD\Laravel\Input::json_and_input();
		return isset($input['parent_controller']);
	}
	
	// Test if the controller must be used in rendering a related list within another.  In other
	// words, the controller is different than the request and you're on an edit page.  Had to
	// use action[uses] because Request::route()->controller is sometimes empty.  
	// Request::route()->action['uses'] is like "admin.issues@edit".  We're also testing that
	// the controller isn't in the URI.  This would never be the case when something was in the
	// sidebar.  But without it, deducing the breadcrumbs gets confused because controllers get
	// instantiated not on their route but aren't the children of the current route.
	private function acting_as_related() {
		$handles = Bundle::option('decoy', 'handles');
		$controller_name = substr($this->CONTROLLER, strlen($handles.'.'));
		return strpos(Request::route()->action['uses'], $this->CONTROLLER.'@') === false
			&& strpos(URI::current(), '/'.$controller_name.'/') === false
			&& strpos(Request::route()->action['uses'], '@edit') !== false;
	}
	
	// Guess at what the parent controller is by examing the route or input varibles
	private function deduce_parent_controller() {
		
		// If a child index view, get the controller from the route
		if ($this->action_is_child()) {
			return URI::segment(1).'.'.URI::segment(2);
		
		// If one of the many to many xhr requests, get the parent from Input
		} elseif ($this->parent_in_input()) {
			$input = BKWLD\Laravel\Input::json_and_input();
			return $input['parent_controller'];
		
		// If this controller is a related view of another, the parent is the main request	
		} else if ($this->acting_as_related()) {
			return Request::route()->controller;
		}
	}
	
	// Guess as what the relationship function on the parent model will be
	// that points back to the model for this controller by using THIS
	// controller's name.
	// returns - The string name of the realtonship
	private function deduce_parent_relationship() {
		$handles = Bundle::option('decoy', 'handles');
		$relationship = substr($this->CONTROLLER, strlen($handles.'.'));
		if (!method_exists($this->PARENT_MODEL, $relationship)) {
			throw new Exception('Parent relationship missing, looking for: '.$relationship);
		}
		return $relationship;
	}
	
	// Guess at what the child relationship name is.  This is typically the same
	// as the parent model.  For instance, Post has many Image.  Image will have
	// a function named "post" for it's relationship
	private function deduce_child_relationship() {
		$relationship = strtolower($this->PARENT_MODEL);
		if (!method_exists($this->MODEL, $relationship)) {
			
			// Try controller name instead, in other words the plural version.  It might be
			// named this if it's a many-to-many relationship
			$handles = Bundle::option('decoy', 'handles');
			$relationship = strtolower(substr($this->PARENT_CONTROLLER, strlen($handles.'.')));
			if (!method_exists($this->MODEL, $relationship)) {
				throw new Exception('Child relationship missing on '.$this->MODEL);
			}
		}
		return $relationship;
	}
	
	// Get the pivot table name and the child foreign key (the active Model) is probably
	// the child, and the parent foreign key column name
	private function pivot() {
		
		// If the request doesn't know it's child of another class (often because an exeption)
		// this won't work
		if (empty($this->SELF_TO_PARENT) || empty($this->PARENT_TO_SELF)) {
			throw new Exception('Empty relationships in pivot');
		}
		
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