<?php return array(
	
	/**
	 * Choose which WYSIWYG editor to use.  Options:
	 *    - redactor (default)
	 *    - ckeditor
	 */
	'vendor' => 'redactor',

	/*
	|--------------------------------------------------------------------------
	| CKEditor only config
	|--------------------------------------------------------------------------
	*/

	'ckeditor' => [

		/**
		 * Directory for saving uploaded images
		 */
		'upload_dir' => public_path().'/uploads/ckfinder',
		
		/**
		 * Site specific license for CKFinder (required)
		 */
		'license_name' => null,
		'license_key' => null,

	],

);