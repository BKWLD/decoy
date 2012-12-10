// --------------------------------------------------
// A single Seed Task
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	
	// Private static vars
	var app;
		
	// Public view module
	var SeedTask = Backbone.View.extend({
		
		initialize: function () {
			_.bindAll(this);
			app = this.options.app;
			
		},
		
		// Register interaction events
		events: {
			'click a': 'execute'
		},
		
		// Execute a task.  We allow multiple tasks to be triggered
		// at a time
		execute: function(e) {
			e.preventDefault();
			
			// Vars
			var url = this.$('a').attr('href'),
				spinner_template = this.$('img').first(),
				spinner = spinner_template.clone().show();
			
			// Add a new spinner
			spinner_template.after(spinner);
			
			// Execute link via AJAX POST
			$.ajax(url, {
				type:'POST',
				dataType: 'JSON'
			})
			
			// Success
			.done(function(data) {
				spinner.fadeOut(function() {spinner.remove();});
			})
			
			// Error
			.fail(function() {
				spinner.fadeOut(function() {spinner.remove();});
			});

		}
		
	});
	
	return SeedTask;
});