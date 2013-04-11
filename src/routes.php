<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Add routing for admin management
addRoutes(array('decoy::admins'));
Route::get('(:bundle)/admins/disable/(:num)', 'decoy::admins@disable');
Route::get('(:bundle)/admins/enable/(:num)', 'decoy::admins@enable');

// Generic site content
Router::register(array('GET', 'POST'), 
	"(:bundle)/content", 
	array('uses' => 'decoy::content@index', 'as' => 'decoy::content'));


// Take the user back to the page they were on before they were on the
// referring page.  A route filter, defined below, keeps the browse_history
// up to date.  The offset variable is used by routes like delete that don't
// want to redirect back to an edit page which no longer exists
Route::get('(:bundle)/back/(:num?)', array('as' => 'decoy::back', function($offset = 1) {
	
	// Get the history.  It should contain the following
	// [0] = The previous page, the referrer
	// [1] = Where the user was before going to the previous page
	$history = Session::get('decoy::browse_history', array());
	if (count($history) - 1 >= $offset) return Redirect::to($history[$offset]);
	else return Redirect::back();
}));

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
	track_browse_history();
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
		Session::flash('save_redirect', Breadcrumbs::smart_back());
	}
	
	// Go to new form by stripping the last segment from the URL
	if (Input::get('_save') == 'new') {
		preg_match('#^(.+)/(new|\d+)$#i', URL::current(), $matches);
		Session::flash('save_redirect', $matches[1].'/new');
	}
	
	// Done
	return false;
}

// Filter some stuff uniformly out from Input so we don't need to defined
// $accessible on the model to filter it out
function filter_clean_input() {
	
	// Input to alawys remove
	$blacklist = array('_wysihtml5_mode', '_save');
	
	// The FILES array will be untouched by this, even the replace 
	$input = Input::get();
	foreach($blacklist as $field) {
		if (isset($input[$field])) unset($input[$field]);
	}
	Input::replace($input);
		
}

// Track the user's history, for the purpose of informing the back button
function track_browse_history() {
	$history = Session::get('decoy::browse_history', array());
	
	// Only update the history on new requests and not if the current url is
	// the back route and not if the request is AJAX
	if ((count($history) && $history[0] == URI::full()) 
		|| Request::route()->is('decoy::back') 
		|| Request::ajax()) return;
	array_unshift($history, URI::full());
	
	// Only rember the last 5 requests
	$history = array_slice($history, 0, 5);
	
	// Replace the history
	Session::put('decoy::browse_history', $history);
}