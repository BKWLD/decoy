// --------------------------------------------------
// Many to Many relationship creator view
// --------------------------------------------------
define(function (require) {
	
	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	
	// private static vars
	var app,
		dataId = 'data-model-id';
			
	// public view module
	var ManyToManyView = Backbone.View.extend({
		
		initialize: function () {
			_.bindAll(this);
			app = this.options.app;

			// Get the path to the controller.  If this is not specified via a
			// data attribtue of "controller-route" then we attempt to infer it from
			// the current URL.
			this.controllerRoute = this.$el.data('controller-route');
			if (!this.controllerRoute) {
				this.controllerRoute = window.location.pathname;
			}
			
			// There must be a parent-id defined for the saving to work
			this.parent_id = this.$el.data('parent-id');
			
			// Cache selectors
			this.$input = this.$('.many-to-many-form input');
			this.$submit = this.$('.many-to-many-form button');

			// Initialize the Bootstrap typahead plugin, which generates the
			// autocomplete menu
			this.data = {}; // This is where the response data will get stored
			this.$input.typeahead({
				source: _.debounce(this.query, 200) // Throttle rquests
			});
			
		},
		
		// Register interaction events
		events: {
			'submit .many-to-many-form': 'add',
			'input .many-to-many-form input': 'match', // I *think* "input" is well supported
			'change .many-to-many-form input': 'match'
		},
		
		// Query and parse the sever data
		query: function(query, process) {
			
			// Make the request
			$.ajax(this.controllerRoute, {
				data: {query:query, parent_id: this.parent_id},
				type:'GET',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) {
							
				// Loop through results and massage the results.  We need an array
				// of just labels for the typeahead.  And we need a key/val pairs
				// to get the id back from the label when saving it.
				this.data = {};
				var labels = [];
				_.each(data, function(row) {
					labels.push(row.label);
					this.data[row.label] = row;
				}, this);
				
				// Tell typeahead about the labels
				process(labels);
				
				// Check again if there is a match in the textfield
				this.match();
				
			}, this));
		},
		
		// Callback from after the user inputs anything in the textfield.  Basically,
		// we wantt to constantly check if what they've entered is valid rather than
		// rely on bootstrap to tell us.  Cause their events to fire with every change
		// the user might make
		match: function() {
			if (this.data[this.$input.val()]) {
				this.$submit.removeClass('disabled');
				this.$submit.prop('disabled', false);
			} else {
				this.$submit.addClass('disabled');
				this.$submit.prop('disabled', true);
			}
		},
		
		// Set item to approved
		add: function (e) {
			e.preventDefault();
			
			// Get the id of the row we're adding
			var label = this.$input.val(),
				row = this.data[label],
				id = row.id;
				
			// Make the request
			$.ajax(this.controllerRoute+'/'+id, {
				data: {parent_id: this.parent_id},
				type:'PUT',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) {
				
				// Clear the input to add another
				this.$input.val('')
				.focus()
				.prop('placeholder', 'Add another');
				this.match();
				
				// Tell the editable list to add the new entry
				var payload = { id: id, pivot_id: data.pivot_id, label: label };
				if (row.image) payload.label = '<img src="'+row.image+'"/> '+payload.label;
				this.$el.trigger('insert', payload);
				
			}, this));
		}
		
	});
	
	return ManyToManyView;
});