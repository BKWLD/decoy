<?php namespace Bkwld\Decoy\Middleware;

// Deps
use Closure;
use DecoyURL;
use Input;
use Redirect;
use Session;

/**
 * Handle the redirection after save that depends on what submit button the
 * user interacte with
 */
class SaveRedirect {

	/**
	* Run the request filter.
	*
	* @param  Illuminate\Http\Request  $request
	* @param  Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next) {

		// Handle a redirect request.  But only if there were no validation errors
		if (Session::has('save_redirect') && !Session::has('errors')) {
			Session::keep(['success', 'errors']);
			return Redirect::to(Session::get('save_redirect'));
		}

		// Go back to the listing
		if (Input::get('_save') == 'back') {
			Session::flash('save_redirect', app('decoy.breadcrumbs')->smartBack());
		}

		// Go to new form by stripping the last segment from the URL
		if (Input::get('_save') == 'new') {
			Session::flash('save_redirect', DecoyURL::relative('create'));
		}

		// Chain
		return $next($request);
	}

}
