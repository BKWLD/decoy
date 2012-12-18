<?php namespace Decoy;

/**
 * Defines methods necessary to validate a user
 */
interface Auth_Interface {
	
	// Returns true if the user is logged in and a valid admin user, false if not
	static public function check();
	
	// Urls related to the login process
	static public function login_action();
	static public function logout_url();
	static public function denied_url();

	// These return properites of the logged in user
	static public function user_photo(); // Avatar photo for the header
	static public function user_name(); // Name to display in the header for the user
	static public function user_url(); // URL to the user's profile page in the admin	
}