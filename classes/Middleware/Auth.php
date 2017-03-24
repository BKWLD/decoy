<?php

namespace Bkwld\Decoy\Middleware;

use App;
use Route;
use Closure;
use Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Endorce admin login and also test their permission to access the current
 * request
 */
class Auth
{
    /**
    * Run the request filter.
    *
    * @param  Illuminate\Http\Request  $request
    * @param  Closure  $next
    * @return mixed
    *
    * @throws AccessDeniedHttpException
    */
    public function handle($request, Closure $next)
    {
        // Require a logged in user
        if (!$admin = app('decoy.user')) {
            return App::make('decoy.acl_fail');
        }

        // Determine the action and controller differently depending on how the
        // request is routed.
        if (Route::is('decoy::wildcard')) {
            list($action, $controller) = $this->dectectFromWildcardRouter();
        } else {
            list($action, $controller) = $this->dectectFromExplicitRoute();
        }

        // If they don't hvae permission, throw an error
        if ($admin->cant($action, $controller)) {
            throw new AccessDeniedHttpException;
        }

        // Continue, the admin has permission.
        return $next($request);
    }

    /**
     * Get the action and controller from an explicilty defined route
     *
     * @return array action,controller
     *
     * @throws Exception
     */
    protected function dectectFromExplicitRoute()
    {
        // Get the action
        $action = Route::current()->getActionName();
        if ($action == 'Closure') {
            throw new \Exception('decoy.auth requires routing to controllers.');
        }

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
    protected function dectectFromWildcardRouter()
    {
        $wildcard = App::make('decoy.wildcard');

        // Attach / detach are ACL-ed by the parent controller.  It's the one being
        // touched,
        $action = $wildcard->detectAction();
        if (in_array($action, ['attach', 'remove'])) {
            $controller = request('parent_controller');
            $action = 'update';

        // Otherwise, use the controller from the route
        } else {
            $controller = $wildcard->detectControllerName();
        }

        // Further mapping of the action
        $action = $this->mapActionToPermission($action);

        // Return the detected action and controller
        return [$action, $controller];
    }

    /**
     * Map the actions from the wildcard router into the smaller set supported by
     * the Decoy permissions system
     */
    protected function mapActionToPermission($action)
    {
        switch ($action) {
            case 'new':
            case 'store':
                return 'create';

            case 'edit':
            case 'autocomplete':
            case 'index':
            case 'indexChild':
                return 'read';

            default:
                return $action;
        }
    }
}
