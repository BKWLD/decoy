<?php

namespace Bkwld\Decoy\Routing;

use App;
use Event;
use Illuminate\Support\Str;

/**
 * The wildcard router is what allows us to wildcard the admin routes so that the
 * developer doesn't need to hard code those.  One thing to know is that decoy
 * uses routes that are very literal with how the content is organized.  This
 * informs the breadcrumbs.  So, if you are looking at the edit view of a photo
 * that belongsTo() an article, the path would be admin/article/2/photo/4/edit
 */
class Wildcard
{
    // DI Properties
    private $dir;
    private $verb;
    private $path;

    /**
     * These are action suffixes on paths
     *
     * @var array
     */
    private $actions = ['create', 'edit', 'destroy', 'attach', 'remove',
        'autocomplete', 'duplicate', 'csv'];

    /**
     * Constructor
     * @param string $dir The path "directory" of the admin.  I.e. "admin"
     * @param string $verb GET,POST,etc
     * @param string $path A URL path like 'admin/articles/new'
     */
    public function __construct($dir, $verb, $path)
    {
        $this->dir = $dir;
        $this->verb = $verb;
        $this->path = $path;
    }

    /**
     * Detect the controller for a given route and then execute the action
     * that is specified in the route.
     */
    public function detectAndExecute()
    {
        // Get the controller
        if (!($controller = $this->detectController())) {
            return false;
        }

        // Get the action
        $action = $this->detectAction();
        if (!$action || !method_exists($controller, $action)) {
            return false;
        }

        // Get the id
        $id = $this->detectId();

        // Tell other classes what was found
        $event = Event::fire('wildcard.detection', [
            $controller, $action, $id
        ]);

        // Instantiate controller
        $controller = new $controller();
        if ($parent = $this->detectParent()) {
            list($parent_slug, $parent_id) = $parent;
            $parent_model = 'App\\'.Str::singular(Str::studly($parent_slug));
            $controller->parent($parent_model::findOrFail($parent_id));
        }

        // Execute the request
        $params = $id ? [$id] : [];
        return $controller->callAction($action, $params);
    }

    /**
     * Get the full namespaced controller
     *
     * @return string i.e. App\Http\Controllers\Admin\People or
     *                Bkwld\Decoy\Controllers\Admins
     */
    public function detectController($class_name = null)
    {

        // Setup the two schemes
        if (!$class_name) {
            $class_name = $this->detectControllerClass();
        }
        $app = 'App\\Http\\Controllers\\'
            . ucfirst(Str::studly($this->dir))
            . '\\'.$class_name;
        $decoy = 'Bkwld\Decoy\Controllers\\'.$class_name;

        // Find the right one
        if (class_exists($app)) {
            return $app;
        } elseif (class_exists($decoy)) {
            return $decoy;
        }

        return false;
    }

    /**
     * Detect the controller for a path.  Which is the last non-action
     * string in the path
     *
     * @return string The controller class, i.e. Articles
     */
    public function detectControllerClass($name = null)
    {
        // The path must begin with the config dir
        if (!preg_match('#^'.$this->dir.'#i', $this->path, $matches)) {
            return false;
        }

        // Find the controller from the end of the path
        if (!$name) {
            $name = $this->detectControllerName();
        }

        // Form the namespaced controller
        return Str::studly($name);
    }

    /**
     * Get just the controller's short name from the path
     * @return mixed false if not found, otherwise a string like "news" or "slides"
     */
    public function detectControllerName()
    {
        $pattern = '#/'.$this->controllerNameRegex().'#i';
        if (!preg_match($pattern, $this->path, $matches)) {
            return false;
        }

        return $matches[1];
    }

    /**
     * Make the regex pattern to find the controller
     * @return regexp
     */
    private function controllerNameRegex()
    {
        return '([a-z-]+)(/\d+)?(/('.implode('|', $this->actions).'))?/?$';
    }

    /**
     * Detect the action for a path
     * @return string 'create', 'update', 'edit', ....
     */
    public function detectAction()
    {

        // If the path ends in one of the special actions, use that as the action
        // as long as the verb is a GET
        if (preg_match('#[a-z-]+$#i', $this->path, $matches)) {
            $action = $matches[0];

            // If posting to the create/edit route, treat as a 'post' route rather than
            // a 'create/edit' one.  This is a shorthand so the create forms can
            // post to themselves
            if ($action == 'create' && $this->verb == 'POST') {
                return 'store';
            } elseif ($action == 'edit' && $this->verb == 'POST') {
                return 'update';
            }

            // ... otherwise, use the route explicitly
            elseif (in_array($action, $this->actions)) {
                return $action;
            }
        }

        // If the path ends in a number, the verb defines what it is
        if (preg_match('#\d+$#', $this->path)) {
            switch ($this->verb) {
                case 'PUT':
                case 'POST':
                    return 'update';

                case 'DELETE':
                    return 'destroy';

                default:
                    return false;
            }
        }

        // Else, it must end with the controller name
        switch ($this->verb) {
            case 'POST':
                return 'store';

            case 'GET':
                return 'index';
        }

        // Must have been an erorr if we got here
        return false;
    }

    /**
     * Detect the id for the path
     * @return integer An id number for a DB record
     */
    public function detectId()
    {
        // If there is an id, it will be the last number
        if (preg_match('#\d+$#', $this->path, $matches)) {
            return $matches[0];
        }

        // .. or the route will be an action preceeded by an id
        $pattern = '#(\d+)/('.implode('|', $this->actions).')$#i';
        if (preg_match($pattern, $this->path, $matches)) {
            return $matches[1];
        }

        // There's no id
        return false;
    }

    /**
     * Detect the parent id of the path
     *
     * @return mixed False or an array containing:
     *               - The slug of the parent controller
     *               - The id of the parent record
     */
    public function detectParent()
    {
        // Look for a string, then a number (the parent id), followed by a non-action
        // string, then possiby a number and/or an action string and then the end
        $pattern = '#([a-z-]+)/(\d+)/(?!'.implode('|', $this->actions).')[a-z-]+(?:/\d+)?(/('.implode('|', $this->actions).'))?$#i';
        if (preg_match($pattern, $this->path, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return false;
    }

    /**
     * Return an array of all classes represented in the URL
     */
    public function getAllClasses()
    {
        // Get all slugs that lead with a slash
        $pattern = '#(?:/([a-z-]+))#i';

        // If no matches, return an empty array.  Matches will be at first index;
        preg_match_all($pattern, $this->path, $matches);
        if (count($matches) <= 1) {
            return [];
        }
        $matches = $matches[1];

        // Remove actions from the matches list (like "edit")
        if (in_array($matches[count($matches) - 1], $this->actions)) {
            array_pop($matches);
        }

        // Convert all the matches to their classes
        return array_map(function ($name) {
            return $this->detectController($this->detectControllerClass($name));
        }, $matches);
    }

    /**
     * Return the path that the wildcard instance is operating on
     * @return string ex: admin/news/2/edit
     */
    public function path()
    {
        return $this->path();
    }
}
