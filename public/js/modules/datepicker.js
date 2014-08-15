// --------------------------------------------------
// Datepicker - Setup datepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	require('decoy/plugins/bootstrap-datepicker'); // http://cl.ly/1N401g3z3M0E
	
	// Define backbone view
	var DatePicker = Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$input = this.$('input.date');
			this.$hidden = $(':hidden[name='+this.$input.attr('name')+'].date');
			
			// Add the widget
			this.$el.addClass('date').datepicker();
			
		},
		
		// Add events
		events: {
			'change input': 'update',
			'blur input': 'blur'
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
			this.parts = parts;
		},
		
		// Update the input field on blur with the formatted value
		blur: function() {
			if (!this.parts) return;
			this.$input.val(this.parts[1]+'/'+this.parts[2]+'/'+this.parts[3]);
		}
		
	});
	
	// Apply the date picker to each instance on the
	$('.input-group-addon:has(.date)').each(function(i, el) {
		new DatePicker({el:el});
	});
	
});