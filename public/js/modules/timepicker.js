// --------------------------------------------------
// Timepicker - Setup timepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	require('decoy/plugins/bootstrap-timepicker'); // http://cl.ly/0r0P0L1G142g
	
	// Define backbone view
	var TimePicker = Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$input = this.$('input.time');
			this.$hidden = $(':hidden[name='+this.$input.attr('name')+'].time');
			
			// Add the widget
			this.$el.addClass('bootstrap-timepicker');
			this.$input.timepicker({ defaultTime: false });
			this.$widget = this.$('.bootstrap-timepicker-widget');
			
			// Position the widget over the add-on
			this.$widget.css({
				left: this.$('.add-on').position().left - 5
			});
			
			// Strip input names in the widget so it doesn't get submitted
			this.$widget.find('input').each(function() {
				$(this).attr('name', null);
			});
			
		},
		
		// Add events
		events: {
			'change input': 'update',
			'focus input': 'focus'
		},
		
		// Update hidden field when value changes
		update: function() {
			
			// Make sure the date is valid
			var parts = this.$input.val().match(/^(\d{1,2}):(\d{1,2}) (am|pm)$/i);
			if (!parts) return;
			
			// Adjust hours
			if (parts[3].toLowerCase() == 'pm' && parts[1] < 12) {
				parts[1] = parseInt(parts[1], 10) + 12;
			} else if (parts[3].toLowerCase() == 'am' && parts[1] > 11) {
				parts[1] = '0';
			}
			
			// Pad the numbers
			if (parts[1].length == 1) parts[1] = '0'+parts[1];
			if (parts[2].length == 1) parts[2] = '0'+parts[2];
			
			// Update hidden field
			this.$hidden.val(parts[1]+':'+parts[2]+':00');
		},
		
		// Show the modal on focs
		focus: function() {
			this.$input.timepicker('showWidget');
		}
		
	});
	
	// Apply the picker to each instance on the page
	$('.input-append:has(.time)').each(function(i, el) {
		new TimePicker({el:el});
	});
	
});