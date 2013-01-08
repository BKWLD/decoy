// --------------------------------------------------
// Many to Many relationship creator view
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Autocomplete = require('decoy/views/autocomplete');
			
	// Public view module
	var ManyToMany = Autocomplete.extend({
		
		// Init
		initialize: function () {
			Autocomplete.prototype.initialize.call(this);

			// There must be a parent-id defined for the saving to work
			this.parent_id = this.$el.data('parent-id');
			
			// Cache selectors
			this.$submit = this.$('button[type="submit"]');
			this.$icon = this.$submit.find('i');
			
			// Add extra events
			this.events = _.clone(this.events);
			this.events.submit = 'attach';
		},
		
		// Define a new query method so we can pass the parent_id
		query: function(query, process) {
			this.execute({query:query, parent_id: this.parent_id}, process);
		},
		
		// Overide the match function to toggle the state of the add button
		match: function() {
			var changed = Autocomplete.prototype.match.call(this);
			if (this.found) this.enable();
			else this.disable();
		},
		
		// Enable the form
		enable: function() {
			if (this.$submit.hasClass('btn-info')) return;
			this.$submit.addClass('btn-info').prop('disabled', false);
			this.$icon.addClass('icon-white');
		},
		
		// Disable the form
		disable: function() {
			if (!this.$submit.hasClass('btn-info')) return;
			this.$submit.removeClass('btn-info').prop('disabled', true);
			this.$icon.removeClass('icon-white');
		},
		
		// Determine if the form should be disabled
		disabled: function() {
			return this.$submit.prop('disabled');
		},
		
		// Tell the server to attach the selected item
		attach: function (e) {
			if (e) e.preventDefault();
			
			// Don't execute it no match is found.  Call the base match
			// because we don't want any UI logic now.
			Autocomplete.prototype.match.call(this);
			if (!this.found) return;
				
			// Make the request
			$.ajax(this.route+'/attach/'+this.id, {
				data: {parent_id: this.parent_id},
				type:'POST',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) {
				
				// Tell the editable list to add the new entry
				var payload = { id: this.id, pivot_id: data.pivot_id, label: this.selection.columns.title };
				this.$el.trigger('insert', payload);
				
				// Clear the input to add another
				this.$input.val('')
				.focus()
				.prop('placeholder', 'Add another');
				this.match();
				
			}, this));
		}
		
	});
	
	return ManyToMany;
});