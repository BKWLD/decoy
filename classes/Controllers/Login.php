<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use Bkwld\Decoy\Models\Admin;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Former;
use URL;
use View;

/**
 * Handle logging in of users.  This is based on the AuthController.php and
 * PasswordController that Laravel's `php artisan make:auth` generates.
 */
class Login extends Base {
  use AuthenticatesUsers, ThrottlesLogins, ResetsPasswords {
    AuthenticatesUsers::redirectPath insteadof ResetsPasswords;
  }

  /**
   * Path to redirect to after login
   *
   * @var string
   */
  protected $redirectTo = '/admin/admins';

  /**
   * Create a new authentication controller instance.
   *
   * @return void
   */
  public function __construct() {
    parent::__construct();

    // Redirects to $redirectTo when authenticated
    $this->middleware('guest', ['except' => 'getLogout']);
  }

  /**
   * Get the guard to be used during authentication.
   *
   * @return string|null
   */
  protected function getGuard() {
    return Config::get('decoy.core.guard');
  }

  /**
   * Show the application login form.
   *
   * @return \Illuminate\Http\Response
   */
  public function showLoginForm()   {

    // Pass validation rules
		Former::withRules(array(
			'email'    => 'required|email',
			'password' => 'required',
		));

		// Show the login homepage
		View::inject('title', 'Login');
		return View::make('decoy::layouts.blank', [
			'content' => View::make('decoy::account.login')->render(),
		]);
  }

  /**
   * Display the form to request a password reset link.
   *
   * @return \Illuminate\Http\Response
   */
  public function showLinkRequestForm() {

    // Pass validation rules
		Former::withRules(array(
			'email' => 'required|email',
		));

		// Set the breadcrumbs
		$this->breadcrumbs(array(
			route('decoy::account@login') => 'Login',
			URL::current() => 'Forgot Password',
		));

		// Show the page
		$this->title = 'Forgot Password';
		$this->description = 'You know the drill.';
		return $this->populateView('decoy::account.forgot');
  }

  /**
   * Display the password reset view for the given token.
   *
   * If no token is present, display the link request form.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  string|null  $token
   * @return \Illuminate\Http\Response
   */
  public function showResetForm(Request $request, $token = null) {

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
			route('decoy::account@login') => 'Login',
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
}
