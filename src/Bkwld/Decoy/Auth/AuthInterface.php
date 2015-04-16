<?php namespace Bkwld\Decoy\Auth;

/**
 * Defines methods necessary to validate a user
 */
interface AuthInterface {
	
	/**
	 * ---------------------------------------------------------------------------
	 * Check perimssions
	 * ---------------------------------------------------------------------------
	 */
	
	/**
	 * Boolean for whether the user is logged in and an admin
	 * @return boolean
	 */
	public function check();

	/**
	 * Get a list of all roles
	 *
	 * @return array 
	 */
	public function roles();

	/**
	 * Check if a user is in a specific role or return the list of all roles
	 * 
	 * @param  string $role
	 * @return boolean
	 */
	public function isRole($role);

	/**
	 * Check if the user has permission to do something
	 * 
	 * @param string $action ex: destroy
	 * @param string $controller 
	 *        - controller instance
	 *        - controller name (Admin\ArticlesController)
	 *        - URL (/admin/articles)
	 *        - slug (articles)
	 * @return boolean
	 */
	public function can($action, $controller);

	/**
	 * ---------------------------------------------------------------------------
	 * Methods for inspecting properties of the user
	 * ---------------------------------------------------------------------------
	 */
	
	/**
	 * Boolean as to whether the user has developer entitlements
	 * 
	 * @return boolean
	 */
	public function developer();
	
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

	/**
	 * ---------------------------------------------------------------------------
	 * URLs & Routes related to authing
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

	

	

}