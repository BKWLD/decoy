<?php

// If Decoy hasn't been officially started yet, do that.  It's neeed, 
// at the very least, for the Decoy_Auth class alias
Bundle::start('decoy');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

/*
 * 
 * Loop through the routes that were setup in the config and create routes.  Decoy
 * uses routes that look like:
 * 
 *   /admin/clients
 *   /admin/clients/new
 *   /admin/clients/20
 *   /admin/clients/20/delete
 *   /admin/clients/1-23-21/delete
 *   /admin/clients/20/projects
 *   /admin/clients/20/projects/new
 *   /admin/projects/4
 *   /admin/submissions/denied -- A moderation queue of denied submissions
 * 
 * This function expects an array from the config like:
 * array like:
 * array(
 *   'news', 
 *   'events' => array(
 *   	  'photos'
 *   	) 
 * )
 * 
 */
addRoutes(Config::get('decoy::decoy.routes'));
function addRoutes($routes, $parent = null) {
	
	// Get the route for decoy, typically 'admin'
	$handles = Bundle::option('decoy', 'handles');
	
	// Loop through all the routes
	foreach($routes as $key => $val) {
		
		// Process children
		if (is_array($val)) {
			addRoutes($val, $key);
			$controller = $key;
		
		// This is the end of the line
		} else $controller = $val;
		
		// If a core Decoy controller, remove the bundle from the controller name
		if (Str::is('decoy::*', $controller)) {
			$controller_path = $controller;
			$controller = str_replace('decoy::', '', $controller); // This is used in the paths
		
		// If not a core Decoy controller, append the handle to the "uses" and "as"
		} else {
			$controller_path = "$handles.$controller";
		}
		
		// New / Create
		Router::register(array('GET', 'POST'), 
			"(:bundle)/$parent/(:num)/$controller/new", 
			array('uses' => "$controller_path@new", 'as' => "$controller_path@new_child"));
		Router::register(array('GET', 'POST'), 
			"(:bundle)/$controller/new", 
			array('uses' => "$controller_path@new", 'as' => "$controller_path@new"));
		
		// Edit / Update
		Router::register(array('PUT', 'POST', 'GET'), 
			"(:bundle)/$parent/(?:[0-9]+)/$controller/(:num)", 
			array('uses' => "$controller_path@edit", 'as' => "$controller_path@edit_child"));
		Router::register(array('PUT', 'POST', 'GET'), 
			"(:bundle)/$controller/(:num)", 
			array('uses' => "$controller_path@edit", 'as' => "$controller_path@edit"));
		
		// Attach, as in add a many to many relationship
		Router::register(array('POST'), 
			"(:bundle)/$controller/attach/(:num)", 
			array('uses' => "$controller_path@attach", 'as' => "$controller_path@attach"));
		
		// Delete
		Router::register(array('DELETE'), 
			"(:bundle)/$controller/(:any)", 
			array('uses' => "$controller_path@delete"));
		Router::register(array('POST', 'GET'), 
			"(:bundle)/$controller/(:any)/delete", 
			array('uses' => "$controller_path@delete", 'as' => "$controller_path@delete"));
		
		// Remove, as in removing a many to many relationship.  The id in this case is the
		// pivot id
		Router::register(array('DELETE', 'POST', 'GET'), 
			"(:bundle)/$controller/remove/(:any)", 
			array('uses' => "$controller_path@remove", 'as' => "$controller_path@remove"));
		
		// Autocomplete, run a search query for an autocomplete
		Router::register(array('GET'), 
			"(:bundle)/$controller/autocomplete", 
			array('uses' => "$controller_path@autocomplete", 'as' => "$controller_path@autocomplete"));
		
		// List, used for one-to-many relationships
		Router::register(array('GET'), 
			"(:bundle)/$parent/(:num)/$controller/(:any?)", 
			array('uses' => "$controller_path@index_child", 'as' => "$controller_path@child"));
		
		// List, used in standard listings and for many-to-manys.  The second variable in the
		// route may be used to pass variables to views (like in moderation)
		Router::register(array('GET'), 
			"(:bundle)/$controller/(:any?)", 
			array('uses' => "$controller_path@index", 'as' => $controller_path));

	}
}

// Add routing for admin management
addRoutes(array('decoy::admins'));
Route::controller('decoy::account');
Route::get('(:bundle)/admins/disable/(:num)', 'decoy::admins@disable');
Route::get('(:bundle)/admins/enable/(:num)', 'decoy::admins@enable');

// Generic site content
Router::register(array('GET', 'POST'), 
	"(:bundle)/content", 
	array('uses' => 'decoy::content@index', 'as' => 'decoy::content'));

// Add routing for the login screen
Route::any('(:bundle)', array(
	'uses' => Decoy_Auth::login_action(),
));

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

/*
|--------------------------------------------------------------------------
| View composers
|--------------------------------------------------------------------------
*/

require_once('composers/layouts._nav.php');
require_once('composers/layouts._breadcrumbs.php');
require_once('composers/shared.list._standard.php');

/*
|--------------------------------------------------------------------------
| Route Filters
|--------------------------------------------------------------------------
*/

// I couldn't register multiple functions on the same pattern, so this
// filter will call out to subsequent functions.  These are filters that
// affect all bundles
Route::filter('pattern: '.Bundle::option('decoy', 'handles').'/*', array('name' => 'decoy', function() {
	if ($result = filter_sentry_acl()) return $result;
	filter_clean_input();
	track_browse_history();
}));

// Turn on access control for all routes that match the handle (except)
// ones that relate to sign in)
function filter_sentry_acl() {
	
	// Whitelist the login screen.  A query string would break this,
	// but there shouldn't be any.  Returning nothing means don't take action
	if (URI::full() == action('decoy::account@login') ||
		URI::full() == action('decoy::account@forgot') ||
		strpos(URI::full(), action('decoy::account@reset')) !== false) return false;

	// Everything else in admin requires a logged in user.  So redirect
	// to login and pass along the current url so we can take the user there.
	if (!Decoy_Auth::check()) {
		return Redirect::to(Decoy_Auth::denied_url())
			->with('login_error', 'You must login first.')
			->with('login_redirect', URL::current());
	}
}

// Filter some stuff uniformly out from Input so we don't need to defined
// $accessible on the model to filter it out
function filter_clean_input() {
	
	// Input to alawys remove
	$blacklist = array('_wysihtml5_mode');
	
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