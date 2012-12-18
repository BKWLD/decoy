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
	
	// Get their photo
	static public function photo() {
		
	}
	
	// Get their name
	static public function name() {
		
	}
	
	// Get the URL to their profile
	static public function url() {
		
	}
	
}