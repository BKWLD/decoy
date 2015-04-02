<?php return array(
	
	// The directory for the admin
	'dir' => 'admin',
	
	// The layout to use
	'layout' => 'decoy::layouts.default',
	
	// The auth class that should be used.  The default relies on Sentry
	'auth_class' => '\Bkwld\Decoy\Auth\Sentry',
	
	// Default admin credentials
	'default_login' => 'redacted',
	'default_password' => 'redacted',

	// Use a password input field for admins
	'obscure_admin_password' => false,
	
	// Mail FROM info
	'mail_from_name' => 'Site Admin',
	'mail_from_address' => 'postmaster@'.parse_url(app()->make('request')->root(), PHP_URL_HOST),
	
);
