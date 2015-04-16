<?php namespace Bkwld\Decoy\Auth;

/**
 * Defines methods necessary to validate a user
 */
interface AuthInterface {
	
	/**
	 * ---------------------------------------------------------------------------
	 * Methods for inspecting properties of the user
	 * ---------------------------------------------------------------------------
	 */

	/**
	 * Boolean for whether the user is logged in and an admin
	 * @return boolean
	 */
	public function check();

	/**
	 * Check if a user is in a specific role or return the list of all roles
	 * 
	 * @param  string $role
	 * @return boolean | array
	 */
	public function role($role = null);
	
	/**
	 * Boolean as to whether the user has developer entitlements
	 * 
	 * @return boolean
	 */
	public function developer();
	
	/**
	 * ---------------------------------------------------------------------------
	 * Methods for inspecting properties of the user
	 * ---------------------------------------------------------------------------
	 */

	/**
	 * URL that contains the login page
	 * 
	 * @return string
	 */
	public function loginAction();
	
	/**
	 * URL to process logging out
	 * 
	 * @return string
	 */
	public function logoutUrl();
	
	/**
	 * URL to redirect to if a user doesn't have permission for the page
	 * 
	 * @return string
	 */
	public function deniedUrl();

	/**
	 * ---------------------------------------------------------------------------
	 * These return properites of the logged in user
	 * ---------------------------------------------------------------------------
	 */

	/**
	 * Avatar photo for the header
	 * 
	 * @return string
	 */
	public function userPhoto();

	/**
	 * Name to display in the header for the user
	 * 
	 * @return string
	 */
	public function userName();

	/**
	 * URL to the user's profile page in the admin
	 * 
	 * @return string
	 */
	public function userUrl();
}