<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use Html;
use Input;
use Sentry;
use Redirect;

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
	
	//Listing view
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
	
	// Create a new one
	public function store() {

		// Validate
		if ($result = $this->validate(Model::$rules)) return $result;
		
		// Create
		$id = Model::create(Input::get());
		
		// Redirect to edit view
		return Redirect::to(Html::relative('edit', $id));
	}

	// Edit form
	public function edit($id) {
		
		// Make password optional
		unset(Model::$rules['password']);
		
		// Rest of logic is the default
		parent::edit($id);
	}
	
	// Handle updates.
	public function update($id) {
		
		// Lookup admin
		if (!($admin = Model::find($id))) return Response::error('404');
		
		// Preserve the old admin data for the email
		$admin_data = $admin->get();
		$admin_data = (object) array_merge($admin_data, $admin_data['metadata']);

		// Validate.  Password isn't required when editing.  And if the inputted 
		// email is the same as what we had for the admin, remove validation so that
		// it doesn't throw uniqueness errors.
		unset(Model::$rules['password']);
		if (Input::get('email') == $admin->get('email')) unset(Model::$rules['email']);
		if ($result = $this->validate(Model::$rules)) return $result;
		
		// Save data
		$input = array(
			'email' => Input::get('email'),
			'metadata' => array(
				'first_name' => Input::get('first_name'),
				'last_name'  => Input::get('last_name'),
		));
		if (Input::has('password')) $input['password'] = Input::get('password');
		$admin->update($input);
		
		// Send email
		if (Input::get('send_email')) Model::send('edit', $admin_data);
		
		// Redirect to the edit view
		return Redirect::to(URL::current());
	
	}
	
	// Delete the admin
	public function destroy($ids) {
		$ids = explode('-',$ids);
		foreach($ids as $id) {
			if (!($admin = Model::find($id))) return Response::error('404');
			$admin->delete();
		}
		if (Request::ajax()) return Response::json('null');
		else return Redirect::to_action('decoy::admins');
	}
	
	// Disable the admin
	public function disable($id) {
		if (!($admin = Model::find($id))) return Response::error('404');
		$admin->disable();
		return Redirect::back();
	}
	
	// Enable the admin
	public function enable($id) {
		if (!($admin = Model::find($id))) return Response::error('404');
		$admin->enable();
		return Redirect::back();
	}
	
}