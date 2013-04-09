<?php return array(
	
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
	//        'people',
	//     	) 
	//   )
	'routes' => array(),
	
	// Where should the post sign in redirect go to
	'post_login_redirect' => action('admin.news'),
	
	// -----------------------------------------------------
	// Decoy Defaults
	// -----------------------------------------------------
	
	// The directory for the admin
	'dir' => 'admin',
	
	// The layout to use
	'layout' => 'decoy::layouts.default',
	
	// Directory for saving uploaded images
	'upload_dir' => public_path().'/uploads',
	
	// Directory for saving uploaded images
	'ckfinder_upload_dir' => public_path().'/uploads/ckfinder',
	
	// The auth class that should be used.  The default relies on Sentry
	'auth_class' => '\Bkwld\Decoy\Auth\Sentry',
	
	// Default admin credentials
	'default_login' => 'redacted',
	'default_password' => 'redacted',
	
	// Messages Mail Sending transport
	'messages_default_transport' => 'mail',
	
	// Mail FROM info
	'mail_from_name' => 'The CMS',
	'mail_from_address' => 'postmaster@'.parse_url(app()->make('request')->root(), PHP_URL_HOST),
	
);
