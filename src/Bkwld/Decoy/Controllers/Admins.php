<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Html;
use Input;
use Sentry;
use Redirect;
use URL;

/**
 * Admin management interface
 */
class Admins extends Base {
	
	// Shared settings
	protected $DESCRIPTION = 'Users who have access to this admin area.';
	protected $COLUMNS = array(
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
		$results = Model::ordered()->paginate($this->PER_PAGE)->getIterator();
		foreach($results as &$item) {
			$item = new Model((array) $item);
		}

		// Bind to view
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'count'            => Model::count(),
			'listing'          => $results,
			'columns'          => $this->COLUMNS,
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
		
		// Redirect to edit view
		return Redirect::to(DecoyURL::relative('edit', $id));
	}

	/**
	 * Edit form
	 */
	public function edit($id) {
		
		// Make password optional
		unset(Model::$rules['password']);
		
		// Rest of logic is the default
		return parent::edit($id);
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