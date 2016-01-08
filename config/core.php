<?php return array(

	// The directory for the admin
	'dir' => 'admin',

	// The layout to use
	'layout' => 'decoy::layouts.default',

	// Auth guard and policy to use
	'guard'  => 'decoy',
	'policy' => 'Bkwld\Decoy\Auth\Policy@check',

	// Default admin credentials
	'default_login'    => 'redacted',
	'default_password' => 'redacted',

	// Use a password input field for admins
	'obscure_admin_password' => false,

	// Mail FROM info
	'mail_from_name'    => 'Site Admin',
	'mail_from_address' => 'postmaster@'.(app()->runningInConsole() ?
		'locahost' : parse_url(url()->current(), PHP_URL_HOST)),

	// Allow regex in redirect rules
	'allow_regex_in_redirects' => false,

);
