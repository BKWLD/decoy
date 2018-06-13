# Views

Admin views are stored in /app/views/admin/CONTROLLER where "CONTROLLER" is the lowercased controller name (i.e. "articles", "photos").  For each admin controller, you need to have at least an "edit.php" file in that directory (i.e. /app/views/admin/articles/edit.php).  This file contains a form used for both the /create and /edit routes.


## Grouping form fields

Use a `fieldset` and a div of class `.legend` to contain groups of fields in box.  For instance:

```haml
!= View::make('decoy::shared.form._header', $__data)->render()
%fieldset
  .legend=empty($item)?'New':'Edit'
  != Former::text('title')
  != Former::textarea('body')
```

## Overriding a Decoy partial

You can override any of the Decoy partials on a per-controller basis.  This is done by creating a file structure within a controller's views directory that matches the decoy views structure.  Any mirrored path will be used in place of the Decoy partial.  For instance, if you create a file at `/resources/views/admin/articles/shared/pagination/index.php` you can customize the pagination partial JUST for the articles controller.

In addition, you can override a partial for ALL controllers through built in [Laravel functionality](http://laravel.com/docs/packages#package-views).  For instance, change the sidebar with a file at `/resources/views/vendor/decoy/layouts/sidebar/_sidebar.haml`.

## Sidebar

The sidebar is primarily designed to house related model listings but you can actually store anything in it.  Add items to the Sidebar by calling `$sidebar->add('Content')` from the view.  For instance:

```haml
$sidebar->add(Former::listing('Contributor')->take(30))
$sidebar->add('<p>A paragraph</p>')
```

Note: This must be run **before** the include of the `decoy::shared.form._header` partial.


## Embeded / inline relationship list

A standard list (like seen on index views) can be embedded in form like:

```haml
!= Former::listing('Faqs')->layout('form')->take(100)
```

See the documentation under Form Fields for the full API of `listing()`.


## Data for Former select, radio, and checkbox

A convention to follow is to create a static array on the model that populates Former's select, radio, and checkbox types.  The name of the property holding this array should be the plural form of the column that will store the value(s).  The keys of this array are slugs that are stored in a database column and the values are the readable vesions.  For instance:

```php?start_inline=1
static public $categories = array(
  'inspiring' => 'Inspiring',
  'quirky' => 'Quirky',
  'cool' => 'Cool',
  'adventurous' => 'Adventurous',
);
```

Then, in the edit view, you could do something like this:

```php?start_inline=1
echo Former::checklist('category')->from(Post::$categories)
echo Former::radiolist('category')->from(Post::$categories)
```

Note, the checklist field will POST an array to the server.  You can convert this to a string for storing in the database by [casting](https://laravel.com/docs/5.4/eloquent-mutators#array-and-json-casting) to an array:

```php?start_inline=1
// In your model
protected $casts = [
    'category' => 'array',
];
```

... or you can convert to a string by hooking into the saving event, which is easy in Decoy using the `onSaving` no-op method that you inherit by subclassing the Decoy base model.

```php?start_inline=1
// In your model
public function onSaving()
{
    $this->category = implode(',', $this->category);
}
```

Additionally, you can use this array for searching the list view by referencing it in the `search` property on your controller:

```php?start_inline=1
protected $search = array(
  'title',
  'category' => array(
    'type' => 'select',
    'options' => App\Post::$categories
  ),
);
```

Finally, there is some automatic logic on the list table that will take the values from that column (if specified in the controller `columns` property) and translate it using the static array, assuming you named it to be the plural of the column.

## Toggleable fields

The `auto-toggleable` JS module applies some JS to forms that will allow you to define fields that hide and show based on clicks on "trigger" elements.  For example:

```haml
!= Former::radiolist('type')->from(App\Article::$types)->dataToggleable('type')
!= Former::text('title')
!= Former::wysiwyg('body')->dataShowWhenType('internal')
!= Former::image('image')->dataShowWhenType('internal')
!= Former::text('url', "URL")->dataShowWhenType('external')
```

## Nested, related models

You can edit a child model in the context of it's parent Through special naming conventions.  Take the following form for example:

```haml
!= Former::text('title')
!= Former::text('author[2][name]')
```

When this form submits, Decoy will update the `title` attribute of the model like normal, but will also look up `$model->author()->find(2)` and set the `name` attribute on it to whatever was in the form.
