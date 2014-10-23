<?php return array(
	
	// Branding for the admin area
	'name' => Config::get('site.name') ? Config::get('site.name') : 'Admin',
	
	// Generate the admin nav from a passed array of urls and/or key value pairs.
	// In the key/value scenario, the key is the label, the value
	// is the url.  Also, the link to manage admins is automatically appended.
	'nav' => [
		// 'Content,book' => [
		// 	'Articles' => '/admin/articles'
		// ]
	],
	
	// After a succesfull login, this is the absolute path or url that should be
	// redirected to
	'post_login_redirect' => '/admin/admins',

	// Roles that super admins can assign other admins to on the admin
	// edit page.  If left empty, all admins will be assigned to the default
	// level of "admin".
	'roles' => [],

	// Permissions settings
	'permissions' => [],
	
);
