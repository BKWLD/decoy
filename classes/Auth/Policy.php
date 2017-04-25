<?php

namespace Bkwld\Decoy\Auth;

use Config;
use Request;
use DecoyURL;
use Bkwld\Decoy\Models\Admin;

/**
 * Check if a user has permission to do something.
 */
class Policy
{
    /**
     * Check an Admin model against an action performed on a controller against
     * permissions from the config.
     *
     * @param  Admin   $admin
     * @param  string  $action     The verb we're checking.  Examples:
     *                             - create
     *                             - read
     *                             - update
     *                             - destroy
     *                             - manage
     *                             - publish
     * @param  string  $controller
     *                             - controller instance
     *                             - controller name (Admin\ArticlesController)
     *                             - URL (/admin/articles)
     *                             - slug (articles)
     * @return boolean
     */
    public function check(Admin $admin, $action, $controller)
    {
        // Convert controller instance to its string name
        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        // Get the slug version of the controller.  Test if a URL was passed first
        // and, if not, treat it like a full controller name.  URLs are used in the
        // nav. Also, an already slugified controller name will work fine too.
        $pattern = '#/'.Config::get('decoy.core.dir').'/([^/]+)#';
        if (preg_match($pattern, $controller, $matches)) {
            $controller = $matches[1];
        } else {
            $controller = DecoyURL::slugController($controller);
        }

        // Allow all admins to upload to redactor
        if ($controller == 'redactor') {
            return true;
        }

        // Always allow an admin to edit themselves for changing password.  Other
        // features will be disabled from the view file.
        if ($controller == 'admins'
            && ($action == 'read'
            || ($action == 'update' && Request::segment(3) == $admin->id))) {
            return true;
        }

        // Don't allow creation on Decoy controlers that don't allow it
        if ($action == 'create' && in_array($controller, [
            'commands', 'changes', 'elements', 'workers',
            ])) {
            return false;
        }

        // Always let developers access workers and commands
        if (in_array($controller, ['workers', 'commands']) && $admin->isDeveloper()) {
            return true;
        }

        // If the admin has permissions, test if they have access to the action using
        // the array of permitted actions.
        if ($permissions = $admin->getPermissionsAttribute()) {

            // Check that the controller was defined in the permissions
            if (!isset($permissions->$controller)
                || !is_array($permissions->$controller)) {
                return false;
            }

            // When interacting with elements, allow as long as there is at least one
            // page they have access to.  Rely on the elements controller to enforce
            // additional restrictions.
            if ($controller == 'elements' && in_array($action, ['read', 'create'])) {
                return count($permissions->elements) > 0;
            }

            // Default behavior checks that the action was checked in the permissions
            // UI for the controller.
            return in_array($action, $permissions->$controller);
        }

        // If there are "can" rules, then apply them as a whitelist.  Only those
        // actions are allowed.
        $can = Config::get('decoy.site.permissions.'.$admin->role.'.can');
        if (is_callable($can)) {
            $can = call_user_func($can, $action, $controller);
        }
        if (is_array($can) &&
            !in_array($action.'.'.$controller, $can) &&
            !in_array('manage.'.$controller, $can)) {
            return false;
        }

        // If the action is listed as "can't" then immediately deny.  Also check for
        // "manage" which means they can't do ANYTHING
        $cant = Config::get('decoy.site.permissions.'.$admin->role.'.cant');
        if (is_callable($cant)) {
            $cant = call_user_func($cant, $action, $controller);
        }
        if (is_array($cant) && (
            in_array($action.'.'.$controller, $cant) ||
            in_array('manage.'.$controller, $cant))) {
            return false;
        }

        // I guess we're good to go
        return true;
    }
}
