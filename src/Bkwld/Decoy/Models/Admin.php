<?php namespace Bkwld\Decoy\Models;

// Deps
use Bkwld\Upchuck\SupportsUploads;
use Bkwld\Library\Utils\String;
use Config;
use Decoy;
use DecoyURL;
use HTML;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Hash;
use Input;
use Mail;
use Request;
use URL;

class Admin extends Base implements AuthenticatableContract, CanResetPasswordContract {
	use Authenticatable, CanResetPassword;

	/**
	 * The table associated with the model.  Explicitly declaring so that sub
	 * classes can use it
	 *
	 * @var string
	 */
	protected $table = 'admins';

	/**
	 * Uploadable attributes
	 * 
	 * @var array
	 */
	protected $upload_attributes = ['image'];

	/**
	 * Don't allow cloning because duplicate emails are not allowed.
	 *
	 * @var boolean 
	 */
	public $cloneable = false;

	/**
	 * Admins should not be localized
	 *
	 * @var boolean
	 */
	static public $localizable = false;

	/**
	 * Validation rules
	 * 
	 * @var array
	 */
	public static $rules = [
		'first_name' => 'required',
		'last_name' => 'required',
		'image' => 'image',
		'email' => 'required|email|unique:admins,email',
		'password' => 'required',
		'confirm_password' => 'sometimes|required_with:password|same:password',
	];

	/**
	 * Orders instances of this model in the admin
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return void
	 */
	public function scopeOrdered($query) {
		$query->orderBy('last_name')->orderBy('first_name');
	}

	/**
	 * Tweak some validation rules
	 *
	 * @param Illuminate\Validation\Validator $validation
	 */
	public function onValidating($validation) {

		// Only apply mods when editing an existing record
		if (!$this->exists) return;
		$rules = self::$rules;

		// Make password optional
		$rules = array_except($rules, 'password');

		// Ignore the current record when validating email
		$rules['email'] .= ','.$this->id;
		
		// Update rules
		$validation->setRules($rules);
	}

	/**
	 * New admin callbacks
	 *
	 * @return void
	 */
	public function onCreating() {

		// Send out email
		if (Input::has('_send_email')) $this->sendCreateEmail();

		// Make them active
		$this->active = 1;

		// If the current user can't grant permissions, make the new admin
		// have the same role as themselves.  Admins created from the CLI (like as
		// part of a migration) won't be logged in.
		if (($admin = app('decoy.auth')->user())
			&& !app('decoy.auth')->can('grant', 'admins')) {
			$this->role = $admin->role;
		}
	}

	/**
	 * Admin updating callbacks
	 *
	 * @return void 
	 */
	public function onUpdating() {
		if (Input::has('_send_email')) $this->sendUpdateEmail();
	}

	/**
	 * Callbacks regardless of new or old
	 *
	 * @return void 
	 */
	public function onSaving() {

		// If the password is changing, hash it
		if ($this->isDirty('password')) {
			$this->password = Hash::make($this->password);
		}

		// Save or clear permission choices if the form had a "custom permissions"
		// pushed checkbox
		if (Input::exists('_custom_permissions')) {
			$this->permissions = Input::get('_custom_permissions') ? 
				json_encode(Input::get('_permission')) : null;
		}
	}

	/**
	 * Send creation email
	 *
	 * @return void 
	 */
	public function sendCreateEmail() {

		// Prepare data for mail
		$admin = app('decoy.auth')->user();
		$email = array(
			'first_name' => $admin->first_name,
			'last_name' => $admin->last_name,
			'email' => Input::get('email'),
			'url' => Request::root().'/'.Config::get('decoy.core.dir'),
			'root' => Request::root(),
			'password' => Input::get('password'),
		);
	
		// Send the email
		Mail::send('decoy::emails.create', $email, function($m) use ($email) {
			$m->to($email['email'], $email['first_name'].' '.$email['last_name']);
			$m->subject('Welcome to the '.Decoy::site().' admin site');
			$m->from(Config::get('decoy.core.mail_from_address'), Config::get('decoy.core.mail_from_name'));
		});
	}

	/**
	 * Send update email
	 *
	 * @return void 
	 */
	public function sendUpdateEmail() {
		
		// Prepare data for mail
		$admin = app('decoy.auth')->user();
		$email = array(
			'editor_first_name' => $admin->first_name,
			'editor_last_name' => $admin->last_name,
			'first_name' =>Input::get('first_name'),
			'last_name' =>Input::get('last_name'),
			'email' => Input::get('email'),
			'password' =>Input::get('password'),
			'url' => Request::root().'/'.Config::get('decoy.core.dir'),
			'root' => Request::root(),
		);
		
		// Send the email
		Mail::send('decoy::emails.update', $email, function($m) use ($email) {
			$m->to($email['email'], $email['first_name'].' '.$email['last_name']);
			$m->subject('Your '.Decoy::site().' admin account info has been updated');
			$m->from(Config::get('decoy.core.mail_from_address'), Config::get('decoy.core.mail_from_name'));
		});
	}

	/**
	 * A shorthand for getting the admin name as a string
	 *
	 * @return string 
	 */
	public function getNameAttribute() {
		return $this->getAdminTitleAttribute();
	}

	/**
	 * Produce the title for the list view
	 * 
	 * @return string
	 */
	public function getAdminTitleHtmlAttribute() {
		if (isset($this->image)) return parent::getAdminTitleHtmlAttribute();
		return "<img src='".response()->gravatar($this->email)."' class='gravatar'/> "
			.$this->getAdminTitleAttribute();
	}

	/**
	 * Show a badge if the user is the currently logged in
	 * 
	 * @return string
	 */
	public function getAdminStatusAttribute() {
		$html ='';
		
		// Add the role
		if (($roles = static::getRoleTitles()) && count($roles)) {
			$html .= '<span class="label label-primary">'.$roles[$this->role].'</span>';
		}

		// If row is you
		if ($this->id == app('decoy.auth')->user()->id) {
			$html .= '<span class="label label-info">You</span>';
		}

		// If row is disabled
		if ($this->disabled()) {
			$html .= '<a href="'.URL::to(DecoyURL::relative('enable', $this->id)).'" class="label label-warning js-tooltip" title="Click to enable login">Disabled</a>';
		}

		// Return HTML
		return $html;
	}

	/**
	 * Get the URL to edit the admin
	 *
	 * @return string 
	 */
	public function getAdminEditAttribute() {
		return DecoyURL::action('Bkwld\Decoy\Controllers\Admins@edit', $this->id);
	}

	/**
	 * Get the permissions for the admin
	 *
	 * @return stdObject
	 */
	public function getPermissionsAttribute() {
		if (!$this->permissions) return null;
		return json_decode($this->permissions);
	}

	/**
	 * Make a list of the role titles by getting just the text between bold tags 
	 * in the roles config array, which is a common convention in Decoy 4.x
	 *
	 * @return array
	 */
	static public function getRoleTitles() {
		return array_map(function($title) {
			if (preg_match('#^<b>(\w+)</b>#i', $title, $matches)) return $matches[1];
			return $title;
		}, Config::get('decoy.site.roles'));
	}

	/**
	 * Get the list of all permissions
	 *
	 * @param Admin|null $admin
	 * @return array
	 */
	static public function getPermissionOptions($admin = null) {

		// Get all the app controllers
		$controllers = array_map(function($path) {
			return 'Admin\\'.basename($path, '.php');
		}, glob(app_path().'/controllers/Admin/*Controller.php'));

		// Remove some classes
		$controllers = array_diff($controllers, ['Admin\BaseController']);

		// Add some Decoy controllers
		$controllers[] = 'Bkwld\Decoy\Controllers\Admins';
		$controllers[] = 'Bkwld\Decoy\Controllers\Changes';
		$controllers[] = 'Bkwld\Decoy\Controllers\Elements';
		$controllers[] = 'Bkwld\Decoy\Controllers\RedirectRules';

		// Alphabetize the controller classes
		usort($controllers, function($a, $b) {
			return substr($a, strrpos($a, '\\') + 1) > substr($b, strrpos($b, '\\') + 1);
		});

		// Convert the list of controller classes into the shorthand strings used
		// by Decoy Auth as well as english name and desciption
		return array_map(function($class) use ($admin) {
			$obj = new $class;
			$permissions = $obj->getPermissionOptions();
			if (!is_array($permissions)) $permissions = [];

			// Build the controller-level node
			return (object) [

				// Add controller information
				'slug' => DecoyURL::slugController($class),
				'title' => $obj->title(),
				'description' => $obj->description(),

				// Add permission options for the controller 
				'permissions' => array_map(function($value, $action) use ($class, $admin) {
					$roles = array_keys(Config::get('decoy.site.roles'));
					return (object) [
						'slug' => $action,
						'title' => is_array($value) ? $value[0] : String::titleFromKey($action),
						'description' => is_array($value) ? $value[1] : $value,

						// Set the initial checked state based on the admin's permissions, if
						// one is set.  Or based on the first role.
						'checked' => $admin ? 
							app('decoy.auth')->can($action, $class, $admin) :
							app('decoy.auth')->can($action, $class, $roles[0]),

						// Filter the list of roles to just the roles that allow the
						// permission currently being iterated through
						'roles' => array_filter($roles, function($role) use ($action, $class) {
							return app('decoy.auth')->can($action, $class, $role);
						}),

					];
				}, $permissions, array_keys($permissions)),
			];
		}, $controllers);
	}

	/**
	 * Check if admin is banned
	 * 
	 * @return boolean true if banned
	 */
	public function disabled() {
		return !$this->active;
	}

}
