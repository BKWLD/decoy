/**
 * Define a simple module that bootstrap's Decoy's frontend modules.  It assumes
 * the site's main.js and thus all references are relative to that.  In otherwords,
 * Decoy dependencies will be resolved out of the admin/vendor dir.
 */
define('frontend', function (require) {

	// Dependencies
	var $ = require('jquery');
	require('jquery-backbone-views');

	// Enable elements icons
	$('[data-decoy-el]').views(require('../packages/bkwld/decoy/js/elements/icon'));

});

// Start it up
require(['frontend']);
