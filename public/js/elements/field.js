define(function (require) {
  
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, CKEditor = window.CKEDITOR
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
		this.$response = this.$('#response');
		this.key = this.$response.data('key') || this.$('[name="key"]').val();
		this.$value = this.$('[name="value"]');
		this.$submit = this.$('.btn.save');

		// If we're handling the saved condition, immediatly send message and stop
		if (this.$response.length) return this.saved();

		// Measure the dimensions of the iframe and report back to the parent.  This
		// is treated like a "ready" event.  If this is field is a WYSIWYG, wait till
		// CKEditor as initialized because it affects the height measurement.  If it's an image, 
		// wait for image to load
		if (this.$value.hasClass('wysiwyg')) CKEditor.on('instanceReady', this.ready);
		else if (this.$('.image-upload').length) this.$('.img-thumbnail').imagesLoaded(this.ready);
		else this.ready();

		// Listen for close / cancel events
		this.$('.btn.back').on('click', this.close);

		// Listen for form submits
		this.$('form').on('submit', this.saving);

	};

	// Tell the parent that the iframe is ready
	View.ready = function() {
		this.message('height', $body.height());
	};

	// Tell the icon to close
	View.close = function() {
		this.message('close')
	};

	// Tell the icon that we're saving
	View.saving = function() {
		this.message('saving');
		this.$submit.prop('disabled', true);
	}

	// Tell the icon that saving has finished and pass the new value
	View.saved = function() {
		this.message('saved', this.$response.html());
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