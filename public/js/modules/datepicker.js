// --------------------------------------------------
// Datepicker - Setup datepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	require('bootstrap-datepicker');
	require('bootstrap-datepicker/dist/css/bootstrap-datepicker3.css')

	// Define backbone view
	var DatePicker = Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$input = this.$('input[type="text"]');
			this.$hidden = this.$('input[type="hidden"]');
			
			// Add the widget
			this.$el.addClass('date').datepicker({
				keyboardNavigation: false, // Makes it possible to manually type in
				todayHighlight: true,
				orientation: 'top left'

			// Update the hidden field whenver the datepicker updates.  Need both
			// hide and changeDate because the plugin keeps touching our hidden field,
			// probably because it has the same selector.
			}).on('changeDate hide', this.update);
		},
		
		// Listen for changes to the datepicker and update the related hidden field
		// with the date in the mysql format
		update: function() {

			// Allow the field to be empty
			if (!this.$input.val()) return this.$hidden.val(null);

			// Make sure the date is valid
			var parts = this.$input.val().match(/^(\d{1,2})\/(\d{1,2})\/(\d{1,2}|\d{4})$/);
			if (!parts) return;
			
			// Pad the numbers
			if (parts[1].length == 1) parts[1] = '0'+parts[1];
			if (parts[2].length == 1) parts[2] = '0'+parts[2];
			if (parts[3].length == 1) parts[3] = '200'+parts[3];
			if (parts[3].length == 2) {
				if (parts[3] - 10 > String(new Date().getFullYear()).substr(2)) parts[3] = '19'+parts[3];
				else parts[3] = '20'+parts[3];
			}
			
			// Update hidden field
			this.$hidden.val(parts[3]+'-'+parts[1]+'-'+parts[2]);
			this.$hidden.trigger('change');
			this.parts = parts;
		},
		
		
	});
	
	// Apply the date picker to each instance on the
	$('.input-group:has(.date)').each(function(i, el) {
		new DatePicker({el:el});
	});
	
});