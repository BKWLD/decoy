<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use \Config;
use \DecoyAuth;
use \Exception;
use \Former;
use \Input;
use \Mail;
use \Redirect;
use \Section;
use \Sentry;
use \Session;
use \URL;
use \View;

// The account controller deals with auth
class Account extends Base {
	
	// Validation rules for resetting password
	private $forgot_rules = array('email' => 'required|email');
	private $reset_rules = array('password' => 'required', 'password_repeat' => 'required|same:password');
	private $reset_msgs = array('same' => 'The passwords do not match');
	
	/**
	 * Redirect to a page where the user can manager their account
	 */
	public function index() {
		return Redirect::to(DecoyAuth::userUrl());
	}

	/**
	 * Display the login form
	 */
	public function login() {

		// Remember where they attempted to go to if they were dropped here from a
		// ACL deny.  This keeps the value around for another request
		Session::keep('login_redirect');
		
		// If the user is logged in, take them to whatever the dashboard should be
		if (DecoyAuth::check()) return Redirect::action(Config::get('decoy::post_login_redirect'));
		
		// Pass validation rules
		Former::withRules(array(
			'email' => 'required|email',
			'password' => 'required',
		));
		
		// Show the login homepage
		View::inject('title', 'Login');
		$this->layout->nest('content', 'decoy::account.login');
	}
	
	/**
	 * Process a sign in from the main login form
	 */
	public function post() {
		try {
			
			// Attempt to login
			Sentry::authenticate(array(
				'email' => Input::get('email'),
				'password' => Input::get('password'),
			), Input::get('is_remember') == 1);
			
			// Login must have succeeded
			return Redirect::to(Session::get('login_redirect', URL::current()));

		// Make more easily read errros
		} catch (\Cartalyst\Sentry\Users\LoginRequiredException $e) {
			return $this->loginError('Email is required.');
		} catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e) {
			return $this->loginError('Password is required.');
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
			return $this->loginError('Those credentials could not be found.');
		} catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e) {
			return $this->loginError('Your ability to login has been suspended for '.Config::get('cartalyst/sentry::sentry.throttling.suspension_time').' minutes.');
		
		// Handle other errrors
		} catch (Exception $e) {			
			return $this->loginError($e->getMessage());
		}

	}

	/**
	 * Log a user out
	 */
	public function logout() {
		Sentry::logout();
		
		// I've gotten errors when going directly to this route
		try { 
			return Redirect::back();
		} catch(Exception $e) {
			return Redirect::to('/'.Config::get('decoy::dir'));
		}
	}
	
	/**
	 * Display the form to begin the reset password process
	 */
	public function forgot() {
		
		// Pass validation rules
		Former::withRules($this->forgot_rules);

		// Show the page
		View::inject('title', 'Forgot Password');
		$this->layout->nest('content', 'decoy::account.forgot');
		
		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			URL::current() => 'Forgot Password',
		));
		
	}
	
	/**
	 * Sent the user an email with a reset password link
	 */
	public function postForgot() {
		
		// Validate
		if ($result = $this->validate($this->forgot_rules)) return $result;

		// Find the user using the user email address
		try {
			$user = Sentry::getUserProvider()->findByLogin(Input::get('email'));
			$code = $user->getResetPasswordCode();
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
			return $this->loginError('That email could not be found.');
		}

		// Form the link
		$url = route('decoy\account@reset', $code);

		// Send an email to the user with the reset token
		Mail::send('decoy::emails.reset', array('url' => $url), function($m) {
			$m->to(Input::get('email'));
			$m->subject('Recover access to the '.Config::get('decoy::site_name').' CMS');
			$m->from(Config::get('decoy::mail_from_address'), Config::get('decoy::mail_from_name'));
		});
		
		// Redirect back to login page
		return Redirect::route('decoy\account@forgot')
			->with('login_notice', 'An email with a link to reset your password has been sent.');
		
	}
	
	/**
	 * Show the user the password reset form
	 * @param $string code A sentry reset password code
	 */
	public function reset($code) {
		
		// Look up the user
		try {
			$user = Sentry::getUserProvider()->findByResetPasswordCode($code);
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
			return $this->loginError('The reset password code is not valid', route('decoy\account@forgot'));
		}
		
		// Pass validation rules
		Former::withRules($this->reset_rules, $this->reset_msgs);

		// Show the page
		View::inject('title', 'Reset Password');
		$this->layout->nest('content', 'decoy::account.reset', array(
			'user' => $user,
		));
		
		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			route('decoy\account@forgot') => 'Forgot Password',
			URL::current() => 'Reset Password',
		));
		
	}
	
	/**
	 * Set a new password for a user and sign them in
	 * @param $string code A sentry reset password code
	 */
	public function postReset($code) {
		
		// Look up the user
		try {
			$user = Sentry::getUserProvider()->findByResetPasswordCode($code);
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
			return $this->loginError('The reset password code is not valid', route('decoy\account@forgot'));
		}
		
		// Validate
		if ($result = $this->validate($this->reset_rules, $this->reset_msgs)) return $result;
		
		// Replace their password
		$user->attemptResetPassword($code, Input::get('password'));
		
		// Log them in
		Sentry::login($user, false);
		
		// Redirect
		return Redirect::action(Config::get('decoy::post_login_redirect'));
	
	}

	/**
	 * Redirect with a login error
	 */
	private function loginError($msg, $url = null) {
		return Redirect::to($url ? $url : URL::current())
		->with('login_error', $msg)
		->withInput();
	}

}