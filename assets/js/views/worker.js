// --------------------------------------------------
// Hookup controls on for each worker on the worker listing
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Define the view
	return Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$log = this.$('.log');
			this.url = this.$el.data('log-url');
			
			// Base the update rate on the interval that the worker runs
			this.rate = parseInt(this.$el.data('interval'), 10);
			if (!this.rate) this.rate = 5;
			else this.rate = Math.max(5, this.rate);
			this.rate *= 1000; // Convert to ms from s
		},
		
		// Events
		events: {
			'click .actions .btn': 'log_toggle'
		},
		
		// Toggle the log open and close
		log_toggle: function(e) {
			e.preventDefault();
			this.$log.toggleClass('closed');
			
			// Load new data if the log is visible
			if (this.$log.hasClass('closed')) clearTimeout(this.timeout);
			else this.render();
		},
		
		// Tail the log and display it
		render: function() {
			
			// Get the data
			$.get(this.url)
			.done(_.bind(function(data) {
				
				// Insert the output
				this.$log.empty();
				this.$log.text(data);
				
				// Update the log every minute with the latest.  Not using
				// interval so it won't play weird catchup if the user
				// leaves the tab
				this.timeout = setTimeout(this.render, this.rate);
				
			}, this));			
			
		}
		
	});	
	
});