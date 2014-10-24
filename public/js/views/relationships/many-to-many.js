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

			// There must be a parent_id and parent_controller defined for the saving to work
			this.parent_id = this.$el.data('parent-id');
			this.parent_controller = this.$el.data('parent-controller');

			// Call init after the parent info is read
			Autocomplete.prototype.initialize.apply(this, arguments);
			
		},
		
		// Add the parent stuff to query
		url: function() {
			return Autocomplete.prototype.url.apply(this)+'&'+$.param({
				parent_id: this.parent_id,
				parent_controller: this.parent_controller
			});
		},
		
		// Overide the match function to attach on selection
		match: function() {
			Autocomplete.prototype.match.apply(this, arguments);
			if (this.found) this.attach();
		},
		
		// Tell the server to attach the selected item
		attach: function (e) {
			if (e) e.preventDefault();
			
			// Don't execute it no match is found.  Call the base match
			// because we don't want any UI logic now.
			Autocomplete.prototype.match.apply(this, arguments);
			if (!this.found) return;
			
			// Switch input to communicate the adding phase
			this.$input
				.prop('disabled', true)
				.typeahead('val', '')
				.prop('placeholder', 'Adding...');

			// Make the request
			$.ajax(this.route+'/'+this.id+'/attach', {
				data: {
					parent_id: this.parent_id,
					parent_controller: this.parent_controller},
				type:'POST',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) {
				
				// Tell the editable list to add the new entry
				var payload = { id: this.id, parent_id: this.parent_id, columns: this.selection.columns };
				this.$el.trigger('insert', payload);
				
				// Clear the input to add another.  Must use typeahead to clear or it will reset
				// the value after you de-focus.
				this.$input
					.prop('disabled', false)
					.focus()
					.prop('placeholder', 'Search again');
				this.match();
				
			}, this));
		}
		
	});
	
	return ManyToMany;
});