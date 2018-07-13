<?php

namespace Bkwld\Decoy;

use App;
use Config;
use Former\Former;
use Bkwld\Decoy\Observers\Validation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Register configs, migrations, etc
        $this->registerDirectories();

        // Register the routes.
        if (config('decoy.core.register_routes') && !$this->app->routesAreCached()) {
            $this->app['decoy.router']->registerAll();
        }

        // Configure Decoy auth setup
        $this->bootAuth();

        // Do bootstrapping that only matters if user has requested an admin URL
        if ($this->app['decoy']->handling()) {
            $this->usingAdmin();
        }

        // Wire up model event callbacks even if request is not for admin.  Do this
        // after the usingAdmin call so that the callbacks run after models are
        // mutated by Decoy logic.  This is important, in particular, so the
        // Validation observer can alter validation rules before the onValidation
        // callback runs.
        $this->app['events']->listen('eloquent.*',
            'Bkwld\Decoy\Observers\ModelCallbacks');
        $this->app['events']->listen('decoy::model.*',
            'Bkwld\Decoy\Observers\ModelCallbacks');

        // Log model change events after others in case they modified the record
        // before being saved.
        $this->app['events']->listen('eloquent.*',
            'Bkwld\Decoy\Observers\Changes');
    }

    /**
     * Register configs, migrations, etc
     *
     * @return void
     */
    public function registerDirectories()
    {
        // Publish config files
        $this->publishes([
             __DIR__.'/../config' => config_path('decoy')
        ], 'config');

        // Publish decoy css and js to public directory
        $this->publishes([
            __DIR__.'/../dist' => public_path('assets/decoy')
        ], 'assets');

        // Publish lanaguage files
        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/decoy')
        ], 'lang');

        // Register views
        $this->loadViewsFrom(__DIR__.'/../views', 'decoy');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'decoy');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../migrations/');
    }

    /**
     * Things that happen only if the request is for the admin
     */
    public function usingAdmin()
    {

        // Define constants that Decoy uses
        if (!defined('FORMAT_DATE')) {
            define('FORMAT_DATE', __('decoy::base.constants.format_date'));
        }
        if (!defined('FORMAT_DATETIME')) {
            define('FORMAT_DATETIME', __('decoy::base.constants.format_datetime'));
        }
        if (!defined('FORMAT_TIME')) {
            define('FORMAT_TIME', __('decoy::base.constants.format_time'));
        }

        // Register global and named middlewares
        $this->registerMiddlewares();

        // Use Decoy's auth by default, while at an admin path
        Config::set('auth.defaults', [
            'guard'     => 'decoy',
            'passwords' => 'decoy',
        ]);

        // Set the default mailer settings
        Config::set('mail.from', [
            'address' => Config::get('decoy.core.mail_from_address'),
            'name' => Config::get('decoy.core.mail_from_name'),
        ]);

        // Config Former
        $this->configureFormer();

        // Delegate events to Decoy observers
        $this->delegateAdminObservers();

        // Use Boostrap 3 classes in Laravel 5.6
        if (method_exists(Paginator::class, 'useBootstrapThree')) {
            Paginator::useBootstrapThree();
        }
    }

    /**
     * Boot Decoy's auth integration
     *
     * @return void
     */
    public function bootAuth()
    {
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
    protected function configureFormer()
    {
        // Use Bootstrap 3
        Config::set('former.framework', 'TwitterBootstrap3');

        // Reduce the horizontal form's label width
        Config::set('former.TwitterBootstrap3.labelWidths', []);

        // Change Former's required field HTML
        Config::set('former.required_text', ' <span class="glyphicon glyphicon-exclamation-sign js-tooltip required" title="' .
            __('decoy::login.form.required') . '"></span>');

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
    protected function delegateAdminObservers()
    {
        $this->app['events']->listen('eloquent.saving:*',
            'Bkwld\Decoy\Observers\Localize');
        $this->app['events']->listen('eloquent.saving:*',
            'Bkwld\Decoy\Observers\Encoding@onSaving');
        $this->app['events']->listen('eloquent.saved:*',
            'Bkwld\Decoy\Observers\ManyToManyChecklist');
        $this->app['events']->listen('eloquent.deleted:*',
            'Bkwld\Decoy\Observers\Encoding@onDeleted');
        $this->app['events']->listen('decoy::model.validating:*',
            'Bkwld\Decoy\Observers\ValidateExistingFiles@onValidating');
    }

    /**
     * Register middlewares
     *
     * @return void
     */
    protected function registerMiddlewares()
    {

        // Register middleware individually
        foreach ([
            'decoy.auth'          => Middleware\Auth::class,
            'decoy.edit-redirect' => Middleware\EditRedirect::class,
            'decoy.guest'         => Middleware\Guest::class,
            'decoy.save-redirect' => Middleware\SaveRedirect::class,
        ] as $key => $class) {
            $this->app['router']->aliasMiddleware($key, $class);
        }

        // This group is used by public decoy routes
        $this->app['router']->middlewareGroup('decoy.public', [
            'web',
        ]);

        // The is the starndard auth protected group
        $this->app['router']->middlewareGroup('decoy.protected', [
            'web',
            'decoy.auth',
            'decoy.save-redirect',
            'decoy.edit-redirect',
        ]);

        // Require a logged in admin session but no CSRF token
        $this->app['router']->middlewareGroup('decoy.protected_endpoint', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            'decoy.auth',
        ]);

        // An open endpoint, like used by Zendcoder
        $this->app['router']->middlewareGroup('decoy.endpoint', [
            'api'
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Merge own configs into user configs
        $this->mergeConfigFrom(__DIR__.'/../config/core.php', 'decoy.core');
        $this->mergeConfigFrom(__DIR__.'/../config/encode.php', 'decoy.encode');
        $this->mergeConfigFrom(__DIR__.'/../config/site.php', 'decoy.site');

        // Register external packages
        $this->registerPackages();

        // Register HTML view helpers as "Decoy".  So they get invoked like: `Decoy::title()`
        $this->app->singleton('decoy', function ($app) {
            return new Helpers;
        });

        // Registers explicit rotues and wildcarding routing
        $this->app->singleton('decoy.router', function ($app) {
            $dir = config('decoy.core.dir');

            return new Routing\Router($dir);
        });

        // Wildcard router
        $this->app->singleton('decoy.wildcard', function ($app) {
            $request = $app['request'];

            return new Routing\Wildcard(
                config('decoy.core.dir'),
                $request->getMethod(),
                $request->path()
            );
        });

        // Return the active user account
        $this->app->singleton('decoy.user', function ($app) {
            $guard = config('decoy.core.guard');

            return $app['auth']->guard($guard)->user();
        });

        // Return a redirect response with extra stuff
        $this->app->singleton('decoy.acl_fail', function ($app) {
            return $app['redirect']
                ->guest(route('decoy::account@login'))
                ->withErrors([ 'error message' => __('decoy::login.error.login_first')]);
        });

        // Register URL Generators as "DecoyURL".
        $this->app->singleton('decoy.url', function ($app) {
            return new Routing\UrlGenerator($app['request']->path());
        });

        // Build the Elements collection
        $this->app->singleton('decoy.elements', function ($app) {
            return with(new Collections\Elements)->setModel(Models\Element::class);
        });

        // Build the Breadcrumbs store
        $this->app->singleton('decoy.breadcrumbs', function ($app) {
            $breadcrumbs = new Layout\Breadcrumbs();
            $breadcrumbs->set($breadcrumbs->parseURL());

            return $breadcrumbs;
        });

        // Register Decoy's custom handling of some exception
        $this->app->singleton(ExceptionHandler::class, Exceptions\Handler::class);

        // Register commands
        $this->commands([Commands\Generate::class]);
        $this->commands([Commands\Admin::class]);
    }

    /**
     * Register external dependencies
     */
    private function registerPackages()
    {
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
        $this->app->register('Cviebrock\EloquentSluggable\ServiceProvider');

        // Support for cloning models
        $this->app->register('Bkwld\Cloner\ServiceProvider');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'decoy',
            'decoy.acl_fail',
            'decoy.breadcrumbs',
            'decoy.elements',
            'decoy.router',
            'decoy.url',
            'decoy.user',
            'decoy.wildcard',
        ];
    }
}
