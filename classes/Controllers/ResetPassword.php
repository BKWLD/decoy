<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use Auth;
use Bkwld\Decoy\Models\Admin;
use Decoy;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Former;

/**
 * Handle logging in of users.  This is based on the AuthController.php and
 * PasswordController that Laravel's `php artisan make:auth` generates.
 */
class ResetPassword extends Base {
	use ResetsPasswords;

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
		app('decoy.breadcrumbs')->set([
			route('decoy::account@login') => 'Login',
			url()->current() => 'Forgot Password',
		]);

		// Show the page
		$this->title = 'Forgot Password';
		$this->description = 'You know the drill.';
		return $this->populateView('decoy::account.forgot');
	}

	/**
	 * Get the e-mail subject line to be used for the reset link email.
	 *
	 * @return string
	 */
	protected function getEmailSubject() {
		return 'Recover access to '.Decoy::site();
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
			->join('password_resets', 'password_resets.email', '=', 'admins.email')
			->firstOrFail();

		// Set the breadcrumbs
		app('decoy.breadcrumbs')->set([
			route('decoy::account@login') => 'Login',
			route('decoy::account@forgot') => 'Forgot Password',
			url()->current() => 'Reset Password',
		]);

		// Show the page
		$this->title = 'Reset Password';
		$this->description = 'Almost done.';
		return $this->populateView('decoy::account.reset', [
			'user' => $user,
			'token' => $token,
		]);
	}

	/**
	 * Get the post register / login redirect path. This is set to the login route
	 * so that the guest middleware can pick it up and redirect to the proper
	 * start page.
	 *
	 * @return string
	 */
	public function redirectPath() {
		return route('decoy::account@login');
	}

	/**
	 * Subclass the resetPassword method so that it doesn't `bcrypt()` the
	 * password.  We're trusting to the model's onSaving callback for this.
	 *
	 * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
	 * @param  string  $password
	 * @return void
	 */
	protected function resetPassword($user, $password) {
		$user->forceFill([
			'password' => $password,
			'remember_token' => Str::random(60),
		])->save();
		Auth::guard($this->getGuard())->login($user);
	}
}
