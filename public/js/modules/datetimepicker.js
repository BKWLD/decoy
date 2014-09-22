// --------------------------------------------------
// Datetimepicker - Setup datetimepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Define backbone view
	var DateTimePicker = Backbone.View.extend({
		
		// Constructor
		initialize: function() {
			_.bindAll(this);

			// Cache selectors
			this.$hidden_date = this.$('.date-field input[type="hidden"]');
			this.$hidden_time = this.$('.time-field input[type="hidden"]');
			this.$hidden = this.$('> input[type="hidden"]').last();
			
			// Make UI look better
			this.move();

			// Events
			this.$hidden_date.add(this.$hidden_time).on('change', this.change);
		},
		
		// Move the time picker into the date picker controls
		move: function() {
			
			// Selectors
			var $controls = this.$('.date-field .controls')
				, $time = this.$('.time-field .input-append')
				, $time_control_group = $time.closest('.control-group')
				, $date_help = $controls.find('.help-block')
				, $time_help = $time_control_group.find('.help-block')
			;
			
			// Move it
			$controls.append($time);
			
			// Add spacing
			$time.css('margin-left', 5);
			
			// Move over help text
			if ($time_help.length && $date_help.length) $date_help.append($time_help.text());
			else if ($time_help.length) $controls.append($time_help);
			
			// Kill the useless control group
			$time_control_group.remove();
			
		},
		
		// On input change, update the hidden field value which is what will actually be
		// passed to the server
		change: function() {
			this.$hidden.val(this.$hidden_date.val()+' '+this.$hidden_time.val());
		}
		
	});
	
	// Apply the picker to each instance on the page
	$('form > div.datetime').each(function(i, el) {
		new DateTimePicker({el:el});
	});
		
});