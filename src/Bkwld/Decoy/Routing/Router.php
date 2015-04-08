<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use App;
use Bkwld\Decoy\Models\Encoding;
use Input;
use Route;

/**
 * This class acts as a bootstrap for setting up
 * Decoy routes
 */
class Router {
	
	/**
	 * Action for current wildcard request
	 *
	 * @var string 
	 */
	private $action;

	/**
	 * Constructor
	 * @param string $dir The path "directory" of the admin.  I.e. "admin"
	 * @param Bkwld\Decoy\Routing\Filters
	 */
	public function __construct($dir, $filters) {
		$this->dir = $dir;
		$this->filters = $filters;
	}
	
	/**
	 * Register all routes
	 *
	 * @param Illuminate\Http\Request $request 
	 */
	public function registerAll($request = null) {
		
		// Register routes
		$this->registerAccounts();
		$this->registerAdmins();
		$this->registerCommands();
		$this->registerFragments();
		$this->registerWorkers();
		$this->registerEncode();
		$this->registerElements();

		// Register wildcard last
		$this->registerWildcard();
		
		// Setup filters if there was a request.  There might not be
		// if Decoy was invoked via CLI
		if ($request) return $this->filters->onBefore($request);
	}
	
	/**
	 * Account routes
	 *
	 * @return void 
	 */
	public function registerAccounts() {
		Route::get($this->dir, ['as' => 'decoy', 'uses' => App::make('decoy.auth']->loginAction()));
		Route::post($this->dir, 'Bkwld\Decoy\Controllers\Account@post');
		Route::get($this->dir.'/account', ['as' => 'decoy::account', 'uses' => 'Bkwld\Decoy\Controllers\Account@index']);
		Route::get($this->dir.'/logout', ['as' => 'decoy::account@logout', 'uses' => 'Bkwld\Decoy\Controllers\Account@logout']);
		Route::get($this->dir.'/forgot', ['as' => 'decoy::account@forgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@forgot']);
		Route::post($this->dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@postForgot');
		Route::get($this->dir.'/reset/{code}', ['as' => 'decoy::account@reset', 'uses' => 'Bkwld\Decoy\Controllers\Account@reset']);
		Route::post($this->dir.'/reset/{code}', 'Bkwld\Decoy\Controllers\Account@postReset');
	}
	
	/**
	 * Setup wilcard routing
	 *
	 * @return void 
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
			if (is_a($response, 'Symfony\Component\HttpFoundation\Response')
				|| is_a($response, 'Illuminate\View\View')) // Possible when layout is involved
				return $response;
			else App::abort(404);
			
		})->where('path', '.*');

	}
	
	/**
	 * Non-wildcard admin routes
	 *
	 * @return void 
	 */
	public function registerAdmins() {
		Route::get($this->dir.'/admins/{id}/disable', ['as' => 'decoy::admins@disable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@disable']);
		Route::get($this->dir.'/admins/{id}/enable', ['as' => 'decoy::admins@enable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@enable']);
	}
	
	/**
	 * Commands / Tasks
	 *
	 * @return void 
	 */
	public function registerCommands() {
		Route::get($this->dir.'/commands', ['uses' => 'Bkwld\Decoy\Controllers\Commands@index', 'as' => 'decoy::commands']);
		Route::post($this->dir.'/commands/{command}', ['uses' => 'Bkwld\Decoy\Controllers\Commands@execute', 'as' => 'decoy::commands@execute']);
	}
	
	/**
	 * Fragments
	 *
	 * @return void 
	 */
	public function registerFragments() {
		Route::get($this->dir.'/fragments/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Fragments@index', 'as' => 'decoy::fragments']);
		Route::post($this->dir.'/fragments/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Fragments@store', 'as' => 'decoy::fragments@store']);
	}
	
	/**
	 * Workers
	 *
	 * @return void 
	 */
	public function registerWorkers() {
		Route::get($this->dir.'/workers', ['uses' => 'Bkwld\Decoy\Controllers\Workers@index', 'as' => 'decoy::workers']);
		Route::get($this->dir.'/workers/tail/{worker}', ['uses' => 'Bkwld\Decoy\Controllers\Workers@tail', 'as' => 'decoy::workers@tail']);
	}

	/**
	 * Encoding
	 *
	 * @return void 
	 */
	public function registerEncode() {

		// Get the status of an encode
		Route::get($this->dir.'/encode/{id}/progress', function($id) {
			return Encoding::findOrFail($id)->forProgress();
		});

		// Make a simply handler for notify callbacks.  The encoding model will pass the the handling
		// onto whichever provider is registered.
		Route::post($this->dir.'/encode/notify', ['as' => 'decoy::encode@notify', function() {
			return Encoding::notify(Input::get());
		}]);
	}

	/**
	 * Elements system
	 *
	 * @return void 
	 */
	public function registerElements() {
		Route::get($this->dir.'/elements/field/{key}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@field', 'as' => 'decoy::elements@field']);
		Route::post($this->dir.'/elements/field/{key}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@fieldUpdate', 'as' => 'decoy::elements@field-update']);
		Route::get($this->dir.'/elements/{locale?}/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@index', 'as' => 'decoy::elements']);
		Route::post($this->dir.'/elements/{locale?}/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@store', 'as' => 'decoy::elements@store']);
	}
	
	/**
	 * Set and get the action for this request
	 * 
	 * @return string 'Bkwld\Decoy\Controllers\Account@forgot'
	 */
	public function action($name = null) {
		if ($name) $this->action = $name;
		if ($this->action) return $this->action; // Wildcard
		else return Route::currentRouteAction();
	}
}