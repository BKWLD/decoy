<?php

// Some constants
if (!defined('UPLOAD_DELETE'))   define('UPLOAD_DELETE', 'delete-');
if (!defined('UPLOAD_OLD'))      define('UPLOAD_OLD', 'old-');
if (!defined('UPLOAD_REPLACE'))  define('UPLOAD_REPLACE', 'replace-');
if (!defined('FORMAT_DATE'))     define('FORMAT_DATE', 'm/d/y');
if (!defined('FORMAT_DATETIME')) define('FORMAT_DATETIME', 'm/d/y g:i a T');
if (!defined('FORMAT_TIME'))     define('FORMAT_TIME', 'g:i a T');

// Bring in bundle dependencies
Bundle::start('former');
Autoloader::alias('Former\Former', 'Former');
Bundle::start('bkwld');
Bundle::start('messages');
Bundle::start('croppa');
if (Bundle::exists('sentry')) Bundle::start('sentry');

// Load specific interal classes
Autoloader::map(array(
	'Decoy_Base_Controller' => Bundle::path('decoy').'controllers/base.php',
	'Decoy\Base_Model' => Bundle::path('decoy').'models/base.php',
	'Decoy\Tag' => Bundle::path('decoy').'models/tag.php',
	'Decoy\Task' => Bundle::path('decoy').'models/task.php',
	'Decoy\Worker' => Bundle::path('decoy').'models/worker.php',
	'Decoy\Auth_Interface' => Bundle::path('decoy').'library/auth_interface.php',
));

// Load all models
Autoloader::directories(array(
	Bundle::path('decoy').'models',
));

// Decoy namespaced classes are all in the library
Autoloader::namespaces(array(
  'Decoy' => Bundle::path('decoy').'library',
));

// Auto-publish the assets when developing locally
if (Request::is_env('local') && !Request::cli()) {
	
	// Had to use this route rather than Command::run(), I couldn't
	// trigger deploy for some reason.
	ob_start();
	$publisher = new Laravel\CLI\Tasks\Bundle\Publisher;
	$publisher->publish('decoy');
	$publisher->publish('croppa');
	ob_end_clean(); // Supress the output from the above, which does an echo
}

// Load Decoy specific helpers
require_once('helpers.php');

// Tell the Messages bundle to use the transport defined in the
// Decoy config file
Config::set('messages::config.default', Config::get('decoy::decoy.messages_default_transport'));

// Change former's required field HTML
Former\Config::set('required_text', ' <i class="icon-exclamation-sign js-tooltip required" title="Required field"></i>');

// Tell former to include unchecked checkboxes in the post
Former\Config::set('push_checkboxes', true);
