<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Auth;
use Bkwld\Decoy\Models\Admin;
use Decoy;
use Exception;
use Former;
use Input;
use Lang;
use Password;
use Redirect;
use Session;
use URL;
use View;

/**
 * This deals with the login / forgot password interfaces
 */
class Account extends Base {
	
	/**
	 * Redirect to a page where the user can manage their account
	 *
	 * @return Illuminate\View\View
	 */
	public function index() {
		return Redirect::to(App::make('decoy.auth')->userUrl());
	}

	/**
	 * Display the login form
	 *
	 * @return Illuminate\View\View
	 */
	public function login() {

		// Remember where they attempted to go to if they were dropped here from a
		// ACL deny.  This keeps the value around for another request
		if (Session::has('login_redirect')) Session::keep('login_redirect');
		
		// If the user is logged in, take them to whatever the dashboard should be
		if (App::make('decoy.auth')->check()) return Redirect::to(config('decoy.site.post_login_redirect'));
		
		// Pass validation rules
		Former::withRules(array(
			'email'    => 'required|email',
			'password' => 'required',
		));
		
		// Show the login homepage
		View::inject('title', 'Login');
		return View::make('decoy::layouts.blank', array(
			'content' => View::make('decoy::account.login')->render()
		));
	}
	
	/**
	 * Process a sign in from the main login form
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function post() {

		// Test submission
		if (Auth::attempt([
			'email'    => Input::get('email'), 
			'password' => Input::get('password'),
			'active'   => 1,
			], Input::get('remember'))) {

			// On success, redirect where they intended to go
			return Redirect::intended(URL::current());

		// On fail, redirect back and show error
		} else {
			return $this->loginError('Your email or password could not be found.');
		}
	}

	/**
	 * Log a user out
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function logout() {

		// Logout session
		Auth::logout();

		// I've gotten errors when going directly to this route
		try { 
			return Redirect::back();
		} catch(Exception $e) {
			return Redirect::to('/'.config('decoy.core.dir'));
		}
	}

	/**
	 * ---------------------------------------------------------------------------
	 * The following is based on:
	 * /vendor/laravel/framework/src/Illuminate/Auth/Console/stubs/controller.stub
	 * ---------------------------------------------------------------------------
	 */
	
	/**
	 * Display the form to begin the reset password process
	 */
	public function forgot() {

		// Pass validation rules
		Former::withRules(array(
			'email' => 'required|email',
		));

		// Show the page
		$this->title = 'Forgot Password';
		$this->description = 'You know the drill.';
		$this->populateView('decoy::account.forgot');
		
		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			URL::current() => 'Forgot Password',
		));
		
	}
	
	/**
	 * Sent the user an email with a reset password link
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function postForgot() {

		// Send reminder
		switch ($response = Password::remind(Input::only('email'), 

			// Add subject and from
			function($m, $user, $token) {
				$m->subject('Recover access to '.Decoy::site());
				$m->from(
					config('decoy.core.mail_from_address'), 
					config('decoy.core.mail_from_name')
				);
			})) {

			// Failure
			case Password::INVALID_USER:
				return $this->loginError(Lang::get($response));
			
			// Success
			case Password::REMINDER_SENT:
				return Redirect::back()->with('success', Lang::get($response));
		}
		
	}
	
	/**
	 * Show the user the password reset form
	 * 
	 * @param $string token A reset password token
	 * @return void
	 */
	public function reset($token) {
		
		// Pass validation rules
		Former::withRules(array(
			'email'                 => 'required|email',
			'password'              => 'required',
			'password_confirmation' => 'required|same:password',
		));

		// Lookup the admin
		$user = Admin::where('token', $token)
			->join('password_reminders', 'password_reminders.email', '=', 'admins.email')
			->firstOrFail();

		// Show the page
		$this->title = 'Reset Password';
		$this->description = 'Almost done.';
		$this->populateView('decoy::account.reset', [
			'user' => $user,
		]);

		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			route('decoy::account@forgot') => 'Forgot Password',
			URL::current() => 'Reset Password',
		));
	}
	
	/**
	 * Set a new password for a user and sign them in
	 * 
	 * @param $string token A reset password token
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function postReset($token) {

		// Gather input
		$credentials = Input::only('email', 'password', 'password_confirmation');
		$credentials['token'] = $token;

		// Save their creds
		$response = Password::reset($credentials, function($user, $password) {
			$user->password = $password; // Gets hashed via model callback
			$user->save();
		});

		// Respond
		switch ($response) {
			case Password::INVALID_PASSWORD:
			case Password::INVALID_TOKEN:
			case Password::INVALID_USER:
				return $this->loginError(Lang::get($response));
			case Password::PASSWORD_RESET:
				Auth::login(Admin::where('email', Input::get('email'))->first());
				return Redirect::to(config('decoy.site.post_login_redirect'));
		}
	}

	/**
	 * Redirect with a login error
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	private function loginError($msg, $url = null) {
		return Redirect::to($url ? $url : URL::current())
		->withErrors([ 'error message' => $msg])
		->withInput();
	}

}
