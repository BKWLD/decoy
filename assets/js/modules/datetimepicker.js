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
			var $date_group = this.$('.date-field')
				, $time = this.$('.time-field .time:text').parent()
				, $time_group = $time.closest('.time-field')
				, $date_help = $date_group.find('.help-block')
				, $time_help = $time_group.find('.help-block')
			;

			// Move it
			$date_group.append($time);
			$date_group.find('.input-group').wrapAll('<div class="input-groups" >');

			// Add spacing
			$time.css('margin-left', 5);

			// Move over help text
			if ($time_help.length && $date_help.length) $date_help.append($time_help.text());
			else if ($time_help.length) $time.parent().after($time_help); // Put next to .input-groups

			// Kill the useless control group
			$time_group.remove();

		},

		// On input change, update the hidden field value which is what will actually be
		// passed to the server
		change: function() {
			this.$hidden.val(this.$hidden_date.val()+' '+this.$hidden_time.val());
		}

	});

	// Apply the picker to each instance on the page
	$('.datetime-field').each(function(i, el) {
		new DateTimePicker({el:el});
	});

});
