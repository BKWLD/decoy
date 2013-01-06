// --------------------------------------------------
// Used in generic autocompletes and designed to be
// extended by other views that need extended feature
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Autocomplete = require('decoy/views/autocomplete');
			
	// Public view module
	var Datalist = Autocomplete.extend({
		
		// Init
		initialize: function () {
			Autocomplete.prototype.initialize.call(this);
			
			// Cache selectors
			this.$status = this.$('button');
			this.$icon = this.$status.find('i');
			this.$hidden = this.$('input[type="hidden"]');
			
			// Add extra events
			this.events['click button'] = 'edit';
		},
		
		// Overide the match function to toggle the state of the match
		// icons and to set the hidden input field
		match: function() {
			Autocomplete.prototype.match.call(this);
			
			// Match found
			if (this.found) {
				this.$status.addClass('btn-info').prop('disabled', false).attr('href', this.route+'/'+this.id);
				this.$icon.removeClass().addClass('icon-pencil icon-white');
				this.$hidden.val(this.id);
			
			// Match cleared
			} else {
				this.$status.removeClass('btn-info').prop('disabled', true).removeAttr('href');
				this.$icon.removeClass().addClass('icon-ban-circle');
				this.$hidden.val('');
			}
		},
		
		// Visit the edit page
		edit: function(e) {
			e.preventDefault();
			location.href = this.route+'/'+this.$hidden.val();
		}
				
	});
	
	return Datalist;
});