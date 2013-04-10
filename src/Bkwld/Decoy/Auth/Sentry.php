<?php namespace Bkwld\Decoy\Auth;

// Dependencies
use \Html;

/**
 * This class abstracts the Sentry methods that are used globally
 * in Decoy, like for checking if a user is logged in and an admin.
 * It also allows developers to use a different authentication system
 * by setting their own Auth handler class in the Decoy config
 */
class Sentry implements AuthInterface {
	
	// Get the logged in user
	static public function user() {
		if (!\Sentry::check()) return false; // Are they logged in
		try {
			return \Sentry::getUser(); // Return the user
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
			return false; // The logged in user couldn't be found in DB
		}
	}
	
	// ---------------------------------------------------------------
	// Methods for inspecting properties of the user
	// ---------------------------------------------------------------
	
	// Boolean for whether the user is logged in and an admin
	static public function check() {
		
		// Get the logged in user
		if (!($user = self::user())) return false;
		
		// Make sure they have admin privledges.  This may be granted even if
		// they aren't in the "admins" group, though it is the most common way
		return $user->hasAccess('admin');
	}
	
	// The logged in user's permissions role
	static public function role() {
		if (!($user = self::user())) return null;
		return $user->getGroups();
	}

	// Boolean as to whether the user has developer entitlements
	static public function developer() {
		if (!($user = self::user())) return false; // They must be logged in
		if ($user->hasAccess('developer')) return true; // They must be a developer
		return strpos($user->email, '@bkwld.com') !== false; // ... or have bkwld in their email
	}
	
	// ---------------------------------------------------------------
	// Urls related to the login process
	// ---------------------------------------------------------------
	
	// Controller action that renders the login form
	static public function loginAction() {
		return 'Bkwld\Decoy\Controllers\Account@login';
	}
	
	// URL to go to that will process their logout
	static public function logoutUrl() {
		return route('decoy\logout');
	}
	
	// The URL to if they don't have access
	static public function deniedUrl() {
		return action(self::loginAction());
	}
	
	// ---------------------------------------------------------------
	// These return properites of the logged in user
	// ---------------------------------------------------------------
	
	// Get their photo
	static public function userPhoto() {
		if (!($user = self::user())) return null;
		return Html::gravatar($user->email);
	}
	
	// Get their name
	static public function user_name() {
		if (!($user = self::user())) return null;
		return $user->first_name;
	}
	
	// Get the URL to their profile
	static public function userUrl() {
		return action('Bkwld\Decoy\Controllers\Admins@edit', self::user_id());
	}
	
	// Get their id
	static public function user_id() {
		if (!($user = self::user())) return null;
		return $user->id;
	}
	
}