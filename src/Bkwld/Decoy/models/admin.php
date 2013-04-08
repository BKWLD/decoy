<?php

// Admin extends Eloquent in part so that the listing view
// can instantiate Admin models and hydrate them.  Which is
// done so that title() can be run to decorate the listing
class Admin extends Decoy\Base_Model {
	
	// Validation rules
	public static $rules = array(
		'email' => 'required|unique:users|email',
		'password' => 'required',
		'first_name' => 'required',
		'last_name' => 'required',
	);
	
	// Get a list of admins ordered by last name
	static public function ordered() {
		$group_id = Sentry::group('admins')->get('id');
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->join('users_metadata', 'users_metadata.user_id', '=', 'users.id')
			->where('users_groups.group_id', '=', $group_id)
			->order_by('last_name', 'asc')
			->select(array('users.id',
					'users.email', 
					'users_metadata.first_name', 
					'users_metadata.last_name',
					'users.created_at',
					'users.last_login',
					'users.status',
				));
	}
	
	// Produce the title for the list view
	public function title() {
		return '<img src="'.HTML::gravatar($this->email).'" class="gravatar"/> '.$this->first_name.' '.$this->last_name;
	}
	
	// Show a badge if the user is the currently logged in
	public function statuses() {
		$html ='';
		
		// If row is you
		if ($this->id == Sentry::user()->get('id')) {
			$html .= '<span class="label label-info">You</span>';
		}
		
		// If row is disabled
		if (!$this->status) {
			$html .= '<a href="'.action('decoy::admins@enable', array($this->id)).'" class="label label-warning js-tooltip" title="Click to enable login">Disabled</a>';
		}
		
		return $html;
	}
	
	// Send welcome and update emails
	static public function send($type, $data = null) {

		// Shared vars
		$to = array(Input::get('email') => $name = Input::get('first_name').' '.Input::get('last_name'));
		
		// Support different types of emails
		switch($type) {
			
			// Creation of admin
			case 'new':
				$subject = 'Welcome to the '.Config::get('decoy::decoy.site_name').' CMS';
				$body = Input::get('first_name').' '.Input::get('last_name'). ' has created an admin account for you for on '.URL::base().'.  You can login at <a href="'.action('decoy::').'">'.action('decoy::').'</a>. Your password is <strong>'.Input::get('password').'</strong>, though you should change it after you first log in.  Welcome!';
			break;
			
			// Update of admin info
			case 'edit':
				$old = $data;
				$to[$old->email] = $old->first_name.' '.$old->last_name;
				$subject = 'Your '.Config::get('decoy::decoy.site_name').' CMS account info has been updated';
				$body = Sentry::user()->get('metadata.first_name').' '.Sentry::user()->get('metadata.last_name'). ' has updated your account info on '.URL::base().'.  Your current account info is:<br/><br/>';
				$body .= '<strong>Name:</strong> '.Input::get('first_name').' '.Input::get('last_name').'<br>';
				$body .= '<strong>Email:</strong> '.Input::get('email').'<br>';
				if (Input::has('password')) $body .= '<strong>Password:</strong> '.Input::get('password').' (you should change this ASAP)';
				else $body.= '<strong>Password:</strong> Unchanged (and cannot be displayed because it is one-way encrypted)';
			break;
		}
		
		// Send welcome email
		$mail = Message::to($to)
			->from(Config::get('decoy::decoy.mail_from_address'), Config::get('decoy::decoy.mail_from_name'))
			->subject($subject)
			->body($body)
			->html(true)
			->send();
			
		// There was an error sending the mail
		if (!$mail->was_sent()) return false;
		
		// Success
		return true;
	}
	
	// Convenince method for looking up admins in sentry cause catching
	// exceptions takes a lot of lines
	static public function get($id) {
		try {
			return Sentry::user((int) $id);
		} catch(Exception $e) {
			return false;
		}
	}
	
	// Override the Eloquent find.  This is required to make admins function with Decoy,
	// which expects Eloquent models in it's generic breadcrumbs, listing, etc
	static public function find($id) {
		
		// Get the item from Sentry and then get an array of the user data from it
		$admin = self::get($id)->get();
		
		// Populate an admin isntance with it's values, for the purposes of populating
		// the edit form.
		$admin = (array) array_merge($admin, $admin['metadata']);
		return new Admin($admin);
		
	}
	
	// Count the total admins
	static public function count() {
		$group_id = Sentry::group('admins')->get('id');
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->where('users_groups.group_id', '=', $group_id)
			->count();
	}
	
}