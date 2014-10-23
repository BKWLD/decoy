<?php namespace Bkwld\Decoy\Models;

// Dependencies
use App;
use Config;
use DB;
use DecoyURL;
use HTML;
use Input;
use Mail;
use Redirect;
use Request;
use Sentry;
use URL;

/**
 * Admin extends Eloquent in part so that the listing view
 * can instantiate Admin models and hydrate them.  Which is
 * done so that title() can be run to decorate the listing
 */
class Admin extends Base {
	
	// Validation rules
	public static $rules = array(
		'email' => 'required|email|unique:users,email',
		'password' => 'required',
		'confirm_password' => 'sometimes|required_with:password|same:password',
		'first_name' => 'required',
		'last_name' => 'required',
	);
	
	/**
	 * Allow all properties to be mass assigned.  This model doesn't actually correspond 
	 * with a real table so there is no risk that this setting would allow a malicious 
	 * person to set one of the actual DB values
	 */
	protected $guarded = array();
	
	// Get a list of admins ordered by last name
	static public function ordered() {
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->leftJoin('throttle', 'throttle.user_id', '=', 'users.id')
			->whereIn('users_groups.group_id', self::adminGroupIds())
			->orderBy('last_name', 'asc')
			->groupBy('users.id')
			->select(array('users.id',
					'users.email', 
					'users.first_name', 
					'users.last_name',
					'users.created_at',
					'throttle.banned',
					'throttle.suspended',
				));
	}
	
	// Count the total admins
	static public function count() {
		return DB::table('users')
			->join('users_groups', 'users_groups.user_id', '=', 'users.id')
			->whereIn('users_groups.group_id', self::adminGroupIds())
			->count();
	}
	
	// Produce the title for the list view
	public function title() {
		return '<img src="'.HTML::gravatar($this->email).'" class="gravatar"/> '.$this->first_name.' '.$this->last_name;
	}
	
	// Show a badge if the user is the currently logged in
	public function statuses() {
		$html ='';
		
		// If row is you
		if ($this->id == App::make('decoy.auth')->userId()) {
			$html .= '<span class="label label-info">You</span>';
		}
		
		// If row is disabled
		if ($this->disabled()) {
			$html .= '<a href="'.URL::to(DecoyURL::relative('enable', $this->id)).'" class="label label-warning js-tooltip" title="Click to enable login">Disabled</a>';
		}
		
		return $html;
	}
	
	// Override the Eloquent find.  This is required to make admins function with Decoy,
	// which expects Eloquent models in it's generic breadcrumbs, listing, etc
	static public function find($id, $columns = array('*')) {
		if ($admin = DB::table('users')->find($id)) return new Admin((array) $admin);
		return false;
	}
	
	/**
	 * Get a sentry user object from an admin object
	 * @return integer
	 */
	public function sentryUser() {
		return Sentry::getUserProvider()->findById($this->id);
	}
	
	/**
	 * Get the ids of all groups that have the "admin" permission
	 * @return integer
	 */
	static public function adminGroupIds() {

		// Get all the groups that are admins
		$groups = array_filter(Sentry::findAllGroups(), function($group) {
			$permissions = $group->getPermissions();
			return !empty($permissions['admin']);
		});

		// Return just their ids
		return array_map(function($group) {
			return $group->getId();
		}, $groups);
	}

	/**
	 * Get the role name of the admin.  This will only return group
	 * names that are explicitly in the `roles` config
	 */
	public function getRoleName() {
		$keys = array_keys(Config::get('decoy::site.roles'));
		$group = array_first($this->sentryUser()->getGroups(), function($i, $group) use ($keys) {
			return in_array($group->getName(), $keys);
		});
		if ($group) return $group->getName();
	}
	
	/**
	 * Create a new admin, reading from Input directly
	 */
	static public function create(array $input) {
		
		// Cast to object
		$input = (object) $input;
		
		// Create the login user
		$user = Sentry::getUserProvider()->create(array(
			'email'    => $input->email,
			'password' => $input->password,
			'first_name' => $input->first_name,
			'last_name'  => $input->last_name,
			'activated' => true,
		));
		
		// Add to the specified group
		if (isset($input->role)) {
			$user->addGroup(Sentry::findGroupByName($input->role));

		// Else add to the default group
		} else {
			$user->addGroup(Sentry::findGroupByName('admins'));
		}
		
		// Send email
		if (!empty($input->send_email)) {
			
			// Prepare data for mail
			$admin = App::make('decoy.auth')->user();
			$email = array(
				'first_name' => $admin->first_name,
				'last_name' => $admin->last_name,
				'url' => Request::root().'/'.Config::get('decoy::core.dir'),
				'root' => Request::root(),
				'password' => $input->password,
			);
		
			// Send the email
			Mail::send('decoy::emails.create', $email, function($m) use ($input) {
				$m->to($input->email, $input->first_name.' '.$input->last_name);
				$m->subject('Welcome to the '.Config::get('decoy::site.name').' admin site');
				$m->from(Config::get('decoy::core.mail_from_address'), Config::get('decoy::core.mail_from_name'));
			});
		}
		
		// Return the id
		return $user->id;
		
	}
	
	/**
	 * Update admin values
	 */
	public function update(array $attributes = array()) {
		
		// Cast to object
		$input = (object) $attributes;
		
		// Update user
		$user = $this->sentryUser();
		$user->email = $input->email;
		if (!empty($input->password)) $user->password = $input->password;
		$user->first_name = $input->first_name;
		$user->last_name = $input->last_name;
		$user->save();

		// If a role was passed, add the group and remove the old groups
		if (isset($input->role)) {

			// Remove the old group IF it is one of the onese that are listed
			// in the config.  Aka, one of the ones that was actually selectable
			// in the admin.  This keeps, for instance, the developer group attached.
			$keys = array_keys(Config::get('decoy::site.roles'));
			foreach($user->getGroups() as $group) {
				if (!in_array($group->getName(), $keys)) continue;
				$user->removeGroup($group);
			}

			// Add the new group
			$user->addGroup(Sentry::findGroupByName($input->role));
		}
		
		// Send email
		if (!empty($input->send_email)) {
			
			// Prepare data for mail
			$admin = App::make('decoy.auth')->user();
			$email = array(
				'editor_first_name' => $admin->first_name,
				'editor_last_name' => $admin->last_name,
				'first_name' => $input->first_name,
				'last_name' => $input->last_name,
				'email' => $input->email,
				'password' => $input->password,
				'url' => Request::root().'/'.Config::get('decoy::core.dir'),
				'root' => Request::root(),
			);
			
			// Send the email
			Mail::send('decoy::emails.update', $email, function($m) use ($input) {
				$m->to($input->email, $input->first_name.' '.$input->last_name);
				$m->subject('Your '.Config::get('decoy::site.name').' admin account info has been updated');
				$m->from(Config::get('decoy::core.mail_from_address'), Config::get('decoy::core.mail_from_name'));
			});
		}
	}
	
	/**
	 * Delete this admin
	 */
	public function delete() {
		$this->sentryUser()->delete();
	}
	
	/**
	 * Disable an admin
	 */
	public function disable() {
		Sentry::getThrottleProvider()->findByUserId($this->id)->ban();
	}
	
	/**
	 * Enable an admin
	 */
	public function enable() {
		Sentry::getThrottleProvider()->findByUserId($this->id)->unBan();
	}

	/**
	 * Check if admin is banned
	 * @return boolean true if banned
	 */
	public function disabled() {
		return Sentry::getThrottleProvider()->findByUserId($this->id)->isBanned();
	}
	
}
