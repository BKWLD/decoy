<?php return array(
	
	/**
	 * The name of the site is shown in the header of all pages
	 */
	'name' => Config::get('site.name') ? Config::get('site.name') : 'Admin',
	
	/**
	 * This generates the nav sidebar.  It is key-value pairs.  The key is always the
	 * label for the nav item.  The value may be either an array of label-url pairs or
	 * a string URL.  The URL to an index view will use slugs that are the plural of 
	 * the model name.  For instance, the Article model can be found at /admin/articles
	 * 
	 * In addition, you may append an icon name after a label, delimited by
	 * a comma.  The icon name should be the suffix of a Bootstrap Glyphicon.  For example,
	 * to show the `glyphicon-book` icon, just append `,book` to the end of the label. 
	 */
	'nav' => [
		// 'Content,book' => [
		// 	'Articles' => '/admin/articles'
		// ]
	],

	/**
	 * After a succesful login, this is the absolute path or url that should be
	 * redirected to
	 */
	'post_login_redirect' => '/admin/admins',

	/**
	 * Roles that super admins can assign other admins to on the admin edit page.
	 * If left empty, all admins will be assigned to the default level of "admin".
	 */
	'roles' => [
		// 'general' => '<b>General</b> - Can manage sub pages of services and buildings (except for forms)',
		// 'forms' => '<b>Forms</b> - Can do everything a general admin can but can also manage forms.',
	],

	/**
	 * Permissions rules.  These are described in more detail in the README.
	 */
	'permissions' => [
		// 'general' => [
		// 	'cant' => [
		// 		'create.categories',
		// 		'destroy.categories',
		// 		'manage.slides',
		// 		'manage.sub-categories',
		// 		'manage.forms',
		// 	],
		// ],
	],
	
);
