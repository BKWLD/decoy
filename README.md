# Decoy

## Installation

1. Add `"bkwld/decoy": "~3.0",` to your composer.json and install.  This reflects the latest stable branch.
2. Run `php artisan migrate --package=cartalyst/sentry`
3. Run `php artisan migrate --package=bkwld/decoy`
4. Run `php artisan config:publish bkwld/decoy`


### Contributing

- The `master` branch represents what will be come the next **minor** release.
- A small, low-risk feature for an actively developed project should be created in a feature branch (based on the latest version-branch) and then merged into both the version-branch and master.
- A riskier feature should be worked on in a feature branch and then moved into master.  When it's finished, it can be come part of the next minor vesion release.  This git command gives you a nice view into commits that are new on master versus the most recent version:

	```bash
	git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr)%Creset' --abbrev-commit --date=relative {branch}..master
	```


### Tests

Decoy 2.x adds some unit tests.  To run them, first do a composer install in the Decoy directory with dev resources: `composer install --dev` or `composer update`.  Then (still from the Decoy package directory) run `vendor/bin/phpunit`.  I hope that we continue to add tests for any issues we fix down the road. 



## Routing

Decoy uses custom routing logic to translate it's heirachially path structure into an admin namespaced controller.  Here are some examples of the types of requests that are supported.

*Index*

* GET admin/articles -> Admin\ArticlesController@index
* GET admin/articles/2/article-slides  -> Admin\ArticleSlidesController@index
* GET admin/articles/2/article-slides/5/assets  -> Admin\AssetsController@index

*Create*

* GET admin/articles/create -> Admin\ArticlesController@create
* GET admin/articles/2/article-slides/create  -> Admin\ArticleSlidesController@create

TODO Add more examples

For more info, check out the tests/Routing/TestWildcard.php unit tests.



## Models

Decoy uses the same models as your app uses.  Thus, put them as per normal in /app/models.  However, instead of extending Eloquent, they should sextend Bkwld\Decoy\Models\Base.

### Many to Many relationships

Decoy expects you to name your relationships after the model/table. So a post with many images should have an "images" relationship defined.

The autocomplete UI also expects you to define a `public static $title_column` property in your model with a value that matches the column name that is used for the title.  Currently, you can ONLY match against a single column in the database.

Since we typically add timestamps to pivot tables, you'll want to call `withTimestamps` on relationships.  And, if the pivot rows should be sortable, you'l need to use `withPivot('position')` so that the position value gets rendered to the listing table.  Additionally, the easiest way to have Decoy sort by position in the admin is to add that `orderBy` clause to the relationships as well.  So your full relationship function may look like (don't forget that both models in the relationship need to be defined):

```
public function users() { return $this->belongsToMany('User')->withTimestamps()->withPivot('position')->orderBy('prospect_user.position', 'asc'); }
```

### Many to Many to Self

I am using this term to describe a model that relates back to it self; like a project that has related projects.  You should define two relationship methods as follows:

	public function projects() { return $this->belongsToMany('Project', 'project_projects', 'project_id', 'related_project_id'); }
	public function projectsAsChild() { return $this->belongsToMany('Project', 'project_projects', 'related_project_id', 'project_id'); }

The "AsChild()" naming convention is significant.  The Decoy Base Controller checks for this when generating it's UI.

### Polymorphic relationships

You must use the convention of suffixing polymorphic stuff with "able".  For instance, in a one to many, the child should have a "...able()" relationship function.  For example, in a `Slide` controller, it should be called `slideable()`.

### Polymorphic Many to Many to Self

Example:

	public function services() { return $this->morphedByMany('Service', 'serviceable')->withTimestamps(); }
	public function servicesAsChild() { return $this->morphedByMany('Service', 'serviceable', null, 'serviceable_id', 'service_id')->withTimestamps(); }



## Controllers

A lot of Decoy's "magic" comes by having your admin controllers extend the `Bkwld\Decoy\Controllers\Base`.  I typically have the admin controllers extend an application specific base controller (i.e. `Admin\BaseController`) which then extends the `Bkwld\Decoy\Controllers\Base`.

### Protected properties

The following protected proprties allow you to customize how Decoy works from the parent controller without overriding whole restful methods.  They generally affect the behavior of multiple methods.  They are all named with all-caps to indicate their significance and to differentiate them from other properties you might set in your admin controller.

* `MODEL` - The name of the controller associated with the controller.  For instance, "Client" in the examples above.  If left undefined, it's generated in the constructor based on the singular form of the controller name.  In addition, the constructor defines a class_alias of `Model` that you can use to refer to the model.  For instance, in a "Clients" controller, you could write `Model::find(2)` instead of `Client::find(2)`.
* `CONTROLLER` - The "path", in Laravel terms, of the controller (i.e. "admin.clients").  If left undefined, it's generated in the constructor from the controller class name.
* `TITLE` - The title used for the pages generated by the controller. If left undefined, it's generated in the constructor from the controller class name.
* `DESCRIPTION` - An optional sentenance or two that is displayed with the title in the header of the page.
* `COLUMNS` - An array of key value pairs used to describe what table columns to have in the listing view.  The default is: `array('Title' => 'title')`.  The key is the label of the column, shown in the header of the table.  The value is the source for the data for the column.  Decoy first checks if there is a method defined on the model with the value and, if so, executes it to return the value.  If there is no method, it checks to see if the model has a property (or dynamic property) with that name and uses it's value of it does.  Finally, if none of those cases are true, it will use the value literally, rendering it in every row of the table.  Note: the default value, `title`, is the name of a method defined in `Decoy\Base_Model`.
* `SHOW_VIEW` - The path, in the Laravel format, to the view for the new/edit view.  I.e. 'admin.news.show'.
* `SEARCH` - A multidimensional associative array that tells Decoy what fields to make available to the search on index views.  It expects data like:

	```
	array(
		'title', // 'title' column assumed to be a text type
		'description' => 'text', // Label auto generated from field name
		'body' => array( // Most explicit way
			'type' => 'text',
			'label' => 'Body',
		)
		'type' => array( // Creates a pulldown menu
			'type' => 'select',
			'options' => array(
				'photo' => 'Photo',
				'video' => 'Video',
			),
		),
		'like_count' => array( // Numeric input field
			'type' => 'number',
			'label' => 'Like total',
		),
		'created_at' => 'date', // Date input field
	);
	```

The following properties are only relevant if a controller is a parent or child of another, as in `hasMany()`, `belongsToMany()`, etc.  You can typically use Decoy's default values for these (which are deduced from the `routes` Config property).

* `PARENT_MODEL` - The model used by the parent controller (i.e. "Project").
* `PARENT_CONTROLLER` - The parent controller (i.e. "admin.projects").
* `PARENT_TO_SELF` - The name of the relationship on the parent controller's model that refers to it's child (AKA the *current* controller's model, i.e. for "admin.projects" it would be "projects").
* `SELF_TO_PARENT` - The name of the relationship on the controller's model that refers to it's parent (i.e. for "admin.projects" it would be "client").

### Setting up relational data

To pass the data needed to show related data on an edit page, you need to override the sidebar() no-op method in your controller.  Passing it instances of `Former::listing`.  For instance:

	protected function sidebar($item) {
		return array(
			Former::listing('Block')->take(30),
			Former::listing('Contributor')->take(30),
		);
	}

See the "Form fields" section of this README for more info in it's API.

## Views

Admin views are stored in /app/views/admin/CONTROLLER where "CONTROLLER" is the lowercased controller name (i.e. "articles", "photos").  For each admin controller, you need to have at least an "edit.php" file in that directory (i.e. /app/views/admin/articles/edit.php).  This file contains a form used for both the /create and /edit routes.

### Overriding a Decoy partial

You can override any of the Decoy partials on a per-controller basis.  This is done by creating a file structure within a controller's views directory that matches the decoy views structure.  Any mirrored path will be used in place of the Decoy partial.  For instance, if you create a file at app/views/admin/articles/shared/_pagination.php you can customize the pagination partial JUST for the articles controller.

In addition, you can override a partial for ALL controllers through built in [Laravel functionality](http://laravel.com/docs/packages#package-views).

### Related sidebar

The View file might look like this:

	<?=View::make('decoy::shared.form_with_related._header', $__data)?>
		
		<?= Former::text('title')->class('span6') ?>
		<?= Former::textarea('body')->class('span6 wysiwyg') ?>

	<?=View::make('decoy::shared.form_with_related._footer', $__data)?>

The related data gets passed to the footer partial and rendered automatically.  Note that the form elements are set to span6 rather than span9.

### Embeded / inline relationship list

A standard list (like seen on index views) can be embedded in form like:

	!= Former::listing('Faqs')->parent($item)->layout('form')->take(100)

See the documentation under Form Fields for the full API of `listing()`.


### Data for Former select, radio, and checkbox

A convention to follow is to create a static array on the model that populates Former's select, radio, and checkbox types.  The name of the property holding this array should be the plural form of the column that will store the value(s).  The keys of this array are slugs that are stored in a database column and the values are the readable vesions.  For instance:

	static public $categories = array(
		'inspiring' => 'Inspiring',
		'quirky' => 'Quirky',
		'cool' => 'Cool',
		'adventurous' => 'Adventurous',
	);

Then, in the edit view, you could do this:

	!= Former::checkbox('category')->checkboxes(Bkwld\Library\Laravel\Former::checkboxArray('category', Post::$categories))->push(false)

Furthermore, you can use this array for searching the list view by referencing it in the `search` property on your controller:

	protected $search = array(
		'title',
		'category' => array(
			'type' => 'select',
			'options' => 'Post::$categories'
		),
	);

Finally, there is some automatic logic on the list table that will take the values from that column (if specified in the controller `columns` property) and translate it using the static array, assuming you named it to be the plural of the column.


## Features

### Authentication

[Sentry](http://docs.cartalyst.com/sentry-2), the pacakge that currently powers authentication, automatically logs out any users who may be logged in when someone logs in using the same creds from another computer.  This can be annoying, so admins should switch to using user specific accounts instead of the default redacted account.


### Enabling CKFinder for file uploads

By default, CKFinder is turned off because a new license must be purchased for every site using it.  Here's how to enable it:

1. Enter the `license_name` and `license_key` in your /app/config/packages/bkwld/decoy/wysiwyg.php config file
2. Tell the wysiwyg.js module to turn on CKFinder.  The easiest way to do that is from your /public/js/admin/start.js like so:

		define(function (require) {
			require('decoy/modules/wysiwyg').config.allowUploads();
		});
		

### Fragments

One off strings, images, and files can be managed in Decoy through the Fragments feature.  Fragments work by reading language files and producing a tabbed form from their key value pairs.  The values from the language file are treated as the default for the key; admins can override that default with Decoy.  The frontend developer pulls the fragment value through the `Decoy::frag($key)` helper.

Start by creating new language files in /app/lang/en.  There are some conventions to follow; an example should be suffient to explain:

*/app/lang/en/home.php*
	
	<?php return array(
		'marquee_title' => 'Welcome to the site',
		'marquee.featured_article,belongs_to' => '/admin/articles',

		'intro.title' => 'This is some great stuff',
		'intro.body,textarea' => 'A paragraph of text goes on and on and on and ...',

		'deep_dive.article,wysiwyg' => '<p>Folks often want some <strong>WYSIWYG</strong> tools</p>',
		'deep_dive.headshot,image' => '/img/path/to/heashot',
		'deep_dive.pdf,file' => '/files/path/to/file',
	);
	
Thus:

- Different translation files are treated as virtual pages in the admin.
- Keys can have a bullet that delimits sections and will be used to break up the page into sections in the admin.  This is optional.
- The default format for a field in the admin is a text input.  This can be overidden by specifying a type following the key, delimited with a comma.  The view helper, howerver, may omit this.  In other words, this is valid: `<?=Decoy::frag('deep_dive.pdf')?>`.
- Images **must** be stored in the /public/img directory.  Decoy will automatically make a copy in the uploads directory for Croppa to act on.  Decoy::frag() will then return the path to the uploads copy.  This is done because PagodaBox doesn't let you push things via git to shared writeable directories, so committing the image to the uploads dir would not work.


### Workers

If you make a Laravel command extend from `Bkwld\Decoy\Models\Worker`, the command is embued with some extra functionality.  The following options get added:

- `--worker` - Run command as a worker.  As in not letting the process die.
- `--cron` - Run command as cron.  As in only a single fire per execution.
- `--heartbeat` - Check that the worker is running.  This is designed to be run from cron.

In a standard PagodaBox config, you would put these in your Boxile:

	web1:
		name: app
		cron:
			- "* * * * *": "php artisan <COMMAND> --heartbeat"
	
	worker1:
		name: worker
		exec: "php artisan <COMMAND> --worker"

In this example, "<COMMAND>" is your command name, like "import:feeds".  With a setup like the above (and the default worker static config options), your command will run every minute on PB.  And if the worker fails, the heartbeat will continue running it, at a rate of every 15 min (because of PB rate limiting).

In addition, by subclassing `Bkwld\Decoy\Models\Worker`, the worker command will show up in a listing in the admin at /admin/workers.  From this interface you can make sure the worker is still running and view logs.


### Slugs

Slugs are auto created from columns named title, name, or specified in the model with a `$title_column` static property.  Your model should have a validation rule like:

	'slug' => 'alpha_dash|unique:services

Decoy will automatically add ignore for the current id when submittng an UPDATE request.

##### Slugs unique across multiple columns

If the slug is unique across multiple models, you should do a couple things.  Specify a multi column unqiue index in the schema like:

	$table->unique(array('slug', 'category_id'));

In this example, this table has a one-to-many parent table called `categories`.  Specify a rule in the model like:

	'slug' => 'alpha_dash|unique_with:services,category_id,slug',

That uses the BKWLD library packages `unique_with` validator.  Lastly, you'll need to pass the id to `Input` on submit by adding this to your Decoy view (this is HAML):

	!= Former::hidden('category_id', $parent_id)


### Permissions

Here is an example of a groups and permissions from the Decoy config:

	'roles' => array(
		'general' => '<b>General</b> - Can manage sub pages of services and buildings (except for forms)',
		'forms' => '<b>Forms</b> - Can do everything a general admin can but can also manage forms.',
		'super' => '<b>Super Admin</b> - Can manage everything.',
	),

	'permissions' => array(
		'general' => array(
			'cant' => array(
				'create.categories',
				'destroy.categories',
				'manage.slides',
				'manage.sub-categories',
				'manage.forms',
			),
		),
	),

The roles array generates the list of roles on the Admin edit screen.  The keys of that array become Groups in Sentry.

The permissions array defines what a user can and can't do.  This could have been run through Sentry but I chose my own approach for two reasons:

1. I didn't like having to make database migrations everytime a group permissions configuration changed
2. In many projects, most roles can do almost everything and I wanted to be able to blacklist actions.  Sentry operates from a whitelist-only perspective.

In the example above, you can see that I've specified that the `general` role **cant't** use the `create` or `destroy` actions on the `categories`, `slides`, and `sub-categories` controllers.  The full list of supported actions that can be denied are:

- create
- read
- update
- destroy
- manage (combines all of the above)


### Form fields

The following additional fields come with Decoy.  They are implemented through Former so you can chain any of the standard Former method calls onto them like "blockhelp", etc.

- `Former::date()` 

	- Create a [calendar widget](http://cl.ly/image/0m1L2H1i3o12).
	- Uses [bootstrap-datepicker](http://www.eyecon.ro/bootstrap-datepicker) for the UI. If you set the value to `'now'`, the current date will populate the field``
	
			!= Former::date('date', 'Start date')->value('now'`


- `Former::time()` 

	- Create a time [selector widget](http://cl.ly/image/22062i19133Y).
	- Uses [bootstrap-timepicker](http://jdewit.github.io/bootstrap-timepicker/) for the UI. If you set the value to `'now'`, the current date will populate the field.

			!= Former::time('time')->value('now')


- `Former::datetime()` 

	- Create a [date-time widget](http://cl.ly/image/3I2G1X1h3s3c), which is like the concatenation of the `date()` and `time()` elements.
	- You can set attributes of the date and time inputs, respectively, by chaining `->date($attributes)` and `->time($attributes)` where $attributes is an associative array.
	- To access the Former `Field` instances for each field, access the public properties `$date` and `$time`.

			!= Former::datetime('when')->date(array('data-example', 'Hey'))->value('now')


- `Former::note()`

	- A note field has no actual input elements.  It's a control group with just the passed html value where the inputs would be.

			!= Former::note('Creator', $author->name)


- `Former::upload()`

	- Creates a [file upload field](http://cl.ly/image/1a0q0C0p3V3y) with addtional UI for reviewing the last upload and deleting it.

			!= Former::upload('file')


- `Former::image()` 

	- Creates an [image upload field](http://cl.ly/image/1M03383E293b) with addtional UI for reviewing the last upload and deleting it.
	- Chain `crops($crops->{image})` onto it to use the cropping tool, where `{image}` is the name of the field field.

			!= Former::image('image', 'Profile image')->crops($crops->image)->blockHelp('Choose an image for the user')


- `Former::belongsTo()`

	- Creates an [autocomplete field](http://cl.ly/image/2e3D3E2o2U2K) that populates a foreign key in a belongs to relationship.
	- You must chain `route($route)` to provide the route that can be AJAX GET requested to serve data to the autocomplete.  For example `/admin/products`.

			!= Former::belongsTo('related_product_id', 'Related product')->route('/admin/products')


- `Former::manyToManyChecklist()` 

	- Render a [list of checkboxes](http://cl.ly/image/0b2w0J312z2i) to represent a related many-to-many table.  The underlying Former field `type` is a checkbox.
	- The relationship name is stored in the field `name`.  This is the name of the relationship method that is defined on the model that is currently being edited in Decoy.
	- You must pass it a model instance that is the parent of the manyToMany relationship by chaining `->item($item)`; this is typically the model instance that is being edited in decoy.  An instance of the relationship will be automatically saved as the `value` of the field; you shouldn't specify a `value()`.
	- You may adjust the query that fetches related objects by passing a `callable` to `scope()` which will recieve the query (an `Illuminate\Database\Eloquent\Builder` instance) as it's first argument.

			!= Former::manyToManyChecklist('hubs')->item($item)->scope(function($query) use ($product) { return $query->where('product_id', '=', $product->id); })


- `Former::listing()`

	- Creates an table of model instances like shown in Decoy's index view.  The `name` for the field should be the model class that is being rendered.  Like `Article`.
	- `controller()` specifies the controller name if it can't be automatically determined.  You may also pass it an instance of a controller class.
	- `items()` stores a collection of model instances to display in the list.  This is optional, `listing()` will try and make a query using the model name to form a query.
	- `layout()` allows you to specify the layout.  This is automatically set when subclassing the base controller's `sidebar` no-op.
		- `full` - A full width view like shown on Decoy's index view.
		- `sidebar` - A narrow view like shown in an edit view's related column.
		- `form` - A full width view designed to be show in a horizontal form.
	- `parent()` - Pass an instance of the field being edited if this listing is meant to show children of the parent.  Like in the related sidebar on the edit page.  This is automatically set when subclassing the base controller's `sidebar` no-op.
	- `take()` - A integer; how many rows to display.
	- You may adjust the query that fetches related objects by passing a `callable` to `scope()` which will recieve the query (an `Illuminate\Database\Eloquent\Builder` instance) as it's first argument.

			!= Former::listing('Author')->take(30)->layout('form')->parent($item)


