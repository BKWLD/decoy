// --------------------------------------------------
// Many to Many relationship creator view
// --------------------------------------------------
define(function (require) {
	
	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Autocomplete = require('decoy/views/autocomplete');
			
	// public view module
	var ManyToManyView = Autocomplete.extend({
		
		initialize: function () {
			Autocomplete.prototype.initialize.call(this);

			// There must be a parent-id defined for the saving to work
			this.parent_id = this.$el.data('parent-id');
			
			// Cache selectors
			this.$submit = this.$('button[type="submit"]');
			this.$icon = this.$submit.find('i');
			
			// Add extra events
			this.events['submit form'] = 'attach';
		},
		
		// Define a new query method so we can pass the parent_id
		query: function(query, process) {
			this.execute({query:query, parent_id: this.parent_id}, process);
		},
		
		// Overide the match function to toggle the state of the add button
		match: function() {
			Autocomplete.prototype.match.call(this);
			
			// Match found
			if (this.found) {
				this.$submit.addClass('btn-info').prop('disabled', false);
				this.$icon.addClass('icon-white');
				
			// Match cleared
			} else {
				this.$submit.removeClass('btn-info').prop('disabled', true);
				this.$icon.removeClass('icon-white');
			}
		},
		
		// Tell the server to attach the selected item
		attach: function (e) {
			e.preventDefault();
				
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
	
	return ManyToManyView;
});