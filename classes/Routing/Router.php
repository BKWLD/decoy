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
	 * The path "directory" of the admin.  I.e. "admin"
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Constructor
	 *
	 * @param string $dir The path "directory" of the admin.  I.e. "admin"
	 */
	public function __construct($dir, $filters) {
		$this->dir = $dir;
	}

	/**
	 * Register all routes
	 *
	 * @return void
	 */
	public function registerAll() {

		// Public routes
		Route::group([
			'prefix' => $this->dir,
			'middleware' => [
				'decoy.middlewares.edit-redirect',
			],
		], function() {
			$this->registerAccount();
		});

		// Protected, admin routes
		Route::group([
			'prefix' => $this->dir,
			'middleware' => [
				'decoy.middlewares.auth',
				'decoy.middlewares.edit-redirect',
			],
		], function() {
			$this->registerAdmins();
			$this->registerCommands();
			$this->registerWorkers();
			$this->registerEncode();
			$this->registerElements();
			$this->registerRedactor();
			$this->registerWildcard(); // Must be last
		});

		// Web service callback endpoints and don't require CSRF filtering
		Route::group([
			'prefix' => $this->dir,
		], function() {
			$this->registerCallbackEndpoints();
		});

	}

	/**
	 * Account routes
	 *
	 * @return void
	 */
	public function registerAccount() {
		Route::get('/', ['as' => 'decoy', 'uses' => App::make('decoy.auth')->loginAction()]);
		Route::post('/', ['as' => 'decoy::account@login', 'uses' => 'Bkwld\Decoy\Controllers\Account@post']);
		Route::get('account', ['as' => 'decoy::account', 'uses' => 'Bkwld\Decoy\Controllers\Account@index']);
		Route::get('logout', ['as' => 'decoy::account@logout', 'uses' => 'Bkwld\Decoy\Controllers\Account@logout']);
		Route::get('forgot', ['as' => 'decoy::account@forgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@forgot']);
		Route::post('forgot', ['as' => 'decoy::account@postForgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@postForgot']);
		Route::get('reset/{code}', ['as' => 'decoy::account@reset', 'uses' => 'Bkwld\Decoy\Controllers\Account@reset']);
		Route::post('reset/{code}', ['as' => 'decoy::account@postReset', 'uses' => 'Bkwld\Decoy\Controllers\Account@postReset']);
	}

	/**
	 * Setup wilcard routing
	 *
	 * @return void
	 */
	public function registerWildcard() {

		// Setup a wildcarded catch all route
		Route::any('{path}', ['as' => 'decoy::wildcard', function($path) {

			// Remember the detected route
			App::make('events')->listen('wildcard.detection', function($controller, $action) {
				$this->action($controller.'@'.$action);
			});

			// Do the detection
			$router = App::make('decoy.wildcard');
			$response = $router->detectAndExecute();
			if (is_a($response, 'Symfony\Component\HttpFoundation\Response')
				|| is_a($response, 'Illuminate\View\View')) // Possible when layout is involved
				return $response;
			else App::abort(404);

		}])->where('path', '.*');

	}

	/**
	 * Non-wildcard admin routes
	 *
	 * @return void
	 */
	public function registerAdmins() {
		Route::get('admins/{id}/disable', ['as' => 'decoy::admins@disable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@disable']);
		Route::get('admins/{id}/enable', ['as' => 'decoy::admins@enable', 'uses' => 'Bkwld\Decoy\Controllers\Admins@enable']);
	}

	/**
	 * Commands / Tasks
	 *
	 * @return void
	 */
	public function registerCommands() {
		Route::get('commands', ['uses' => 'Bkwld\Decoy\Controllers\Commands@index', 'as' => 'decoy::commands']);
		Route::post('commands/{command}', ['uses' => 'Bkwld\Decoy\Controllers\Commands@execute', 'as' => 'decoy::commands@execute']);
	}

	/**
	 * Workers
	 *
	 * @return void
	 */
	public function registerWorkers() {
		Route::get('workers', ['uses' => 'Bkwld\Decoy\Controllers\Workers@index', 'as' => 'decoy::workers']);
		Route::get('workers/tail/{worker}', ['uses' => 'Bkwld\Decoy\Controllers\Workers@tail', 'as' => 'decoy::workers@tail']);
	}

	/**
	 * Get the status of an encode
	 *
	 * @return void
	 */
	public function registerEncode() {

		// Get the status of an encode
		Route::get($this->dir.'/encode/{id}/progress', ['as' => 'decoy::encode@progress', function($id) {
			return Encoding::findOrFail($id)->forProgress();
		}]);

		// Make a simply handler for notify callbacks.  The encoding model will pass
		// the the handling onto whichever provider is registered.
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
		Route::get('elements/field/{key}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@field', 'as' => 'decoy::elements@field']);
		Route::post('elements/field/{key}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@fieldUpdate', 'as' => 'decoy::elements@field-update']);
		Route::get('elements/{locale?}/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@index', 'as' => 'decoy::elements']);
		Route::post('elements/{locale?}/{tab?}', ['uses' => 'Bkwld\Decoy\Controllers\Elements@store', 'as' => 'decoy::elements@store']);
	}

	/**
	 * Upload handling for Redactor
	 * http://imperavi.com/redactor/
	 *
	 * @return void
	 */
	public function registerRedactor() {
		Route::post('redactor/upload', 'Bkwld\Decoy\Controllers\Redactor@upload');
	}

	/**
	 * Web service callback endpoints
	 */
	public function registerCallbackEndpoints() {

		// Make a simply handler for notify callbacks.  The encoding model will pass
		// the the handling onto whichever provider is registered.
		Route::post('encode/notify', ['as' => 'decoy::encode@notify', function() {
			return Encoding::notify(Input::get());
		}]);

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
