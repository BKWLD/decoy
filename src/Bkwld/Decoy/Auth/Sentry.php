<?php namespace Bkwld\Decoy\Auth;

// Dependencies
use Cartalyst\Sentry\Users\UserNotFoundException;
use DecoyURL;
use Html;
use Sentry as CartalystSentry;

/**
 * This class abstracts the Sentry methods that are used globally
 * in Decoy, like for checking if a user is logged in and an admin.
 * It also allows developers to use a different authentication system
 * by setting their own Auth handler class in the Decoy config
 */
class Sentry implements AuthInterface {
	
	// Get the logged in user
	private $user; // Cache an instance to reduce DB queries looking up a user
	public function user() {
		if (!empty($this->user)) return $this->user; // Use cached user
		try {
			if (!CartalystSentry::check()) return false; // Are they logged in
			$this->user = CartalystSentry::getUser(); 
			return $this->user;
		} catch (UserNotFoundException $e) {
			return false; // The logged in user couldn't be found in DB
		}
	}
	
	// ---------------------------------------------------------------
	// Methods for inspecting properties of the user
	// ---------------------------------------------------------------
	
	// Boolean for whether the user is logged in and an admin
	public function check() {
		
		// Get the logged in user
		if (!($user = $this->user())) return false;
		
		// Make sure they have admin privledges.  This may be granted even if
		// they aren't in the "admins" group, though it is the most common way
		return $user->hasAccess('admin');
	}
	
	// The logged in user's permissions role
	public function role() {
		if (!($user = $this->user())) return null;
		return $user->getGroups();
	}

	// Boolean as to whether the user has developer entitlements
	public function developer() {
		if (!($user = $this->user())) return false; // They must be logged in
		if ($user->hasAccess('developer')) return true; // They must be a developer
		return strpos($user->email, '@bkwld.com') !== false; // ... or have bkwld in their email
	}
	
	// ---------------------------------------------------------------
	// Urls related to the login process
	// ---------------------------------------------------------------
	
	// Controller action that renders the login form
	public function loginAction() {
		return 'Bkwld\Decoy\Controllers\Account@login';
	}
	
	// URL to go to that will process their logout
	public function logoutUrl() {
		return route('decoy\account@logout');
	}
	
	// The URL to if they don't have access
	public function deniedUrl() {
		return action($this->loginAction());
	}
	
	// ---------------------------------------------------------------
	// These return properites of the logged in user
	// ---------------------------------------------------------------
	
	// Get their photo
	public function userPhoto() {
		if (!($user = $this->user())) return null;
		return HTML::gravatar($user->email);
	}
	
	// Get their name
	public function userName() {
		if (!($user = $this->user())) return null;
		return $user->first_name;
	}
	
	// Get the URL to their profile
	public function userUrl() {
		return DecoyURL::action('Bkwld\Decoy\Controllers\Admins@edit', $this->userId());
	}
	
	// Get their id
	public function userId() {
		if (!($user = $this->user())) return null;
		return $user->id;
	}
	
}