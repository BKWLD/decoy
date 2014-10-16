// --------------------------------------------------
// Fire a notification error event on all AJAX errors.
// This includes Backbone as well as jQuery
// --------------------------------------------------
define(function (require) {
	var $ = require('jquery');
	require('notifyjs');

	// Need to explicitly request the styles
	require('vendor/notifyjs/dist/styles/bootstrap/notify-bootstrap');
	
	// Switch to a fading animation style
	$.notify.defaults({
		showAnimation: 'fadeIn',
		hideAnimation: 'fadeOut'
	});

	// Apply to all AJAX requests
	$(document).ajaxError(function() {
		$.notify('Server request failed', 'error');
	});
});