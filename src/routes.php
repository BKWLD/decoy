<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Generic site content
Router::register(array('GET', 'POST'), 
	"(:bundle)/content", 
	array('uses' => 'decoy::content@index', 'as' => 'decoy::content'));

// Show all the task methods.  This option isn't typically isn't made
// available in the nav.
Route::get('(:bundle)/tasks', array('uses' => 'decoy::tasks@index', 'as' => 'decoy::tasks'));
Route::post('(:bundle)/tasks/(:any)/(:any)', array('uses' => 'decoy::tasks@execute', 'as' => 'decoy::tasks@execute'));

// View worker status
Route::get('(:bundle)/workers', array('uses' => 'decoy::workers@index', 'as' => 'decoy::workers'));
Route::get('(:bundle)/workers/tail/(:any).txt', array('uses' => 'decoy::workers@tail', 'as' => 'decoy::workers@tail'));


/*
|--------------------------------------------------------------------------
| Route Filters
|--------------------------------------------------------------------------
*/

// I couldn't register multiple functions on the same pattern, so this
// filter will call out to subsequent functions.  These are filters that
// affect all bundles
Route::filter('pattern: '.Bundle::option('decoy', 'handles').'/*', array('name' => 'decoy', function() {
	
	// Allow other code to handle this pattern.  Required, again, because Laravel
	// only allows one pattern per handler
	if ($response = Event::until('decoy::before')) return $response;
	
	// Native handling of the routes
	if ($response = filter_acl()) return $response;
	if ($response = redirect_after_save()) return $response;
	filter_clean_input();
}));

// Turn on access control for all routes that match the handle (except)
// ones that relate to sign in)
function filter_acl() {
	
	// Whitelist the login screen.  A query string would break this,
	// but there shouldn't be any.  Returning nothing means don't take action
	if (URI::full() == action('decoy::account@login') ||
		URI::full() == action('decoy::account@forgot') ||
		strpos(URI::full(), action('decoy::account@reset')) !== false) return false;

	// Everything else in admin requires a logged in user.  So redirect
	// to login and pass along the current url so we can take the user there.
	if (!DecoyAuth::check()) {
		return Redirect::to(DecoyAuth::deniedUrl())
			->with('login_error', 'You must login first.')
			->with('login_redirect', URL::current());
	}
}

// If _save was set (it is the name of the save action buttons), store a proper
// redirect instruction to the session.  I don't want methods that override
// the post_new or put_edit methods to have to care about this.  So, on the next
// page load, the session value will be used.  Finally, the _save input
// will be stripped by filter_clean_input
function redirect_after_save() {
	
	// Handle a redirect request
	if (Session::has('save_redirect')) return Redirect::to(Session::get('save_redirect'));
	
	// Only act on save values of 'back' or 'new'
	if (!Input::has('_save') || Input::get('_save') == 'save') return false;
	
	// Get the route for decoy, typically 'admin'
	$handles = Bundle::option('decoy', 'handles');
	
	// Go back to the listing
	if (Input::get('_save') == 'back') {
		Session::flash('save_redirect', Breadcrumbs::smartBack());
	}
	
	// Go to new form by stripping the last segment from the URL
	if (Input::get('_save') == 'new') {
		preg_match('#^(.+)/(new|\d+)$#i', URL::current(), $matches);
		Session::flash('save_redirect', $matches[1].'/new');
	}
	
	// Done
	return false;
}
