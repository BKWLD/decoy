<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use \App;
use Illuminate\Support\Str;

/**
 * The wildcard router is what allows us to wildcard the admin routes so that the
 * developer doesn't need to hard code those.  One thing to know is that decoy
 * uses routes that are very literal with how the content is organized.  This
 * informs the breadcrumbs.  So, if you are looking at the edit view of a photo
 * that belongsTo() an article, the path would be admin/article/2/photo/4/edit
 */
class Wildcard {
	
	// DI Properties
	private $dir;
	private $verb;
	private $path;
	
	// These are action suffixes on paths
	private $actions = array('create', 'edit', 'destroy', 'attach', 'remove', 'autocomplete');
	
	/**
	 * Constructor
	 * @param $string dir The path "directory" of the admin.  I.e. "admin"
	 * @param $string verb GET,POST,etc
	 * @param $string path A URL path like 'admin/articles/new'
	 */
	public function __construct($dir, $verb, $path) {
		$this->dir = $dir;
		$this->verb = $verb;
		$this->path = $path;
	}
	
	/**
	 * Detect the controller for a given route and then execute the action
	 * that is specified in the route.
	 */
	public function detectAndExecute() {
		
		// Get the controller
		if (!($controller = $this->detectController())) return false;
		
		// Get the action
		$action = $this->detectAction();
		if (!$action || !method_exists($controller, $action)) return false;

		// Get the id
		$id = $this->detectId();
		
		// Invoke the controller
		$controller = new $controller();
		$params = $id ? array($id) : array();
		return $controller->callAction(App::getFacadeApplication(), App::make('router'), $action, $params);
		
	}
	
	/**
	 * Get the full namespaced controller
	 * @return string i.e. Admin\ArticlesController or Bkwld\Decoy\Controllers\Admins
	 */
	public function detectController($class_name = null) {

		// Setup the two schemes
		if (!$class_name) $class_name = $this->detectControllerClass();
		$app = Str::studly($this->dir).'\\'.$class_name.'Controller';
		$decoy = 'Bkwld\Decoy\Controllers\\'.$class_name;

		// Find the right one
		if (class_exists($app)) return $app;
		else if (class_exists($decoy)) return $decoy;
		else return false;
	}
	
	/**
	 * Detect the controller for a path.  Which is the last non-action
	 * string in the path
	 * @return string The controller class, i.e. Articles
	 */
	public function detectControllerClass($name = null) {
		
		// The path must begin with the config dir
		if (!preg_match('#^'.$this->dir.'#i', $this->path, $matches)) return false;
		
		// Find the controller from the end of the path
		if (!$name) $name = $this->detectControllerName();
		
		// Form the namespaced controller
		return Str::studly($name);
	}
	
	/**
	 * Get just the controller's short name from the path
	 * @return mixed false if not found, otherwise a string like "news" or "slides"
	 */
	public function detectControllerName() {
		$pattern = '#/'.$this->controllerNameRegex().'#i';
		if (!preg_match($pattern, $this->path, $matches)) return false;
		return $matches[1];
	}
	
	/**
	 * Make the regex pattern to find the controller
	 * @return regexp
	 */
	private function controllerNameRegex() {
		return '([a-z-]+)(/\d+)?(/('.implode('|', $this->actions).'))?/?$';
	}
	
	/**
	 * Detect the action for a path
	 * @return string 'create', 'update', 'edit', ....
	 */
	public function detectAction() {
		
		// Get whether the request acts as a child.  This modifies some of the
		// actions that would be called
		$is_child = $this->detectIfChild();
		
		// If the path ends in one of the special actions, use that as the action
		// as long as the verb is a GET
		if (preg_match('#[a-z-]+$#i', $this->path, $matches)) {
			$action = $matches[0];
			
			// If posting to the create/edit route, treat as a 'post' route rather than
			// a 'create/edit' one.  This is a shorthand so the create forms can
			// post to themselves
			if ($action == 'create' && $this->verb == 'POST') return 'store';
			else if ($action == 'edit' && $this->verb == 'POST') return 'update';
			
			// ... otherwise, use the route explicitly
			else if (in_array($action, $this->actions)) return $action;
		}
		
		// If the path ends in a number, the verb defines what it is
		if (preg_match('#\d+$#', $this->path)) {
			switch($this->verb) {
				case 'PUT':
				case 'POST': return 'update';
				case 'DELETE': return 'destroy';
				default: return false;
			}
		}
		
		// Else, it must end with the controller name
		switch($this->verb) {
			case 'POST': return 'store';
			case 'GET': return $is_child ? 'indexChild' : 'index';
		}
		
		// Must have been an erorr if we got here
		return false;
	}
	
	/**
	 * Detect the id for the path
	 * @return integer An id number for a DB record
	 */
	public function detectId() {
		
		// If there is an id, it will be the last number
		if (preg_match('#\d+$#', $this->path, $matches)) return $matches[0];
		
		// .. or the route will be an action preceeded by an id
		$pattern = '#(\d+)/('.implode('|', $this->actions).')$#i';
		if (preg_match($pattern, $this->path, $matches)) return $matches[1];
		
		// There's no id
		return false;
		
	}
	
	/**
	 * Detect the parent id of the path
	 * @return mixed An id number or false
	 */
	public function detectParentId() {
		
		// Look for a string, then a number (the parent id), followed by a non-action
		// string, then possiby a number and/or an action string and then the end
		$pattern = '#[a-z-]+/(\d+)/(?!'.implode('|', $this->actions).')[a-z-]+(/\d+)?(/('.implode('|', $this->actions).'))?$#i';
		if (preg_match($pattern, $this->path, $matches)) return $matches[1];
		return false;		
	}
	
	/**
	 * Detect if the request is for a child of another controller
	 * @return boolean
	 */
	public function detectIfChild() {
		
		// A child is a controller preceeded by an id and another controller
		// though there may be an action on the end (which should be ignored)
		$pattern = '#[a-z-]+/\d+/(?!$|'.implode('$|', $this->actions).'$)#i';
		return preg_match($pattern, $this->path) === 1;
		
	}
	
	/**
	 * Figure out the parent controller of the path
	 * @return string For admin/base/2/slides/4/edit, returns "base"
	 */
	public function getParentController() {
		
		// Find the name of the parent controller, which is string followed by
		// a number followed by the controller's regexp.  Making sure not to confuse
		// the action on a parent controller with the child action.
		$pattern = '#/([a-z-]+)/\d+/(?!('.implode('|', $this->actions).')$)'.$this->controllerNameRegex().'#i';
		if (!preg_match($pattern, $this->path, $matches)) return false;
		$name = $matches[1];
		
		// Convert it to a class
		return $this->detectController($this->detectControllerClass($name));
		
	}
	
	/**
	 * Return the path that the wildcard instance is operating on
	 * @return string ex: admin/news/2/edit
	 */
	public function path() {
		return $this->path();
	}
	
}