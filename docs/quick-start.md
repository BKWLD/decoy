## Config

On the average project, the only config file that would need changing is the `site.php` file.  All sites will need to customize at least the `nav` and `post_login_redirect` properties.

## Generators

The Decoy workflow begins with generating a migration for a database table using the [standard Laravel approach](http://laravel.com/docs/migrations).  Then, Decoy provides a generator that creates the controller, model, and view for that table.  Run `php artisan decoy:generate Model` where "Model" is the name of the Model class you intend to create.  This should be the singular form of the table you created.

You will now be able to access the index view for the new model by going to "/admin/{plural model name}".  For instance, "/admin/articles".  The generated files will contain commented out, boilerplate code that you can customize for your particular needs.

## Routing

#### Standard CRUD routes

Decoy registers a wildcard `admin/*` route. It parses the requested path to determine what controller and action should be used.  The route is parsed by taking dash-delimited slugs of the controller and converting them to StudlyCased controller names and snakeCased actions on the controller.  You can read the Bkwld\Decoy\Controllers\Base docs for all of the default actions, but here's an example of the more significant ones:

Assuming your model is `App\ProjectCategory` with a controller of `App\Http\Controllers\Admin\ProjectCategories`:

- `GET /admin/project-categories` - List all project categories
- `GET /admin/project-categories/create` - Form to create a new category
- `POST /admin/project-categories/create` - Create a new category and redirect to edit action
- `GET /admin/project-categories/2/edit` - Form to edit category with primary key of `2`
- `POST /admin/project-categories/2/edit` - Update the category and redirect to same edit action
  - Also supports `PUT /admin/project-categories/2` and `POST /admin/project-categories/2`
- `GET /admin/project-categories/2/destroy` - Delete the category
  - Also supports `DELETE /admin/project-categories/2`

#### Creating custom routes

Decoy's wildcard router will interfere with creating custom /admin routes because it runs before app routes gets registered (and I [haven't found a way](https://github.com/BKWLD/decoy/issues/490) to delay it).  You can stop Decoy from registering it's routes via the `decoy.core.register_routes` boolean config value and then manually register them after you finish registering your own routes. Thus:

- `config/decoy/core.php`:

  ```php?start_inline=1
  'register_routes' => false,
  ```

- `app/Http/routes.php`:

  ```php?start_inline=1
  // Register custom "example" action
  Route::group([
    'middleware' => 'decoy.protected',
    'prefix' => 'admin',
    'namespace' => 'Admin',
  ], function() {
    Route::get('project-categories/example', 'ProjectCategories@example');
  });

  // Register rest of Decoy routes manually
  app('decoy.router')->registerAll();
  ```
