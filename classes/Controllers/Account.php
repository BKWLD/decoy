<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Auth;
use Bkwld\Decoy\Models\Admin;
use Config;
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

		// If the user is logged in, take them to whatever the home controller should be
		if (App::make('decoy.auth')->check()) return Redirect::to($this->getHome());

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
	 * Determine what the dashboard URL should be, where the user is redirected
	 * after login.
	 *
	 * @return string
	 */
	public function getHome() {

		// Vars
		$config = Config::get('decoy.site.post_login_redirect');
		$auth = App::make('decoy.auth');

		// Make the config optional
		if ($config) {

			// Support the config being a colsure
			if (is_callable($config)) $config = call_user_func($config);

			// Make sure the user has permission before redirecting
			if ($auth->can('read', $config)) return $config;
		}

		// If the user doesn't have permission, iterate through the navigation
		// options until one is permissible
		foreach($this->getNavUrls() as $url) {
			if ($auth->can('read', $url)) return $url;
		}

		// Default to their account page, which all can access
		return $auth->userUrl();
	}

	/**
	 * Return a flat list of all the URLs in the nav.  This doesn't include ones
	 * automatically added by Decoy
	 *
	 * @param  array $nav
	 * @return array
	 */
	public function getNavUrls($nav = null) {

		// If no nav passed, as it would be for a sub navs, get the configed nav
		if (empty($nav)) $nav = Config::get('decoy.site.nav');

		// Allow for the nav to be acallable
		if (is_callable($nav)) $nav = call_user_func($nav);

		// Loop through the nav
		$flat = [];
		foreach($nav as $val) {
			if (is_array($val)) $flat = array_merge($flat, $this->getNavUrls($val));
			else $flat[] = $val;
		}
		return $flat;
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
			return Redirect::to('/'.Config::get('decoy.core.dir'));
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

		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			URL::current() => 'Forgot Password',
		));

		// Show the page
		$this->title = 'Forgot Password';
		$this->description = 'You know the drill.';
		return $this->populateView('decoy::account.forgot');
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
					Config::get('decoy.core.mail_from_address'),
					Config::get('decoy.core.mail_from_name')
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

		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy') => 'Login',
			route('decoy::account@forgot') => 'Forgot Password',
			URL::current() => 'Reset Password',
		));

		// Show the page
		$this->title = 'Reset Password';
		$this->description = 'Almost done.';
		return $this->populateView('decoy::account.reset', [
			'user' => $user,
		]);
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
				return Redirect::to(Config::get('decoy.site.post_login_redirect'));
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
