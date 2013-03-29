// --------------------------------------------------
// Hookup controls on for each worker on the worker listing
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Settings
	var update_sleep = 5*1000;

	// Define the view
	return Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$log = this.$('.log');
			this.url = this.$el.data('log-url');
			
			// Fetch the log
			this.render();
			
		},
		
		// Events
		events: {
			'click .actions .btn': 'log_toggle'
		},
		
		// Toggle the log open and close
		log_toggle: function(e) {
			e.preventDefault();
			this.$log.toggleClass('hide');
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
				_.delay(this.render, update_sleep);
				
			}, this));			
			
		}
		
	});	
	
});