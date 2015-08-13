<?php namespace Bkwld\Decoy\Auth;

// Deps
use Config;
use DecoyURL;
use HTML;
use Illuminate\Auth\AuthManager;
use Request;

/**
 * Authentication using Eloquent queries. This is the Decoy default
 */
class Eloquent implements AuthInterface {

	/**
	 * @var Illuminate\Auth\AuthManager 
	 */
	private $auth;

	/**
	 * Dependency injection
	 * 
	 */
	public function __construct(AuthManager $auth) {
		$this->auth = $auth;

		// Tell Laravel that we want to use a different admin model.  Putting this
		// logic here so that Decoy\Auth usage from outside the admin (like the 
		// disabling until live filter check) will use the right model.
		Config::set('auth.model', 'Bkwld\Decoy\Models\Admin');
		Config::set('auth.reminder.email', 'decoy::emails.reset');
	}

	/**
	 * ---------------------------------------------------------------------------
	 * Check perimssions
	 * ---------------------------------------------------------------------------
	 */

	/**
	 * Boolean for whether the user is logged in and an admin
	 * 
	 * @return boolean
	 */
	public function check() {
		return $this->auth->check();
	}

	/**
	 * Get a list of all roles
	 *
	 * @return array 
	 */
	public function roles() {
		$roles = Config::get('decoy.site.permissions');
		if (!is_array($roles)) return [];
		return array_keys($roles);
	}

	/**
	 * Check if a user is in a specific role or return the list of all roles
	 * 
	 * @param  string $role
	 * @return boolean
	 */
	public function is($role) {
		return $this->user()->role == $role;
	}

	/**
	 * Check if the user has permission to do something
	 * 
	 * @param string $action ex: destroy
	 * @param string $controller 
	 *        - controller instance
	 *        - controller name (Admin\ArticlesController)
	 *        - URL (/admin/articles)
	 *        - slug (articles)
	 * @param string|Admin|null $who
	 *        - if string, treat as a role
	 *        - if Admin instance, treat as an admin user
	 *        - if null, use the current user
	 * @return boolean
	 */
	public function can($action, $controller, $who = null) {

		// They must be logged in
		if (!$this->check()) return false;

		// If no permissions have been defined, do nothing.
		if (!Config::has('decoy::site.permissions')) return true;

		// Convert controller instance to it's name
		if (is_object($controller)) $controller = get_class($controller);

		// Get the slug version of the controller.  Test if a URL was passed first
		// and, if not, treat it like a full controller name.  URLs are used in the 
		// nav. Also, an already slugified controller name will work fine too.
		if (preg_match('#/'.Config::get('decoy.core.dir').'/([^/]+)#', $controller, $matches)) {
			$controller = $matches[1];
		} else $controller = DecoyURL::slugController($controller);

		// Always allow an admin to edit themselves for changing password.  Other
		// features will be disabled from the view file.
		if ($controller == 'admins' 
			&& ($action == 'read' 
			|| ($action == 'update' && Request::segment(3) == $this->user()->id))) {
			return true;
		}

		// Always let developers access workers and commands
		if (in_array($controller, ['workers', 'commands']) && $this->developer($who)) {
			return true;
		}

		// If no `who` (and using logged in user) or the `who` is an admin and if
		// that admin has permissiosn, test if they have access to the action using
		// the array of permitted actions.
		if ((is_a($who, 'Bkwld\Decoy\Models\Admin') 
				&& ($permissions = $who->getPermissionsAttribute()))
			|| (is_null($who) 
				&& ($permissions = $this->user()->getPermissionsAttribute()))) {

			// Check that the controller was defined in the permissions
			if (!isset($permissions->$controller) 
				|| !is_array($permissions->$controller)) return false;

			// When interacting with elements, allow as long as there is at least one
			// page they have access to.  Rely on the elements controller to enforce
			// additional restrictions.
			if ($controller == 'elements' && in_array($action, ['read', 'create'])) {
				return count($permissions->elements) > 0;
			}

			// Default behavoir checks that the action was checked in the permissions
			// UI for the controller.
			return in_array($action, $permissions->$controller);
		}

		// If `who` was passed in as a string, treat is as a role.  Otherwise, get 
		// the current user's role.
		if (is_string($who)) $role = $who;
		elseif (is_a($who, 'Bkwld\Decoy\Models\Admin')) $role = $who->role;
		else $role = $this->user()->role;

		// If there are "can" rules, then apply them as a whitelist.  Only those
		// actions are allowed.
		$can = Config::get('decoy.site.permissions.'.$role.'.can');
		if (is_callable($can)) $can = call_user_func($can, $action, $controller);
		if (is_array($can) &&
			!in_array($action.'.'.$controller, $can) && 
			!in_array('manage.'.$controller, $can)) return false;

		// If the action is listed as "can't" then immediately deny.  Also check for
		// "manage" which means they can't do ANYTHING
		$cant = Config::get('decoy.site.permissions.'.$role.'.cant');
		if (is_callable($cant)) $cant = call_user_func($cant, $action, $controller);
		if (is_array($cant) && (
			in_array($action.'.'.$controller, $cant) ||
			in_array('manage.'.$controller, $cant))) return false;

		// I guess we're good to go
		return true;
	}

	/**
	 * ---------------------------------------------------------------------------
	 * Methods for inspecting properties of the user
	 * ---------------------------------------------------------------------------
	 */
	
	/**
	 * Return the authed Admin model
	 *
	 * @return Bkwld\Decoy\Models\Admin
	 */
	public function user() {
		return $this->auth->user();
	}

	/**
	 * Boolean as to whether the user has developer entitlements
	 *
	 * @param Bkwld\Decoy\Models\Admin $user
	 * @return boolean
	 */
	public function developer($user = null) {
		if (!$user) $user = $this->user();
		return $user->role == 'developer' || strpos($user->email, 'bkwld.com');
	}

	/**
	 * Avatar photo for the header
	 * 
	 * @return string
	 */
	public function userPhoto() {
		return $this->user()->croppa(80, 80) ?: response()->gravatar($this->user()->email);
	}

	/**
	 * Name to display in the header for the user
	 * 
	 * @return string
	 */
	public function userName() {
		return $this->user()->first_name;
	}

	/**
	 * URL to the user's profile page in the admin
	 * 
	 * @return string
	 */
	public function userUrl() {
		return $this->user()->getAdminEditAttribute();
	}
	
	/**
	 * ---------------------------------------------------------------------------
	 * URLs & Routes related to authing
	 * ---------------------------------------------------------------------------
	 */

	/**
	 * URL that contains the login page
	 * 
	 * @return string
	 */
	public function loginAction() {
		return 'Bkwld\Decoy\Controllers\Account@login';
	}
	
	/**
	 * URL to process logging out
	 * 
	 * @return string
	 */
	public function logoutUrl() {
		return route('decoy::account@logout');
	}
	
	/**
	 * URL to redirect to if a user doesn't have permission for the page
	 * 
	 * @return string
	 */
	public function deniedUrl() {
		return action($this->loginAction());
	}

}
