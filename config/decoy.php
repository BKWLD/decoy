<?php

// The configuration array
return array(
	
	// -----------------------------------------------------
	// Application specfific
	// -----------------------------------------------------
	
	// Branding for the admin area
	'site_name' => 'Osborn Barr',
	
	// Generate the admin nav from a passed array of urls and/or key value pairs.
	// In the key/value scenario, the key is the label, the value
	// is the url.  Also, the link to manage admins is automatically appended.
	'nav' => array(
		'The Site' => array(
			'The Latest' => action('admin.latest'),
			'Home Brand Stories Marquee' => action('admin.home_brand_stories_slides'),
			'-',
			'General Content' => action('decoy::content'),
		), 
		'The Work' => array(
			'Brand Stories' => action('admin.brand_stories'),
			'Workbook' => action('admin.clients'),
		),
		'The Agency' => array(
			'Our Strengths' => action('admin.strengths'),
			'-',
			'Our Agency Marquee' => action('admin.agency_slides'),
			'Offices Slideshow' => action('admin.office_slides'),
			'-',
			'People' => action('admin.people'),
			'Happening Now' => action('admin.instagrams'),
			'-',
			'Careers' => action('admin.careers'),
		),
	),
	
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
	'routes' => array(
		'latest',
		'clients' => array(
			'projects' => array(
				'project_assets',
			)
		),
		'instagrams',
		'careers',
		'home_brand_stories_slides',
		'brand_stories' => array(
			'people' => MANY_TO_MANY,
			'brand_story_slides',
			'brand_story_assets',
		),
		'content',
		'strengths',
		'agency_slides',
		'office_slides',
	),
	
	// Where should the post sign in redirect go to
	'post_login_redirect' => action('admin.latest'),
	
	// -----------------------------------------------------
	// Decoy Defaults
	// -----------------------------------------------------
	
	// The layout to use
	'layout' => 'decoy::layouts.default',
	
	// Directory for saving uploaded images
	'upload_dir' => path('public').'uploads',
	
	// Default admin credentials
	'default_login' => 'redacted',
	'default_password' => 'redacted',
	
	// Messages Mail Sending transport
	'messages_default_transport' => 'mail',
	
	// Mail FROM info
	'mail_from_name' => 'The CMS',
	'mail_from_address' => 'postmaster@'.parse_url(URL::base(), PHP_URL_HOST),
	
);