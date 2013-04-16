<?php namespace Bkwld\Decoy\Models;

// Dependencies
use DB;
use Html;
use Input;
use Sentry;

/**
 * Admin extends Eloquent in part so that the listing view
 * can instantiate Admin models and hydrate them.  Which is
 * done so that title() can be run to decorate the listing
 */
class Admin extends Base {
	
	// Validation rules
	public static $rules = array(
		'email' => 'required|unique:users|email',
		'password' => 'required',
		'first_name' => 'required',
		'last_name' => 'required',
	);
	
	// Allow all properties to be mass assigned.  This assignment happens in the listing
	// view.  This model doesn't actually correspond with a real table so there is no risk
	// that this setting would allow a malicious person to set one of the actual DB values
	protected $guarded = array();
	
	// Get a list of admins ordered by last name
	static public function ordered() {
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->join('throttle', 'throttle.user_id', '=', 'users.id')
			->where('users_groups.group_id', '=', self::adminGroupId())
			->orderBy('last_name', 'asc')
			->select(array('users.id',
					'users.email', 
					'users.first_name', 
					'users.last_name',
					'users.created_at',
					'throttle.banned',
					'throttle.suspended',
				));
	}
	
	// Produce the title for the list view
	public function title() {
		return '<img src="'.Html::gravatar($this->email).'" class="gravatar"/> '.$this->first_name.' '.$this->last_name;
	}
	
	// Show a badge if the user is the currently logged in
	public function statuses() {
		$html ='';
		return ''; // Temporary hack
		
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
	
	// Override the Eloquent find.  This is required to make admins function with Decoy,
	// which expects Eloquent models in it's generic breadcrumbs, listing, etc
	static public function find($id, $columns = array('*')) {
		if ($admin = DB::table('users')->find($id)) return new Admin((array) $admin);
		return false;
	}
	
	// Count the total admins
	static public function count() {
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->where('users_groups.group_id', '=', self::adminGroupId())
			->count();
	}
	
	/**
	 * Get the admin group id
	 */
	static public function adminGroupId() {
		return Sentry::getGroupProvider()->findByName('admins')->id;
	}
	
}