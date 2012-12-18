<?php namespace Decoy;

/**
 * Defines methods necessary to validate a user
 */
interface Auth_Interface {
	
	// Returns true if the user is logged in and a valid admin user, false if not
	static public function check();

	// These return properites of the logged in user
	static public function photo(); // Avatar photo for the header
	static public function name(); // Name to display in the header for the user
	static public function url(); // URL to the user's profile page in the admin	
}