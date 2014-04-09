<?php return array(
	
	// -----------------------------------------------------
	// Application specfific
	// -----------------------------------------------------
	
	// Branding for the admin area
	'site_name' => Config::get('site.name') ? Config::get('site.name') : 'Admin',
	
	// Generate the admin nav from a passed array of urls and/or key value pairs.
	// In the key/value scenario, the key is the label, the value
	// is the url.  Also, the link to manage admins is automatically appended.
	'nav' => array(),
	
	// After a succesfull login, this is the absolute path or url that should be
	// redirected to
	'post_login_redirect' => '/admin/admins',

	// Roles that super admins can assign other admins to on the admin
	// edit page.  If left empty, all admins will be assigned to the default
	// level of "admin".
	'roles' => array(),

	// Permissions settings
	'permissions' => array(),
	
	// -----------------------------------------------------
	// Decoy Defaults
	// -----------------------------------------------------
	
	// The directory for the admin
	'dir' => 'admin',
	
	// The layout to use
	'layout' => 'decoy::layouts.default',
	
	// Directory for saving uploaded images
	'upload_dir' => public_path().'/uploads',
	
	// The auth class that should be used.  The default relies on Sentry
	'auth_class' => '\Bkwld\Decoy\Auth\Sentry',
	
	// Default admin credentials
	'default_login' => 'redacted',
	'default_password' => 'redacted',
	
	// Mail FROM info
	'mail_from_name' => 'Site Admin',
	'mail_from_address' => 'postmaster@'.parse_url(app()->make('request')->root(), PHP_URL_HOST),
	
);
