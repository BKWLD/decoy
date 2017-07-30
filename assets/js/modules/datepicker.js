// --------------------------------------------------
// Datepicker - Setup datepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
        __ = require('../localize/translated'),
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
				orientation: 'top left',
                format: __('date.format')

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
			var parts = this.$input.val().split(__('date.separator'));
			if (!parts) return;

			// Identify date parts based on date format
			var formatParts = __('date.format').toLowerCase().split(__('date.separator'));
			var day = formatParts.indexOf('d') >= 0 ? parts[formatParts.indexOf('d')] : parts[formatParts.indexOf('dd')];
			var month = formatParts.indexOf('m') >= 0 ? parts[formatParts.indexOf('m')] : parts[formatParts.indexOf('mm')];
			var year = formatParts.indexOf('yy') >= 0 ? parts[formatParts.indexOf('yy')] : parts[formatParts.indexOf('yyyy')];

			// Pad the numbers
			if (month.length == 1) month = '0'+month;
			if (day.length == 1) day = '0'+day;
			if (year.length == 1) year = '200'+year;
			if (year.length == 2) {
				if (year - 10 > String(new Date().getFullYear()).substr(2)) year = '19'+year;
				else year = '20'+year;
			}

			// Update hidden field
			this.$hidden.val(year+'-'+month+'-'+day);
			this.$hidden.trigger('change');
		},

	});

	// Apply the date picker to each instance on the
	$('.input-group:has(.date)').each(function(i, el) {
		new DatePicker({el:el});
	});

});
