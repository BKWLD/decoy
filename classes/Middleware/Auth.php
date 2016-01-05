<?php namespace Bkwld\Decoy\Middleware;

// Deps
use App;
use Config;
use Closure;
use Request;
use Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Endorce admin login and also test their permission to access the current
 * request
 */
class Auth {

	/**
	* Run the request filter.
	*
	* @param  Illuminate\Http\Request  $request
	* @param  Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next) {

		// Require a logged in user
		if (!$admin = App::make('decoy.auth')) return App::make('decoy.acl_fail');

		// Determine the action and controller differently depending on how the
		// request is routed.
		if (Route::is('decoy::wildcard')) {
			list($action, $controller) = $this->dectectFromWildcardRouter();
		} else {
			list($action, $controller) = $this->dectectFromExplicitRoute();
		}

		// If they don't hvae permission, throw an error
		if ($admin->cannot('decoy.auth', [$action, $controller])) {
			throw new AccessDeniedHttpException;
		}

		// Continue, the admin has permission.
		return $next($request);
	}

	/**
	 * Get the actino and controller from an explicilty defined route
	 *
	 * @return array action,controller
	 */
	protected function dectectFromExplicitRoute() {

		// Get parse the `uses` from the route definition
		preg_match('#(.+)@(.+)#', Route::current()->getActionName(), $matches);
		$controller = $matches[1];
		$action = $matches[2];

		// Further mapping of the action
		$action = $this->mapActionToPermission($action);

		// Return the detected action and controller
		return [$action, $controller];
	}

	/**
	 * Get the action and controller from the wildcard router
	 *
	 * @return array action,controller
	 */
	protected function dectectFromWildcardRouter() {
		$wildcard = App::make('decoy.wildcard');

		// Attach / detach are ACL-ed by the parent controller.  It's the one being
		// touched,
		$action = $wildcard->detectAction();
		if (in_array($action, ['attach', 'remove'])) {
			$controller = Input::get('parent_controller');
			$action = 'update';

		// Otherwise, use the controller from the route
		} else $controller = $wildcard->detectControllerName();

		// Further mapping of the action
		$action = $this->mapActionToPermission($action);

		// Return the detected action and controller
		return [$action, $controller];
	}

	/**
	 * Map the actions from the wildcard router into the smaller set supported by
	 * the Decoy permissions system
	 */
	protected function mapActionToPermission($action) {
		switch($action) {
			case 'new':
			case 'store': return 'create';
			case 'edit':
			case 'autocomplete':
			case 'index':
			case 'indexChild': return 'read';
			default: return $action;
		}
	}

}
