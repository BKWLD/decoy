<?php

namespace Bkwld\Decoy\Routing;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * This class exists to help make links between pages in Decoy, which is
 * complicated because none of the routes are explicitly defined.  All of
 * the relationships and breadcrumbs are created through controller, models,
 * and reading the current URL
 */
class UrlGenerator
{
    /**
     * DI properties
     *
     * @var string
     */
    private $path;

    /**
     * Possible actions in path that would process a view that would be generating URLs
     *
     * @var array
     */
    private $actions = ['edit', 'create', 'destroy', 'duplicate'];

    /**
     * Inject dependencies
     * @param string $path A Request::path()
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Construct a route that takes into account the current url path as well
     * as the function arguments
     *
     * @param  string  $action The action we're linking to: index/edit/etc
     * @param  integer $id     Optional id that we're linking to.  Required for actions like edit.
     * @param  string  $child  The name (or full class) of a child controller
     *                         of the current path: 'slides', 'Admin\SlidesController'
     * @return string  '/admin/articles'
     */
    public function relative($action = 'index', $id = null, $child = null)
    {
        // Get the current path, adding a leading slash that should be missing
        $path = '/'.$this->path;

        // Get the URL up to and including the last controller.  If we're not linking
        // to a child, also remove the id, which may be replaced by a passed id
        $actions = implode('|', $this->actions);
        if ($child) {
            $pattern = '#(/('.$actions.'))?/?$#';
        } else {
            $pattern = '#(/\d+)?(/('.$actions.'))?/?$#';
        }
        $path = preg_replace($pattern, '', $path);

        // If there is an id and we're not linking to a child, add that id
        if (!$child && $id) {
            $path .= '/'.$id;
        }

        // If there is a child controller, add that now
        if ($child) {

            // If the child has a backslash, it's a namespaced class name, so convert to just name
            if (strpos($child, '\\') !== false) {
                $child = $this->slugController($child);
            }

            // If currently on an edit view (where we always respect child parameters literally),
            // or if the link is to an index view (for many to many to self) or if the child
            // is different than the current path, appened the child controller slug.
            if (preg_match('#edit$#', $this->path)
                || $action == 'index'
                || !preg_match('#'.$child.'(/\d+)?$#', $path)
                ) {
                $path .= '/'.$child;
            }

            // If the action was not index and there was an id, add it
            if ($action != 'index' && $id) {
                $path .= '/'.$id;
            }
        }

        // Now, add actions (except for index, which is implied by the lack of an action)
        if ($action && $action != 'index') {
            $path .= '/'.$action;
        }

        // Done, return it
        return $path;
    }

    /**
     * Make a URL given a fully namespaced controller.  This only generates routes
     * as if the controller is in the root level; as if it has no parents.
     *
     * @param  string  $controller ex: Bkwld\Decoy\Controllers\Admins@create
     * @param  integer $id
     * @return string  ex: http://admin/admins/create
     */
    public function action($controller = null, $id = null)
    {
        // Assume that the current, first path segment is the directory decoy is
        // running in
        preg_match('#[a-z-]+#i', $this->path, $matches);
        $decoy = $matches[0];

        // Strip the action from the controller
        $action = '';
        if (preg_match('#@\w+$#', $controller, $matches)) {
            $action = substr($matches[0], 1);
            $controller = substr($controller, 0, -strlen($matches[0]));
        }

        // Convert controller for URL
        $controller = $this->slugController($controller);

        // Begin the url
        $path = '/'.$decoy.'/'.$controller;

        // If there is an id, add it now
        if ($id) {
            $path .= '/'.$id;
        }

        // Now, add actions (except for index, which is implied by the lack of an action)
        if ($action && $action != 'index') {
            $path .= '/'.$action;
        }

        // Done, return it
        return $path;
    }

    /**
     * Convert a controller to how it is referenced in a url
     *
     * @param  string $controller ex: Admin\ArticlesAreCoolController
     * @return string ex: articles-are-cool
     */
    public function slugController($controller)
    {
        // Get the controller name
        $controller = preg_replace('#^('.preg_quote('Bkwld\Decoy\Controllers\\')
            .'|'.preg_quote('App\Http\Controllers\Admin\\').')#', '', $controller);

        // Convert study caps to dashes
        $controller = Str::snake($controller, '-');

        // Done
        return $controller;
    }
}
