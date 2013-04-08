<?php namespace Decoy;

/**
 * Defines methods necessary to validate a user
 */
interface Auth_Interface {
	
	// Methods for inspecting properties of the user
	static public function check();        // Boolean for whether the user is logged in and an admin
	static public function role();         // The logged in user's permissions role
	static public function developer();    // Boolean as to whether the user has developer entitlements
	
	// Urls related to the login process
	static public function login_action(); // URL that contains the login page
	static public function logout_url();   // URL to process logging out
	static public function denied_url();   // URL to redirec to if a user doesn't have permission for the page

	// These return properites of the logged in user
	static public function user_photo();   // Avatar photo for the header
	static public function user_name();    // Name to display in the header for the user
	static public function user_url();     // URL to the user's profile page in the admin	
}