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
		this.$value = this.$('input[name="value"]');
		this.$submit = this.$('.btn.save');

		// Measure the dimensions of the iframe and report back to the parent.  This
		// is treated like a "ready" event
		this.message('height', $body.height());

		// Listen for close / cancel events
		this.$('.btn.back').on('click', this.close);

		// Listen for form submits
		this.$('form').on('submit', this.saving);

	};

	// Tell the icon to close
	View.close = function() {
		this.message('close')
	};

	// Tell the icon that we're saving
	View.saving = function() {
		this.message('saving', this.$value.val());
		this.$submit.prop('disabled', true);
	}

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