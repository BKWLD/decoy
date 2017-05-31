// --------------------------------------------------
// Show a loading indicator on all AJAX POST, PUT
// and DELETE.
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery')
		, _ = require('underscore')
		, Backbone = require('backbone')
		$doc = $(document)
	;

	// Private static vars
	var app,
		progress = 0, // How many requests have finished
		total = 0; // How many total requests have been made

	// Public view module
	var AjaxProgress = Backbone.View.extend({

		// Constructor
		initialize: function (options) {
			_.bindAll(this);
			app = options.app;

			// Shared vars
			this.$bar = this.$('.progress-bar');
			this.$links = $('.main-nav a[href], .breadcrumbs a, .standard-list a[href*="http://"]:not([target="_blank"]), .progress-link, .form-actions .btn:not([target="_blank"]):not([disabled])');
			this.persist = false;

			// Listen for start and complete
			$doc.ajaxSend(this.send);
			$doc.ajaxComplete(this.complete);
			this.$links.on('click', this.showSpinner);
		},

		// Add progress of a new ajax request, thus making the
		// progress smaller
		send: function() {
			total++;
			this.render();
		},

		// Remove progress of an ajax request cause it finished,
		// thus lengthening the bar
		complete: function() {
			progress++;
			if (progress == total) total = progress = 0; // Totally finished with all requests, so reset
			this.render();
		},

		// when the loader is called from a link click, not an ajax request,
		// show the loader while the sever is responding to the request
		showSpinner: function() {
			// hack to animate to a 99% loader width
			total = 0;
			this.persist = true // Show forever
			this.render();
		},

		// Update the position of the bar
		render: function() {

			// Show and hide the bar
			if (total > 0 || this.persist) this.$bar.stop(true).css('opacity', 1);
			else if (total === 0) this.$bar.stop(true).delay(800).animate({opacity:0}, function() {
				$(this).css('width', 0);
			});

			// Animate the bar
			var perc = (progress + 1) / (total + 1);
			this.$bar.css('width', (perc*100)+"%");
		}

	});

	// Return view
	return AjaxProgress;

});
