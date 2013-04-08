// --------------------------------------------------
// Fire a notification error event on all AJAX errors.
// This includes Backbone as well as jQuery
// --------------------------------------------------
define(function (require) {
	var $ = require('jquery'),
		notify = require('decoy/modules/notify');
	
	// Apply to all AJAX requests
	$(document).ajaxError(function() {
		notify.error('Server request failed');
	});
});