define(function (require) {
  
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, $body = $('body')
	;

	// Polyfill window.location.origin in for IE
	// http://tosbourn.com/a-fix-for-window-location-origin-in-internet-explorer/
	if (!window.location.origin) {
		window.location.origin = window.location.protocol + "//" + window.location.hostname 
			+ (window.location.port ? ':' + window.location.port: '');
	}
	
	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);
		
		// Cache
		this.key = this.$('input[name="key"]').val();

		// Measure the dimensions of the iframe and report back to the parent.  This
		// is treated like a "ready" event
		this.message('height', $body.height());

		// Listen for closee / cancel events
		this.$('.btn.back').on('click', this.close);

	};

	// Tell the iframe to close
	View.close = function() {
		this.message('close');
	};

	// postMessage helper
	View.message = function(type, value) {
		window.top.postMessage({
			key: this.key,
			type: type,
			value: value
		}, window.location.origin);
	}
	
	// Return view class
	return Backbone.View.extend(View);
});