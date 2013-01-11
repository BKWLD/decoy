<?php

// The configuration array
$config =  array(
	
	// -----------------------------------------------------
	// Application specfific
	// -----------------------------------------------------
	
	// Branding for the admin area
	'site_name' => 'CMS',
	
	// Generate the admin nav from a passed array of urls and/or key value pairs.
	// In the key/value scenario, the key is the label, the value
	// is the url.  Also, the link to manage admins is automatically appended.
	'nav' => array(),
	
	// Multidimensional array that is used to create routes.  It is also used to
	// produce breadcrumbs and some other elements of the magic in the base
	// controller.  Expects an array of controller slugs like:
	//   array(
	//     'news', 
	//     'events' => array(
	//     	  'photos',
	//        'people' => MANY_TO_MANY,
	//     	) 
	//   )
	'routes' => array(),
	
	// Where should the post sign in redirect go to
	'post_login_redirect' => action('admin.news'),
	
	// -----------------------------------------------------
	// Decoy Defaults
	// -----------------------------------------------------
	
	// The layout to use
	'layout' => 'decoy::layouts.default',
	
	// Directory for saving uploaded images
	'upload_dir' => path('public').'uploads',
	
	// Directory for saving uploaded images
	'ckeditor_upload_dir' => path('public').'uploads/ckeditor',
	
	// Default admin credentials
	'default_login' => 'redacted',
	'default_password' => 'redacted',
	
	// Messages Mail Sending transport
	'messages_default_transport' => 'mail',
	
	// Mail FROM info
	'mail_from_name' => 'The CMS',
	'mail_from_address' => 'postmaster@'.parse_url(URL::base(), PHP_URL_HOST),
	
	// The auth class that should be used.  The default Decoy\Auth class
	// relies on Sentry.  The class must implement Decoy\iAuth
	'auth_class' => 'Decoy\Auth',
	
);

// Load a 'decoy' config file from the application directory and use it's values
return array_merge($config, (array) Config::get('decoy'));