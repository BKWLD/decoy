# Decoy

The Decoy 2.x docs are very incomplete.  The old docs can be found here: https://github.com/BKWLD/decoy/blob/laravel-3/README.md

## Tests

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

## Controllers

TODO

## Views

Admin views are stored in /app/views/admin/CONTROLLER where "CONTROLLER" is the lowercased controller name (i.e. "articles", "photos").  For each admin controller, you need to have at least an "edit.php" file in that directory (i.e. /app/views/admin/articles/edit.php).  This file contains a form used for both the /create and /edit routes.

TODO Describe changing the layout and index