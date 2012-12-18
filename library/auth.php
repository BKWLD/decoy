<?php namespace Decoy;

/**
 * This class abstracts the Sentry methods that are used globally
 * in Decoy, like for checking if a user is logged in and an admin.
 * It also allows developers to use a different authentication system
 * by setting their own Auth handler class in the Decoy config
 */
class Auth implements Auth_Interface {
	
	// Check if the user is an admin
	static public function check() {
		return \Sentry::check() && \Sentry::user()->in_group('admins');
	}
	
	// Controller action that renders the login form
	static public function login_action() {
		return 'decoy::account@login';
	}
	
	// URL to go to that will process their logout
	static public function logout_url() {
		return action('admin.account@logout');
	}
	
	// The URL to if they don't have access
	static public function denied_url() {
		return action('decoy::account@login');
	}
	
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
		return action('admin.account');
	}
	
}