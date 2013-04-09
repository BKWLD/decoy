<?php namespace Bkwld\Decoy\Auth;

/**
 * This class abstracts the Sentry methods that are used globally
 * in Decoy, like for checking if a user is logged in and an admin.
 * It also allows developers to use a different authentication system
 * by setting their own Auth handler class in the Decoy config
 */
class Sentry implements AuthInterface {
	
	// ---------------------------------------------------------------
	// Methods for inspecting properties of the user
	// ---------------------------------------------------------------
	
	// Boolean for whether the user is logged in and an admin
	static public function check() {
		return false;
		// return \Sentry::check() && \Sentry::user()->in_group('admins');
	}
	
	// The logged in user's permissions role
	static public function role() {
		return \Sentry::user()->groups();
	}

	// Boolean as to whether the user has developer entitlements
	static public function developer() {
		return strpos(\Sentry::user()->get('email'), '@bkwld.com') !== false;
	}
	
	// ---------------------------------------------------------------
	// Urls related to the login process
	// ---------------------------------------------------------------
	
	// Controller action that renders the login form
	static public function login_action() {
		return 'Bkwld\Decoy\Controllers\Account@getLogin';
	}
	
	// URL to go to that will process their logout
	static public function logout_url() {
		return action('admin.account@logout');
	}
	
	// The URL to if they don't have access
	static public function denied_url() {
		return action('decoy::account@login');
	}
	
	// ---------------------------------------------------------------
	// These return properites of the logged in user
	// ---------------------------------------------------------------
	
	// Get their photo
	static public function user_photo() {
		return \Laravel\HTML::gravatar(\Sentry::user()->get('email'));
	}
	
	// Get their name
	static public function user_name() {
		return \Sentry::user()->get('metadata.first_name');
	}
	
	// Get the URL to their profile
	static public function user_url() {
		return action('decoy::admins', array(self::user_id()));
	}
	
	// Get their id
	static public function user_id() {
		return \Sentry::user()->get('id');
	}
	
}