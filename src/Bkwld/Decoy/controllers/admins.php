<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use \Input;
use \Model;
use \Sentry;

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
		$query = Model::ordered()->paginate($this->PER_PAGE);
		foreach($query as &$item) {
			$item = new Model((array) $item);
		}
	
		// Bind to view
		$this->layout->nest('content', 'decoy::shared.list._standard', array(
			'title'            => $this->TITLE,
			'controller'       => $this->CONTROLLER,
			'description'      => $this->DESCRIPTION,
			'count'            => Model::count(),
			'listing'          => $query,
			'columns'          => $this->COLUMNS,
		));
	}
	
	// Create a new one
	public function post_new() {
		
		// Validate
		if ($result = $this->validate(Admin::$rules)) return $result;
		
		// Create the user
		$id = Sentry::user()->create(array(
			'email'    => Input::get('email'),
			'password' => Input::get('password'),
			'metadata' => array(
				'first_name' => Input::get('first_name'),
				'last_name'  => Input::get('last_name'),
		)));
		
		// Assign the user to admins
		Sentry::user($id)->add_to_group('admins');
		
		// Send email
		if (Input::get('send_email')) {
			if (!Model::send('new')) {
				$errors = new Laravel\Messages();
				$errors->add('email', 'There was an error sending the email to this admin.');
				return Redirect::to_action('decoy::admins@edit', array($id))
					->with_errors($errors);
			}
		}
		
		// Redirect to edit view
		return Redirect::to_route('decoy::admins@edit', array($id));
	}

	// Edit form
	public function get_edit($id) {
		
		// Make password optional
		unset(Model::$rules['password']);
		
		// Rest of logic is the default
		parent::get_edit($id);
	}
	
	// Handle updates.
	public function post_edit($id) {
		
		// Lookup admin
		if (!($admin = Admin::get($id))) return Response::error('404');
		
		// Preserve the old admin data for the email
		$admin_data = $admin->get();
		$admin_data = (object) array_merge($admin_data, $admin_data['metadata']);

		// Validate.  Password isn't required when editing.  And if the inputted 
		// email is the same as what we had for the admin, remove validation so that
		// it doesn't throw uniqueness errors.
		unset(Admin::$rules['password']);
		if (Input::get('email') == $admin->get('email')) unset(Admin::$rules['email']);
		if ($result = $this->validate(Admin::$rules)) return $result;
		
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
		if (Input::get('send_email')) Admin::send('edit', $admin_data);
		
		// Redirect to the edit view
		return Redirect::to(URL::current());
	
	}
	
	// Delete the admin
	public function delete_delete($ids) {
		$ids = explode('-',$ids);
		foreach($ids as $id) {
			if (!($admin = Admin::get($id))) return Response::error('404');
			$admin->delete();
		}
		if (Request::ajax()) return Response::json('null');
		else return Redirect::to_action('decoy::admins');
	}
	
	// Disable the admin
	public function get_disable($id) {
		if (!($admin = Admin::get($id))) return Response::error('404');
		$admin->disable();
		return Redirect::back();
	}
	
	// Enable the admin
	public function get_enable($id) {
		if (!($admin = Admin::get($id))) return Response::error('404');
		$admin->enable();
		return Redirect::back();
	}
	
}