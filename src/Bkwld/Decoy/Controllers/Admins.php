<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Fields\Listing;
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

		// Add group column
		$this->columns = array_merge(
			array_slice($this->columns, 0, 1),
			['Group' => 'getRoleName'],
			array_slice($this->columns, 1)
		);
		
		// Take the listing results and replace them with model instances
		// so title() can be called on them to decorate the person's name
		$results = Model::ordered()->paginate($this->perPage())->getIterator();
		foreach($results as &$item) {
			$item = new Model((array) $item);
		}

		// Bind to view
		$this->populateView(Listing::createFromController($this, $results));

	}
	
	/**
	 * Create a new one
	 */
	public function store() {

		// Validate
		if ($result = $this->validate(null, Model::$rules)) return $result;
		
		// Create
		$id = Model::create(Input::get());
		
		// Redirect to edit view
		return Redirect::to(DecoyURL::relative('edit', $id))
			->with('success', $this->successMessage(Input::get('first_name').' '.Input::get('last_name')));
	}

	/**
	 * Edit form
	 */
	public function edit($id) {
		
		// Make password optional
		unset(Model::$rules['password']);
		
		// Run default logic (which doesn't return a response)
		parent::edit($id);

		// Populate the role
		Former::populateField('role', Model::find($id)->getRoleName());

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
		if ($result = $this->validate(null, Model::$rules)) return $result;
		
		// Update
		$admin->update(Input::get());
		
		// Redirect to the edit view
		return Redirect::to(URL::current())
			->with('success', $this->successMessage(Input::get('first_name').' '.Input::get('last_name')));
	
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