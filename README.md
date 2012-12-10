# Decoy

The BKWLD CMS framework, packaged as a Laravel Bundle.  It helps the developer in several different ways:

* Handles auth into the CMS and the management of admins with zero configuration.
* Contains a bunch of view partials that can be implemented by binding special variables to the view rendered to generate nice looking, common UI elements.  Like:
	* Standard list views
	* Form header, footers, and sidebars
	* Breadcrumbs
	* Navigation
* Comes with an abstract Decoy_Base_Controller and Decoy_Base_Model class that add utility functions to admin controllers and models.
* The Base_Controller has inheritable RESTful actions that can be used to handle many types of common CRUD operations with no additional programming.  For instance, a simple News management interface only requires creating a mostly empty controller, a model, and a edit form.  All of the logic that pulls records from the database for generating list views, writing rows during new record saves, etc all can be inherited from the Base_Controller.
* Has a built in interface for managing static blocks of text (called Content) that can be used simply by adding a reference to `action(decoy::content)` to the navigation.
* Uses Twitter Bootstrap to inform the UI, making it easy to style unique form elements.
* Has an interface for running Laravel tasks from the browser

Almost all of the behaviors described above are opt-in; everything can be overriden. To use Decoy, you **still create controllers in the application directory**.  Decoy just adds utility to these through inheritance.


## Installation

For now, this Bundle is not available to the artisan bundle installer, it must be installed manually.

1. [Download](http://laravel.com/download) and install Laravel
2. Download this repo and put it at /bundles/decoy.  I recomment `git init`-ing a new repo at /bundles/decoy and adding this repo as a remote (`git remote add origin git@github.com:BKWLD/decoy.git`.  Then `pull`-ing so you have an easy way to get updates and push up your own fixes and features.
3. [Install](http://laravel.com/docs/bundles#installing-bundles) all the bundle dependencies:
   * [bkwld](http://bundles.laravel.com/bundle/bkwld) - Used for the file handlin utilities
   * [croppa](http://bundles.laravel.com/bundle/croppa) - Used for thumbnail generation
   * [sentry](http://bundles.laravel.com/bundle/sentry) - Used to handle access control to the CMS
   * [former](http://bundles.laravel.com/bundle/former) - Used to make generating form elements more terse
   * [messages](http://bundles.laravel.com/bundle/messages) - Used for sending HTML email
4. Initialize Decoy in the bundles.php file with: `'decoy' => array('auto' => true, 'handles' => 'admin'),`
5. To customize the default user credentialt, edit the `default_login` and `default_password` fields within the config file (/config/decoy.php)
6. Run a general migration to create the standard decoy databae tables and generate the default user.
7. Make all admin controllers and models extend from Decoy_Base_Controller and Decoy_Base_Model.  I generally have an application Base_Controller and Base_Model for the front end, both of which inherit from the Decoy classes.
8. Configure Compass to look for sass in the decoy bundle by adding the following to the compass config.rb file: `add_import_path "bundles/decoy/sass"`.  Then, within the sass file for your application's admin area, include the decoy sass with a simple: `@import "decoy";`.  Here's [an example](https://gist.github.com/39230d1241590093986e).

#### Integration with [BukBuilder](https://github.com/BKWLD/buk-builder), Compass, and RequireJS

Decoy ships with a default layout that you'll most likely use.  The following assumes this is the case.  However, you can use a custom layout by setting the appropriate variabel in the config file.

##### BukBuilder

1. Edit the /config/builder.js file so that it expects a seperate js asset for the admin.  This is done with the `admin` path and the `admin-main` asset from [this example](https://gist.github.com/b17024aaf5fc81591114).
2. Add the paths to the footer and header with the BukBuilder id-ed script and style tags to the `templates` block.  If you using the Decoy layout, it will look exactly like shown in this [example](https://gist.github.com/b17024aaf5fc81591114).

##### Compass

1. Configure Compass to look for sass in the decoy bundle by adding the following to the compass config.rb file: `add_import_path "bundles/decoy/sass"`.
2. Then, within the sass file for your application's admin area, include the decoy sass with a simple: `@import "decoy";`.  Here's [an example](https://gist.github.com/39230d1241590093986e).

##### RequireJS

1. Make sure that your require-jquery.js file has the jQuery animate library.  The version that is currently included with BukBuilder does not have it.
2. Make the deployment process run `php artisan bundle:publish decoy` from the CLI.  This could be added to the before_deploy hook if you're using PagodaBox (you'll also need to add /public/bundles to the `shared_writable_dirs`).  This publishes the decoy JS to a web readable directory (/public/bundles/decoy/js).  Locally, this is run automatically on every page request.  I suggest adding public/bundles to the gitignore and your Sublime exlcluded directories.
3. Add a (relative) path (like in the `paths` property) to the the decoy JS directory in the main.js file.  For example, [this](https://gist.github.com/8dcbe9082994fef6b865):
 
	```
	require.config({
		paths: {
			jquery: 'empty:', // jquery is already loaded
			underscore: '../libs/underscore',
			backbone: '../libs/backbone',		
			decoy: '../../bundles/decoy/js'
		}
	});
	```

4. Require the Decoy bootstrap module (/public/bundles/decoy/js/decoy.js) from the application's admin bootstrap (i.e. /public/js/admin/app.js).  I like to return the `app` object from Decoy's bootstrap and use it for the admin's main `app` as well.  For example: [this](https://gist.github.com/6a4a57eaf9073e39596a).
5. Decoy uses some JS plugins that have to use Require.js's shim functionality to load.  These shim definitions must be placed in the application's admin require js config (i.e. /public/js/admin/main.js), they couldn't be made part of Decoy internals.  The required shim definition looks like:

	```
	require.config({
		shim: {
			'decoy/plugins/bootstrap-wysihtml5' : ['decoy/plugins/wysihtml5-0.3.0', 'decoy/plugins/bootstrap-bkwld']
		},
	});
	```


## The config file

The Decoy config file (./config/decoy.php) defines a number of high level parameters.

### Per-application configs

These options should be customized for every deploy of Decoy.

* `site_name` - This is shown in the upper left of the nav bar and in transactional emails.

* `nav` - An array that informs the nav bar menus.  It looks like:

	```
	nav => array(
		'Dropdown Title' => array(
			'Label 1' => '/path/to/page',
			'Label 2' => action('admin.controller'),
			'-', // A horizontal divider
			'Label 3' => action('admin.parent_controller'),
		),
		'Label 4' => action('admin.another_grandchild'),
	)),
	```
	
	Essentially, it is a list of key value pairs; the key is the label that appears in the nav and the value is the URL it should go to.  You can only go one level deep (you can't have menus within menus).  You should also be careful to not add too many items to the root level of the nav because you can run out of width in narrower browsers. 

* `routes` - An array that documents the relationships between controllers.  In Decoy, the controllers need to know whether their models are parent or children to another.  This helps choose how to layout forms, how to make breadcrumbs, and how to create default restful routes to the controllers.  The array looks like this:

	```
	routes => array(
		'simple_controller',
		'parent_controller' => array(
			'child_controller' => array(
				'grandchild_controller',
				'another_grandchild' => MANY_TO_MANY
			)
		),
		'another_simple_one',
	),
	```
	
	The keywords above (i.e. `simple_controller`) are the controller names without the $handles from the bundle.  For instance, the `Admin_News_Photos_Controller` would be listed in `routes` as `'news_photos'`.   
	
	Typically, you'd have links in the `nav` array only for the root level controllers in the `routes` array.  Any children controllers would be made accessible in Decoy UI via the related content sidebar on the create and edit forms (you wouldn't link directly to them).
	
	However, you'll notice that the `another_grandchild` controller has a value of the constant `MANY_TO_MANY`.  When two controller's models relate to one another using Laravel's `has_many_and_belongs_to()`, one of the controllers needs to be identified as the parent of the other for the purposes of organzing them in Decoy.  In the above example, you'd make links to both `child_controller` and  `another_grandchild` in the `nav` array.  But you would only be able to join instances of `another_grandchild` to instances of `child_controller` through `child_controller`'s edit UI.  The value of `MANY_TO_MANY` must be specified to tell Decoy to create routes and UI elements (like an autocomplete menu) unique to `has_many_and_belongs_to()` parent/children.

* `post_login_redirect` - The URL the admin should be taken to after sign in.

### Decoy Defaults

The default values from the bundle may be left alone.  They tweak properties of Decoy's operation.

* `layout` - The layout view that will be used by Decoy.
* `upload_dir` - Where file uploads are saved.
* `default_login` - When the initial Laravel migrations are run, this will be used as the default login username
* `default_password` - … and this will be the password
* `messages_default_transport` - Which "transport" to use from the Message's bundle config.
* `mail_from_name` - The sender name in transactional emails
* `mail_from_address` - The sender adress in transactional emails

## The Decoy_Base_Controller

A lot of Decoy's "magic" comes by having your admin controllers extend the `Decoy_Base_Controller`.  I typically have the admin controllers extend an application specific base controller (i.e. `Admin_Base_Controller`) which then extends the `Decoy_Base_Controller`.

### Restful routes

The Decoy Base implements a full set of restful methods that respond to requests like:

* List

	```
	GET    /admin/clients                 Admin_Clients_Controller->get_index()
	GET    /admin/submissions/denied      Admin_Clients_Controller->get_index('denied')
	GET    /admin/clients/20/projects     Admin_Projects_Controller->get_index(20)
	```

* Create

	```
	GET    /admin/clients/new             Admin_Clients_Controller->get_new()
	POST   /admin/clients/new             Admin_Clients_Controller->post_new()
	GET    /admin/clients/20/projects/new Admin_Projects_Controller->get_new(20)
	POST   /admin/clients/20/projects/new Admin_Projects_Controller->post_new(20)
	```

* Update

	```
	GET    /admin/clients/20              Admin_Clients_Controller->get_edit(20)
	PUT    /admin/clients/20              Admin_Clients_Controller->put_edit(20)
	POST   /admin/projects/4              Admin_Projects_Controller->put_edit(4)
	```

* Delete

	```
	DELETE /admin/clients/20              Admin_Clients_Controller->delete_delete(20)
	GET    /admin/clients/20/delete       Admin_Clients_Controller->delete_delete(20)
	POST   /admin/projects/4/delete       Admin_Projects_Controller->delete_delete(4)
	```

### Restful actions

If you don't need any complicated behavior, it's very possible that an application's admin controller needn't define their own resful actions and can rely on Decoy's default behavior.  Or, maybe just one of the actions (like `get_index()`) needs custom behavior and the rest can inherit from the parent.  Or, maybe your override one of the actions, do something custom (like add an additional `Input` property), and then call the parent action (i.e. `parent::post_new()`).

The logic of the build in restful actions is described below:

* `get_index([mixed $key])` - Lists rows.  If `$key` is numeric, it is assumed the list is of rows from a model that is a child.  The `routes` property of the config is used to determine who the parent is.  If `$key` is a string, it is ignored (the expectation is that you are overriding the `get_index()` method to do something custom, like filter the list).  The rendered output uses the `decoy::shared._standard_list` view partial.

* `get_new([int $id])` - Displays a create new item form.  If `$id` is present, it is assumed that the new item will be added as a child of the parent (defined in the `routes` config property) identified by the `$id`.  Validation logic will be pulled from the model's `$rules` static property.  The rendered output expects there to be a view file at ADMIN_CONTROLLER_VIEWS_PATH.'/show.php'.  For instance, for the `admin.clients` controller, it expects there to be a view file that can be referenced by `admin.clients.show`.  The show.php file should implement form fields using Former.

* `post_new([int $id])` - Create a new item record.  Validation rules are pulled from the same source as `get_new()`.  The model is populated using mass assignment (`$obj->fill()`) so if you have form fields that don't correspond to columns in the database, those should be stripped first by overriding the method in the admin controller.  Any POSTed files will be saved to the `upload_dir` specified in the config file and the absolute path (with respect to the web server's document root) stored in the database.  If the validation rules mention a "slug" column, it will attempt to make the slug from the field identified in the $SLUG_COLUMN property or fields named 'title' or 'name'.

* `get_edit(int $id)` - Display the edit form for a record.  Validation rules and the form view use the same sources as `get_new()`.

* `put_edit(int $id)` - Updates a record.  `post_edit($id)` is an alias of this function.  It handles validation, slugification, and file saving like `post_new()`.  If called via AJAX, will return a 200 code on success.

* `delete_delete(int $id)` - Deletes a record.  `get_delete($id)` and `post_delete($id)` are aliases of this function.  If called via AJAX, will return a 200 code on success.


### Protected properties

The following protected proprties allow you to customize how Decoy works from the parent controller without overriding whole restful methods.  They generally affect the behavior of multiple methods.  They are all named with all-caps to indicate their significance and to differentiate them from other properties you might set in your admin controller.

* `MODEL` - The name of the controller associated with the controller.  For instance, "Client" in the examples above.  If left undefined, it's generated in the constructor based on the singular form of the controller name.  In addition, the constructor defines a class_alias of `Model` that you can use to refer to the model.  For instance, in a "Clients" controller, you could write `Model::find(2)` instead of `Client::find(2)`.
* `CONTROLLER` - The "path", in Laravel terms, of the controller (i.e. "admin.clients").  If left undefined, it's generated in the constructor from the controller class name.
* `TITLE` - The title used for the pages generated by the controller. If left undefined, it's generated in the constructor from the controller class name.
* `DESCRIPTION` - An optional sentenance or two that is displayed with the title in the header of the page.
* `SLUG_COLUMN` - What column should be used as the basis for the slug.  If left undefined but a slug is identified in the validation rules on the model, Decoy will try to use the "name" or "title" columns, if they exist.
* `COLUMNS` - An array of key value pairs used to describe what table columns to have in the listing view.  The default is: `array('Title' => 'title')`.  The key is the label of the column, shown in the header of the table.  The value is the source for the data for the column.  Decoy first checks if there is a method defined on the model with the value and, if so, executes it to return the value.  If there is no method, it checks to see if the model has a property (or dynamic property) with that name and uses it's value of it does.  Finally, if none of those cases are true, it will use the value literally, rendering it in every row of the table.  Note: the default value, `title`, is the name of a method defined in `Decoy_Base_Model`.

The following properties are only relevant if a controller is a parent or child of another, as in `has_many()`, `has_many_and_belongs_to()`, etc.  You can typically use Decoy's default values for these (which are deduced from the `routes` Config property).

* `PARENT_MODEL` - The model used by the parent controller (i.e. "Project").
* `PARENT_CONTROLLER` - The parent controller (i.e. "admin.projects").
* `PARENT_RELATIONSHIP` - The name of the relationship on the parent controller's model that refers to it's child (AKA the *current* controller's model, i.e. for "admin.projects" it would be "projects").
* `CHILD_RELATIONSHIP` - The name of the relationship on the controller's model that refers to it's parent (i.e. for "admin.projects" it would be "client").


### Protected methods

The following methods are used by the default restful methods to do their thing.  If you override any of the restful methods, you may want to still invoke the following to DRY up your code.

* `validate($rules, [array $messages])` - Wraps several bits of common validation logic: runs the `Input::all()` against the passed `$rules` and redirects back to the current page on failure.  It's designed to be run from a POST or PUT.  Returns `false` if there were no errors.  If an XHR request, returns a 400 error instead.
* `breadcrumbs()` - Shortcut for binding the array of breadcrumb data that the breadcrumbs partial and composer expect.
* `breadcrumbs_from_routes(array $links)` - A complicated method that generates an array of breadcrumb data in part by stepping through the parent and child relationship functions on the models associated with controllers.  The array it produces is passed to `breadcrumbs()` upon exit of the function.
* `merge_default_slug()` - Creates a slug using either the `SLUG_COLUMN` protected property or from the `Input`'s name or title values, if they exist.  This function also adjusts `unique` validation rules of models when on an edit view, adding an exception on uniqueness for the current row so that resaving the same slug value doesn't cause a validation error.
* `STATIC eloquent_to_array(array $query)` - Takes the result set of an Eloquent query and converts each instance in the array to a simple associative array.
* `parent_find(int $parent_id)` - Convenience method to fetch a row by it's id on the `PARENT_MODEL`.

The following methods deal with the handling of file attachments:

* `STATIC pre_validate_files()` - When editing some content that has file attachments when using the Decoy HTML macros for generating the form fields, several extra fields are added to the form that would cause errors during validation.  This function adjusts both the `$rules` on the model and the `Input` properties so that validation passes.
* `STATIC move_replace_file_input(string $field_name)` - Part of how the Decoy file HTML macros work involves renaming the file input to 'replace-FIELDNAME'.  This function takes a column name (i.e. 'image') and renames the `replace-*` (i.e. 'replace-image') property from the `$_FILES` array to what the validator would expect (i.e. 'image').
* `STATIC delete_files(Model $item)` - The Decoy HTML macro's add a delete checkbox to all edit views for file input that are not 'required' in the validation rules.  This method looks in `Input` for these checked boxes and deletes the files from the disk and it's value from the passed Eloquent Model instance.
* `STATIC save_files(Model $item)` - This function loops through all the POSTed `$_FILES`, saves the files to the `upload_dir`, and then adds the absolute path (relative to the web server's document root) to the proper property of the passed Eloquent Model instance.
* `STATIC unset_file_edit_inputs()` - Much like `pre_validate_files()`, you would execute this function before mass assignment to massage the `Input`, removing the "extra" input fields that are added by Decoy's HTML file macros.  

## The Decoy_Base_Model
TODO

## View partials
TODO

### Requests that are unique to MANY_TO_MANY controllers
TODO

## Access Control and Admins
TODO

## General Content
TODO

## Tasks
TODO