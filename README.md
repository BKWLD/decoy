# Decoy

#### Compatibility

Decoy is tested to support:

- Latest Chrome (recommended)
- Latest Firefox
- Latest Safari
- IE 9-11
- iOS 8 Safari on iPhone and iPad
- Latest Android Chrome

#### Version history

See the [Github "Releases"](https://github.com/BKWLD/decoy/releases) history

## Installation

Decoy expects to be installed ontop of [Camo](https://github.com/BKWLD/camo).  In particular, Decoy has dependencies that are part of Camo's dependency list.  For instance, there are some expectating on the version of Compass that is used.

If you **are** installing outside of Camo, here are some steps to get you started.

1. Add `"bkwld/decoy": "~4.1",` to your composer.json and install.  This reflects the latest stable branch.
2. Run `php artisan migrate --package=cartalyst/sentry`
3. Run `php artisan migrate --package=bkwld/decoy`
4. Run `php artisan config:publish bkwld/decoy`


### Contributing

- The `master` branch represents what will be come the next **minor** release.

- A small, low-risk feature for an actively developed project should be created in a feature branch (based on the latest version-branch) and then merged into both the version-branch and master.

- A riskier feature should be worked on in a feature branch and then moved into master.  When it's finished, it can be come part of the next minor vesion release.  This git command gives you a nice view into commits that are new on master versus the most recent version (replace `{branch}` with the latest versioned-branch):

	```bash
	git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr)%Creset' --abbrev-commit --date=relative {branch}..master
	```


### Tests

Decoy 2.x adds some unit tests.  To run them, first do a composer install in the Decoy directory with dev resources: `composer install --dev` or `composer update`.  Then (still from the Decoy package directory) run `vendor/bin/phpunit`.  I hope that we continue to add tests for any issues we fix down the road. 



### Config

On the average project, the only config file that would need changing is the `site.php` file.  All sites will need to customize at least the `nav` and `post_login_redirect` properties.



### Generators

The Decoy workflow begins with generating a migration for a database table using the [standard Laravel approach](http://laravel.com/docs/migrations).  Then, Decoy provides a generator that creates the controller, model, and view for that table.  Run `php artisan decoy:generate Model` where "Model" is the name of the Model class you intend to create.  This should be the singular form of the table you created.

You will now be able to access the index view for the new model by going to "/admin/{plural model name}".  For instance, "/admin/articles".  The generated files will contain commented out, boilerplate code that you can customize for your particular needs.



## Models

Decoy uses the same models as your app uses.  Thus, put them as per normal in /app/models.  However, instead of extending Eloquent, they should sextend Bkwld\Decoy\Models\Base.

### Many to Many relationships

Decoy expects you to name your relationships after the model/table. So a post with many images should have an "images" relationship defined.

The autocomplete UI also expects you to define a `public static $title_column` property in your model with a value that matches the column name that is used for the title.  Currently, you can ONLY match against a single column in the database.

Since we typically add timestamps to pivot tables, you'll want to call `withTimestamps` on relationships.  And, if the pivot rows should be sortable, you'l need to use `withPivot('position')` so that the position value gets rendered to the listing table.  Additionally, the easiest way to have Decoy sort by position in the admin is to add that `orderBy` clause to the relationships as well.  So your full relationship function may look like (don't forget that both models in the relationship need to be defined):

	public function images() { return $this->belongsToMany('Image')->withTimestamps()->withPivot('position')->orderBy('article_image.position'); }

Here is an example of how you can set the `position` column to the `MAX` value, putting the attached record at the end, by using an event callback on the Model that gets attached:

	/**
	 * When attached as a related set the position on the pivot column to the end
	 * 
	 * @param  Illuminate\Database\Eloquent\Model
	 * @return void
	 */
	public function onAttached($parent) {
		if (get_class($parent) == 'Article') {
			$parent->images()->updateExistingPivot($this->id, [
				'position' => $parent->images()->max('article_image.position') + 1,
			]);
		}		
	}


### Many to Many to Self

I am using this term to describe a model that relates back to it self; like a project that has related projects.  You should define two relationship methods as follows:

	public function projects() { return $this->belongsToMany('Project', 'project_projects', 'project_id', 'related_project_id'); }
	public function projectsAsChild() { return $this->belongsToMany('Project', 'project_projects', 'related_project_id', 'project_id'); }

The "AsChild()" naming convention is significant.  The Decoy Base Controller checks for this when generating it's UI.

### Polymorphic relationships

You must use the convention of suffixing polymorphic stuff with "able".  For instance, in a one to many, the child should have a "...able()" relationship function.  For example, in a `Slide` controller, it should be called `slideable()`.

### Polymorphic Many to Many to Self

Example:

	public function services() { return $this->morphedByMany('Service', 'serviceable', null, 'serviceable_id', 'service_id')->withTimestamps(); }
	public function servicesAsChild() { return $this->morphedByMany('Service', 'serviceable')->withTimestamps(); }



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
		'category' => array( // Creates a pulldowon using static array on Post model
			'type' => 'select',
			'options' => 'Post::$categories'
		),
		'like_count' => array( // Numeric input field
			'type' => 'number',
			'label' => 'Like total',
		),
		'created_at' => 'date', // Date input field
	);
	```

The following properties are only relevant if a controller is a parent or child of another, as in `hasMany()`, `belongsToMany()`, etc.  You can typically use Decoy's default values for these (which are deduced from the `nav` Config property).

* `PARENT_MODEL` - The model used by the parent controller (i.e. "Project").
* `PARENT_CONTROLLER` - The parent controller (i.e. "admin.projects").
* `PARENT_TO_SELF` - The name of the relationship on the parent controller's model that refers to it's child (AKA the *current* controller's model, i.e. for "admin.projects" it would be "projects").
* `SELF_TO_PARENT` - The name of the relationship on the controller's model that refers to it's parent (i.e. for "admin.projects" it would be "client").



## Views

Admin views are stored in /app/views/admin/CONTROLLER where "CONTROLLER" is the lowercased controller name (i.e. "articles", "photos").  For each admin controller, you need to have at least an "edit.php" file in that directory (i.e. /app/views/admin/articles/edit.php).  This file contains a form used for both the /create and /edit routes.


### Grouping form fields

Use a `fieldset` and a div of class `.legend` to contain groups of fields in box.  For instance:

	!= View::make('decoy::shared.form._header', $__data)
	%fieldset
		.legend=empty($item)?'New':'Edit'
		!= Former::text('title')
		!= Former::textarea('body') 


### Overriding a Decoy partial

You can override any of the Decoy partials on a per-controller basis.  This is done by creating a file structure within a controller's views directory that matches the decoy views structure.  Any mirrored path will be used in place of the Decoy partial.  For instance, if you create a file at app/views/admin/articles/shared/_pagination.php you can customize the pagination partial JUST for the articles controller.

In addition, you can override a partial for ALL controllers through built in [Laravel functionality](http://laravel.com/docs/packages#package-views).


### Sidebar

The sidebar is primarily designed to house related model listings but you can actually store anything in it.  Add items to the Sidebar by calling `$sidebar->add('Content')` from the view.  For instance:

	- $sidebar->add(Former::listing('Contributor')->take(30))
	- $sidebar->add('<p>A paragraph</p>')
	
Note: This must be run **before** the include of the `decoy::shared.form._header` partial.


### Embeded / inline relationship list

A standard list (like seen on index views) can be embedded in form like:

	!= Former::listing('Faqs')->layout('form')->take(100)

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


### Toggleable fields

The `auto-toggleable` JS module applies some JS to forms that will allow you to define fields that hide and show based on clicks on "trigger" elements.  For example:

	!= Former::radios('type')->radios(Bkwld\Library\Laravel\Former::radioArray(Article::$types))->dataToggleable('type')
	!= Former::text('title')
	!= Former::wysiwyg('body')->dataShowWhenType('internal')
	!= Former::image('image')->dataShowWhenType('internal')
	!= Former::text('url', "URL")->dataShowWhenType('external')


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
		

### Fragments *(To be deprecated in 5.0)*

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
		'deep_dive.video,video-encoder' => '',
	);
	
Thus:

- Different translation files are treated as virtual pages in the admin.
- Keys can have a bullet that delimits sections and will be used to break up the page into sections in the admin.  This is optional.
- The default format for a field in the admin is a text input.  This can be overidden by specifying a type following the key, delimited with a comma.  The view helper, howerver, may omit this.  In other words, this is valid: `<?=Decoy::frag('deep_dive.pdf')?>`.
- Images **must** be stored in the /public/img directory.  Decoy will automatically make a copy in the uploads directory for Croppa to act on.  Decoy::frag() will then return the path to the uploads copy.  This is done because PagodaBox doesn't let you push things via git to shared writeable directories, so committing the image to the uploads dir would not work.


### Elements

Copy, images, and files that aren't managed as part of an item in a list.  If content needs to be managed and a model doesn't make sense, use Elements.  Elements are managed from both the frontend of the site:

![](http://yo.bkwld.com/image/0f2t150O380B/Image%202014-11-11%20at%202.10.37%20PM.png)

... and the backend:

![](http://yo.bkwld.com/image/3X3C0r1H2g1D/Image%202014-11-11%20at%202.21.23%20PM.png)

##### Setup

Begin by customizing the `app/config/packages/bkwld/decoy/elements.yaml` file that will have been published during the Decoy installation.  Roughly speaking, there are 3 nested layers of hashes that configure elements:

- A page
	- A section
		- A field
		
The syntax has a terse form:

	homepage:
		marquee:
			image,image: /img/temp/home-marquee.jpg

And an expanded form:

	homepage:
		label: The homepage
		help: This is the site homepage
		sections:
			marquee:
				label: Home marquee
				help: The featured image section on the top of homepage
				fields:
					image:
						type: image
						label: An image
						value: /img/temp/home-marquee.jpg

The two forms can be intermixed. Check out the `elements.yaml` file for more examples.

Alternatively, you can create a directory at `app/config/packages/bkwld/decoy/elements` and create many different *.yaml files within there. They all share the same syntax as the main `elements.yaml` and get merged into recursively merged into one another.

##### Usage

Call `Decoy::el('key')` in your frontend views to return the value for an Element.  They key is the `.` concatented keys for the three heirachies: `page.section.field`.  The value will be massaged in different ways depending on the element type:

- Texteareas will have `nl2br()` applied
- WYSIWYG will be wrapped in a `<p>` if there is no containing HTML element
- Images will be copied out of the /img directory and into /uploads

To enable frontend ending of an Element, add a `data-decoy-el` attribute to the containing HTML element with a value equal to the Element key.  It is positioned using [Bootstrap Tooltips](http://getbootstrap.com/javascript/#tooltips) and many of its data attribute configs are supported.  For instance `data-placement` will specify on which side of the container the droplet icon is placed.  Here's a HAML example:

	.title(data-decoy-el='homepage.marquee.title') !=Decoy::el('homepage.marquee.title')
	%img(src=Decoy::el('homepage.marquee.image') data-decoy-el='homepage.marquee.image' data-placement='bottom') 

##### Additional notes

- The default format for a field in the admin is a text input
- Images **must** be stored in the /public/img directory.  Decoy will automatically make a copy in the uploads directory for Croppa to act on.  Decoy::el() will then return the path to the uploads copy.  This is done because PagodaBox doesn't let you push things via git to shared writeable directories, so committing the image to the uploads dir would not work.
- YAML only allows whitespace indenting, no tabs


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


- `Former::wysiwyg()`

	- Create a textarea that will be wrapped in a WYSIWYG editor by Decoy JS.

			!= Former::wysiwyg('body')


- `Former::upload()`

	- Creates a [file upload field](http://cl.ly/image/1a0q0C0p3V3y) with addtional UI for reviewing the last upload and deleting it.

			!= Former::upload('file')


- `Former::image()` 

	- Creates an [image upload field](http://cl.ly/image/1M03383E293b) with addtional UI for reviewing the last upload and deleting it.

			!= Former::image('image', 'Profile image')->blockHelp('Choose an image for the user')

	- To enable in-browser cropping, add a static `$crops` array to the model.  There should be a key for each field on the model that stores an image and should be corpped.  The values are a hash of different cropping styles for that image.  For instance, you could use the same image in two places where, in the first, it needs to be square and, in the second, it can be anything.  Here is an example of this config:

			static public $crops = array(
				'image' => array('default'),
				'marquee_image' => array('default' => '16:9'),
			);


- `Former::videoEncoder()` 

	- Creates a [video upload field](http://yo.bkwld.com/image/1R3V1T2o1R1P) with addtional UI for checking the progress of the encoding and then playing back the video.
	- Review the feature on Encoding from this doc for more information on the setup of the video encoding feature of Decoy.

			!= Former::videoEncoder('video')


- `Former::belongsTo()`

	- Creates an [autocomplete field](http://cl.ly/image/2e3D3E2o2U2K) that populates a foreign key in a belongs to relationship.
	- You must chain `route($route)` to provide the route that can be AJAX GET requested to serve data to the autocomplete.  For example `/admin/products`.

			!= Former::belongsTo('related_product_id', 'Related product')->route('/admin/products')


- `Former::manyToManyChecklist()` 

	- Render a [list of checkboxes](http://cl.ly/image/0b2w0J312z2i) to represent a related many-to-many table.  The underlying Former field `type` is a checkbox.
	- The relationship name is stored in the field `name`.  This is the name of the relationship method that is defined on the model that is currently being edited in Decoy.
	- You may adjust the query that fetches related objects by passing a `callable` to `scope()` which will recieve the query (an `Illuminate\Database\Eloquent\Builder` instance) as it's first argument.
	- You can display the results in two columns rather than one by chaining `addGroupClass('two-col')`

			!= Former::manyToManyChecklist('hubs')->scope(function($query) use ($product) { return $query->where('product_id', '=', $product->id); })


- `Former::listing()`

	- Creates an table of model instances like shown in Decoy's index view.  The `name` for the field should be the model class that is being rendered.  Like `Article`.
	- `controller()` specifies the controller name if it can't be automatically determined.  You may also pass it an instance of a controller class.
	- `items()` stores a collection of model instances to display in the list.  This is optional, `listing()` will try and make a query using the model name to form a query.
	- `layout()` allows you to specify the layout.  This is automatically set when passing a `Listing` instance to `$sidebar->add()` from a view.
		- `full` - A full width view like shown on Decoy's index view.
		- `sidebar` - A narrow view like shown in an edit view's related column.
		- `form` - A full width view designed to be show in a horizontal form.
	- `take()` - A integer; how many rows to display.
	- You may adjust the query that fetches related objects by passing a `callable` to `scope()` which will recieve the query (an `Illuminate\Database\Eloquent\Builder` instance) as it's first argument.

			!= Former::listing('Author')->take(30)->layout('form')


### Video encoding

The `Former::videoEncoder` form field creates the upload field for a video in the admin.  However, there is additional setup that the developer must do to make video encoding work.  Currently, only one provider is supported for video encoding, [Zencoder](https://zencoder.com/), but it's implementation is relatively abstracted; other providers could be added in the future.

You'll need to edit the Decoy "encoding.php" config file.  It should be within your app/configs/packages directory.  The comments for each config parameter should be sufficient to explain how to use them.  Depending on where you are pushing the encoded videos to, you may need to spin up an S3 instance.  If you push to SFTP you can generate a key-pair locally (`ssh-keygen`), post the private key to [Zencoder](https://app.zencoder.com/account/credentials) and then add the public key to the server's authorized_keys.

Note: by default, segmented files for [HTTP Live Streaming](http://en.wikipedia.org/wiki/HTTP_Live_Streaming) while be created.  This increases encoding cost and time but will create a better experience for mobile users.  To disable this, set the `outputs` config to have `'playlist' => false`.

Then, models that support encoding should use the `Bkwld\Decoy\Models\Traits\Encodable` trait.  You also need to add a validator to the field of `video:encode`. You may want to add an accessor for building the video tag like:

```php
	public function getVideoTagAttribute() {
		return (string) $this->encoding()->tag->preload();
	}
```

You may want to use [Ngrok](https://ngrok.com/) to give your dev enviornment a public address so that Zencoder can pickup the files to convert.


### Localization

Decoy's localization features come in two flavors: (1) localization of Elements and (2) localization of model instances.  In both cases, you begin by editing the Decoy "site.php" config file.

##### Elements localization

Add each locale you need to support to the `locales` array, where the keys are a slug for the locale and the value is the readable name for the locale.  The former is stored in the database while the latter is displayed in form elements in Decoy.  As long as there are more than one locale in that array, the Elements UI will show a locale selector:

![](http://yo.bkwld.com/image/3h1z3G3D0o11/Image%202014-12-01%20at%2010.24.18%20AM.png)

##### Model localization

To localize model records, the database table for your model needs to have the following columns:

- VARCHAR `locale`
- VARCHAR `locale_group`

Here is an example migration schema:

```php
Schema::create('articles', function(Blueprint $table) {
	$table->string('locale');
	$table->string('locale_group')->index();
	$table->boolean('visible')->nullable(); // Not required, just an example
	$table->index(['locale', 'visible']); // You'll want to use locale in indexes
	$table->index(['visible', 'locale']);
});
```

Then, either set the `auto_localize_root_models` config to `true` in the "site.php" file OR (to control localization more granularly) set the `$localizable` static property of a model to `true`:

```php
class Article extends Base {
	static public $localizable = true;
}
```

If you set the `$localizable` property to false, it will override the global `auto_localize_root_models` config for the model.  When a model has been set to be localizeable, the following two UI elements are automatically added to edit forms.  In the left column, you can change the locale of the model being edited:

![](http://yo.bkwld.com/image/313Q2K0P3P1e/Image%202014-12-01%20at%2010.32.09%20AM.png)

In the right column, you can duplicate the current model into a different locale, where you can begin to customize the fields for *that* locale:

![](http://yo.bkwld.com/image/2W1G243m1p12/Image%202014-12-01%20at%2010.31.04%20AM.png)

The "Compare" radio buttons enable tooltips for each form group that show the value of that group in the selected locale.

##### Frontend implementation

Decoy provides some helpers for selecting a locale on the frontend of the site.  It's assumed that there will be some way that a user chooses a locale.  This might be from a menu or by accessing the site from a special domain.  It is up to the developer to implement this uniquely for the site.  To tell Decoy what was selected, call `Decoy::locale($locale)` where `$locale` is the slug of the locale (the key from the "site.php" `locales` config array).  Here is an example route that would set the locale based on what was passed in:

```php
Route::get('locale/{locale}', ['as' => 'change locale', function($locale) {
	Decoy::locale($locale);
	return Redirect::back();
}]);
```

You can get the current locale by calling `Decoy::locale()` with no argument.  The Decoy Base Model provides a scope for restricting queries by the current locale by chaining `->localize()` onto your query.  For instance:

```php
Article::ordered()->visible()->localize()->paginate(10)
```
