<?php namespace Bkwld\Decoy\Auth;

/**
 * Defines methods necessary to validate a user
 */
interface AuthInterface {
	
	// Methods for inspecting properties of the user
	public function check();            // Boolean for whether the user is logged in and an admin
	public function role($role = null); // Check if a user is in a specific role or return the list of all roles
	public function developer();        // Boolean as to whether the user has developer entitlements
	
	// Urls related to the login process
	public function loginAction();      // URL that contains the login page
	public function logoutUrl();        // URL to process logging out
	public function deniedUrl();        // URL to redirec to if a user doesn't have permission for the page

	// These return properites of the logged in user
	public function userPhoto();        // Avatar photo for the header
	public function userName();         // Name to display in the header for the user
	public function userUrl();          // URL to the user's profile page in the admin	
}