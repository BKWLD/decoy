<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use DecoyURL;
use Input;
use Former;
use Sentry;
use Redirect;
use URL;

/**
 * Admin management interface
 */
class Admins extends Base {
	
	// Shared settings
	protected $description = 'Users who have access to this admin area.';
	protected $columns = array(
		'Name'          => 'title',
		'Status'        => 'statuses',
		'Email'         => 'email',
	);
	
	/**
	 * Listing view
	 */
	public function index() {
		
		// Take the listing results and replace them with model instances
		// so title() can be called on them to decorate the person's name
		$results = Model::ordered()->paginate($this->perPage())->getIterator();
		foreach($results as &$item) {
			$item = new Model((array) $item);
		}

		// Bind to view
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->title,
			'controller'       => $this->controller,
			'description'      => $this->description,
			'count'            => Model::count(),
			'listing'          => $results,
			'columns'          => $this->columns,
		));
	}
	
	/**
	 * Create a new one
	 */
	public function store() {

		// Validate
		if ($result = $this->validate(Model::$rules)) return $result;
		
		// Create
		$id = Model::create(Input::get());

		// If a group was passed, add the group
		if ($group = Input::get('group')) {
			Model::find($id)->sentryUser()->addGroup(Sentry::findGroupByName($group));
		}
		
		// Redirect to edit view
		return Redirect::to(DecoyURL::relative('edit', $id));
	}

	/**
	 * Edit form
	 */
	public function edit($id) {
		
		// Make password optional
		unset(Model::$rules['password']);
		
		// Run default logic (which doesn't return a response)
		parent::edit($id);

		// Populate the group
		Former::populateField('group', Model::find($id)->getGroupName());

	}
	
	/**
	 * Handle updates
	 */
	public function update($id) {
		
		// Lookup admin
		if (!($admin = Model::find($id))) return App::abort(404);

		// Validate.  Password isn't required when editing.  And make sure this row
		// is excluded from uniqueness check
		unset(Model::$rules['password']);
		Model::$rules['email'] = Model::$rules['email'].','.$id;
		if ($result = $this->validate(Model::$rules)) return $result;
		
		// Update
		$admin->update(Input::get());
		
		// Redirect to the edit view
		return Redirect::to(URL::current());
	
	}
	
	/**
	 * Disable the admin
	 */
	public function disable($id) {
		if (!($admin = Model::find($id))) return App::abort(404);
		$admin->disable();
		return Redirect::back();
	}
	
	/**
	 * Enable the admin
	 */
	public function enable($id) {
		if (!($admin = Model::find($id))) return App::abort(404);
		$admin->enable();
		return Redirect::back();
	}
	
}