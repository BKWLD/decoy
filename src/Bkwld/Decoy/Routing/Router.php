<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use App;
use Config;
use Event;
use Route;

/**
 * This class acts as a bootstrap for setting up
 * Decoy routes
 */
class Router {
	
	/**
	 * Constructor
	 * @param string $dir The path "directory" of the admin.  I.e. "admin"
	 * @param Illuminate\Http\Request
	 */
	public function __construct($dir) {
		$this->dir = $dir;
	}
	
	/**
	 * Register all routes
	 */
	public function registerAll() {
		
		// Register routes
		$this->registerAccounts();
		$this->registerAdmins();
		$this->registerCommands();
		$this->registerFragments();
		$this->registerWorkers();
		$this->registerWildcard();
		
		// Setup filters
		$filters = new Filters($this->dir);
		$filters->registerAll();
	}
	
	/**
	 * Account routes
	 */
	public function registerAccounts() {
		Route::get($this->dir, array('as' => 'decoy', 'uses' => App::make('decoy.auth')->loginAction()));
		Route::post($this->dir, 'Bkwld\Decoy\Controllers\Account@post');
		Route::get($this->dir.'/account', array('as' => 'decoy\account', 'uses' => 'Bkwld\Decoy\Controllers\Account@index'));
		Route::get($this->dir.'/logout', array('as' => 'decoy\account@logout', 'uses' => 'Bkwld\Decoy\Controllers\Account@logout'));
		Route::get($this->dir.'/forgot', array('as' => 'decoy\account@forgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@forgot'));
		Route::post($this->dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@postForgot');
		Route::get($this->dir.'/reset/{code}', array('as' => 'decoy\account@reset', 'uses' => 'Bkwld\Decoy\Controllers\Account@reset'));
		Route::post($this->dir.'/reset/{code}', 'Bkwld\Decoy\Controllers\Account@postReset');
	}
	
	/**
	 * Setup wilcard routing
	 */
	public function registerWildcard() {
		
		// Localize vars for closure
		$dir = $this->dir;
		$self = $this;
		
		// Setup a wildcarded catch all route
		Route::any($this->dir.'/{path}', function($path) use ($dir, $self) {

			// Remember the detected route
			App::make('events')->listen('wildcard.detection', function($controller, $action) use ($self) {
				$self->action($controller.'@'.$action);
			});
			
			// Do the detection
			$router = App::make('decoy.wildcard');
			$response = $router->detectAndExecute();
			if (is_a($response, 'Symfony\Component\HttpFoundation\Response')) return $response;
			else App::abort(404);
			
		})->where('path', '.*');

	}
	
	/**
	 * Non-wildcard admin routes
	 */
	public function registerAdmins() {
		Route::get($this->dir.'/admins/{id}/disable', array('as' => 'decoy\admins@disable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@disable'));
		Route::get($this->dir.'/admins/{id}/enable', array('as' => 'decoy\admins@enable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@enable'));
	}
	
	/**
	 * Commands / Tasks
	 */
	public function registerCommands() {
		Route::get($this->dir.'/commands', array('uses' => 'Bkwld\Decoy\Controllers\Commands@index', 'as' => 'decoy\commands'));
		Route::post($this->dir.'/commands/{command}', array('uses' => 'Bkwld\Decoy\Controllers\Commands@execute', 'as' => 'decoy\commands@execute'));
	}
	
	/**
	 * Fragments
	 */
	public function registerFragments() {
		Route::get($this->dir.'/fragments', array('uses' => 'Bkwld\Decoy\Controllers\Fragments@index', 'as' => 'decoy\fragments'));
		Route::post($this->dir.'/fragments', array('uses' => 'Bkwld\Decoy\Controllers\Fragments@store', 'as' => 'decoy\fragments@store'));
	}
	
	/**
	 * Workers
	 */
	public function registerWorkers() {
		Route::get($this->dir.'/workers', array('uses' => 'Bkwld\Decoy\Controllers\Workers@index', 'as' => 'decoy\workers'));
		Route::get($this->dir.'/workers/tail/{worker}', array('uses' => 'Bkwld\Decoy\Controllers\Workers@tail', 'as' => 'decoy\workers@tail'));
	}
	
	/**
	 * Set and get the action for this request
	 * @return string 'Bkwld\Decoy\Controllers\Account@forgot'
	 */
	private $_action;
	public function action($name = null) {
		if ($name) $this->_action = $name;
		if ($this->_action) return $this->_action; // Wildcard
		else return Route::currentRouteAction();
	}
}