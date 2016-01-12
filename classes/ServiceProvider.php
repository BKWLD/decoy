<?php namespace Bkwld\Decoy;

// Dependencies
use App;
use Bkwld\Decoy\Observers\NotFound;
use Bkwld\Decoy\Observers\Validation;
use Bkwld\Decoy\Fields\Former\MethodDispatcher;
use Config;
use Former\Former;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Symfony\Component\Yaml\Parser;

class ServiceProvider extends BaseServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {

		// Register configs, migrations, etc
		$this->registerDirectories();

		// Register the routes.
		$this->app['decoy.router']->registerAll();

		// Configure Decoy auth setup
		$this->bootAuth();

		// Do bootstrapping that only matters if user has requested an admin URL
		if ($this->app['decoy']->handling()) $this->usingAdmin();

		// Wire up model event callbacks even if request is not for admin.  Do this
		// after the usingAdmin call so that the callbacks run after models are
		// mutated by Decoy logic.  This is important, in particular, so the
		// Validation observer can alter validation rules before the onValidation
		// callback runs.
		$this->app['events']->listen('eloquent.*',     'Bkwld\Decoy\Observers\ModelCallbacks');
		$this->app['events']->listen('decoy::model.*', 'Bkwld\Decoy\Observers\ModelCallbacks');

		// If logging changes hasn't been disabled, log all model events.  This
		// should come after the callbacks in case they modify the record before
		// being saved.  And we're logging ONLY admin actions, thus the handling
		// condition.
		if ($this->app['decoy']->handling() && config('decoy.site.log_changes')) {
			$this->app['events']->listen('eloquent.*', 'Bkwld\Decoy\Observers\Changes');
		}
	}

	/**
	 * Register configs, migrations, etc
	 *
	 * @return void
	 */
	public function registerDirectories() {

		// Publish config files
		$this->publishes([
			 __DIR__.'/../config' => config_path('decoy')
		], 'config');

		// Publish migrations
		$this->publishes([
			__DIR__.'/../migrations/' => database_path('migrations')
		], 'migrations');

		// Register views
		$this->loadViewsFrom(__DIR__.'/../views', 'decoy');

		// Load translations
		$this->loadTranslationsFrom(__DIR__.'/../lang', 'decoy');
	}

	/**
	 * Things that happen only if the request is for the admin
	 */
	public function usingAdmin() {

		// Define constants that Decoy uses
		if (!defined('FORMAT_DATE'))     define('FORMAT_DATE', 'm/d/y');
		if (!defined('FORMAT_DATETIME')) define('FORMAT_DATETIME', 'm/d/y g:i a T');
		if (!defined('FORMAT_TIME'))     define('FORMAT_TIME', 'g:i a T');

		// Load all the composers
		require_once(__DIR__.'/../composers/layouts._nav.php');
		require_once(__DIR__.'/../composers/shared.list._search.php');

		// Register global and named middlewares
		$this->registerMiddlewares();

		// Use Decoy's auth by default, while at an admin path
		Config::set('auth.defaults', [
			'guard'     => 'decoy',
			'passwords' => 'decoy',
		]);

		// Use the Decoy paginator
		Config::set('view.pagination', 'decoy::shared.list._paginator');

		// Set the default mailer settings
		Config::set('mail.from', [
			'address' => Config::get('decoy.core.mail_from_address'),
			'name' => Config::get('decoy.core.mail_from_name'),
		]);

		// Config Former
		$this->configureFormer();

		// Delegate events to Decoy observers
		$this->delegateAdminObservers();
	}

	/**
	 * Boot Decoy's auth integration
	 *
	 * @return void
	 */
	public function bootAuth() {

		// Inject Decoy's auth config
		Config::set('auth.guards.decoy', [
			'driver'   => 'session',
			'provider' => 'decoy',
		]);
		Config::set('auth.providers.decoy', [
			'driver' => 'eloquent',
			'model'  => Models\Admin::class,
		]);
		Config::set('auth.passwords.decoy', [
			'provider' => 'decoy',
			'email'    => 'decoy::emails.reset',
			'table'    => 'password_resets',
			'expire'   => 60,
		]);

		// Point to the Gate policy
		$this->app[Gate::class]->define('decoy.auth', config('decoy.core.policy'));
	}

	/**
	 * Config Former
	 *
	 * @return void
	 */
	protected function configureFormer() {

		// Use Bootstrap 3
		Config::set('former.framework', 'TwitterBootstrap3');

		// Reduce the horizontal form's label width
		Config::set('former.TwitterBootstrap3.labelWidths', []);

		// Change Former's required field HTML
		Config::set('former.required_text', ' <span class="glyphicon glyphicon-exclamation-sign js-tooltip required" title="Required field"></span>');

		// Make pushed checkboxes have an empty string as their value
		Config::set('former.unchecked_value', '');

		// Add Decoy's custom Fields to Former so they can be invoked using the "Former::"
		// namespace and so we can take advantage of sublassing Former's Field class.
		$this->app['former.dispatcher']->addRepository('Bkwld\Decoy\Fields\\');
	}

	/**
	 * Delegate events to Decoy observers
	 *
	 * @return void
	 */
	protected function delegateAdminObservers() {
		foreach([
			'eloquent.saving:*'  => 'Bkwld\Decoy\Observers\Localize',
			'eloquent.saving:*'  => 'Bkwld\Decoy\Observers\Cropping@onSaving',
			'eloquent.deleted:*' => 'Bkwld\Decoy\Observers\Cropping@onDeleted',
			'eloquent.saved:*'   => 'Bkwld\Decoy\Observers\ManyToManyChecklist',
			'eloquent.saving:*'  => 'Bkwld\Decoy\Observers\Encoding@onSaving',
			'eloquent.deleted:*' => 'Bkwld\Decoy\Observers\Encoding@onDeleted',
			'decoy::model.validating:*' => 'Bkwld\Decoy\Observers\Validation@onValidating',
		] as $key => $method) $this->app['events']->listen($key, $method);
	}

	/**
	 * Register middlewares
	 *
	 * @return void
	 */
	protected function registerMiddlewares() {
		foreach([
			'decoy.auth'          => Middleware\Auth::class,
			'decoy.edit-redirect' => Middleware\EditRedirect::class,
			'decoy.guest'         => Middleware\Guest::class,
			'decoy.headers'       => Middleware\Headers::class,
			'decoy.save-redirect' => Middleware\SaveRedirect::class,
		] as $key => $class) $this->app['router']->middleware($key, $class);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Merge own configs into user configs
		$this->mergeConfigFrom(__DIR__.'/../config/core.php',    'decoy.core');
		$this->mergeConfigFrom(__DIR__.'/../config/encode.php',  'decoy.encode');
		$this->mergeConfigFrom(__DIR__.'/../config/site.php',    'decoy.site');

		// Register external packages
		$this->registerPackages();

		// Register HTML view helpers as "Decoy".  So they get invoked like: `Decoy::title()`
		$this->app->singleton('decoy', function($app) {
			return new Helpers;
		});

		// Registers explicit rotues and wildcarding routing
		$this->app->singleton('decoy.router', function($app) {
			$dir = config('decoy.core.dir');
			return new Routing\Router($dir);
		});

		// Wildcard router
		$this->app->singleton('decoy.wildcard', function($app) {
			$request = $app['request'];
			return new Routing\Wildcard(
				config('decoy.core.dir'),
				$request->getMethod(),
				$request->path()
			);
		});

		// Return the active user account
		$this->app->singleton('decoy.user', function($app) {
			$guard = config('decoy.core.guard');
			return $app['auth']->guard($guard)->user();
		});

		// Return a redirect response with extra stuff
		$this->app->singleton('decoy.acl_fail', function($app) {
			return $app['redirect']
				->guest(route('decoy::account@login'))
				->withErrors([ 'error message' => 'You must login first']);
		});

		// Register URL Generators as "DecoyURL".
		$this->app->singleton('decoy.url', function($app) {
			return new Routing\UrlGenerator($app['request']->path());
		});

		// Build the Elements collection
		$this->app->singleton('decoy.elements', function($app) {
			return with(new Collections\Elements)->setModel(Models\Element::class);
		});

		// Build the Breadcrumbs store
		$this->app->singleton('decoy.breadcrumbs', function($app) {
			$breadcrumbs = new Routing\Breadcrumbs();
			$breadcrumbs->set($breadcrumbs->parseURL());
			return $breadcrumbs;
		});

		// Register Decoy's custom handling of some exception
		$this->app->singleton(ExceptionHandler::class, Exceptions\Handler::class);

		// Register commands
		$this->commands([Commands\Generate::class]);
	}

	/**
	 * Register external dependencies
	 */
	private function registerPackages() {

		// Form field generation
		AliasLoader::getInstance()->alias('Former', \Former\Facades\Former::class);
		$this->app->register('Former\FormerServiceProvider');

		// Image resizing
		AliasLoader::getInstance()->alias('Croppa', \Bkwld\Croppa\Facade::class);
		$this->app->register('Bkwld\Croppa\ServiceProvider');

		// PHP utils
		$this->app->register('Bkwld\Library\ServiceProvider');

		// HAML
		$this->app->register('Bkwld\LaravelHaml\ServiceProvider');

		// BrowserDetect
		AliasLoader::getInstance()->alias('Agent', \Jenssegers\Agent\Facades\Agent::class);
		$this->app->register('Jenssegers\Agent\AgentServiceProvider');

		// File uploading
		$this->app->register('Bkwld\Upchuck\ServiceProvider');

		// Creation of slugs
		$this->app->register('Cviebrock\EloquentSluggable\SluggableServiceProvider');

		// Support for cloning models
		$this->app->register('Bkwld\Cloner\ServiceProvider');

		// Probably already registered by the App, but just in case
		AliasLoader::getInstance()->alias('Camo', \Bkwld\Camo\Facade::class);
		$this->app->register('Bkwld\Camo\ServiceProvider');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array(
			'decoy',
			'decoy.acl_fail',
			'decoy.breadcrumbs',
			'decoy.elements',
			'decoy.router',
			'decoy.url',
			'decoy.user',
			'decoy.wildcard',
		);
	}

}
