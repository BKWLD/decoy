// --------------------------------------------------
// Look for a meta tag called "csrf" and add it's
// value to all AJAX requests as a header
// --------------------------------------------------
define(function (require) {
	var $ = require('jquery');
	
	// Get the CSRF token from the meta tags
	var csrf = $('meta[name="csrf"]').attr('content');
	if (!csrf) return;
	
	// Apply to all AJAX requests
	$.ajaxPrefilter(function(options) {
		var headers = options.headers || {};
		headers['x-csrf'] = csrf;
		options.headers = headers;
	});
});