// --------------------------------------------------
// Used in generic autocompletes and designed to be
// extended by other views that need extended feature
// --------------------------------------------------
define(function (require) {
	
	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
			
	// public view module
	var Autocomplete = Backbone.View.extend({
		
		// Initial state and inheritable vars
		found: false,
		data: {}, // Stores the key (label) - value (row data) pairs
		id: null,
		title: null,
		selection: null,  // The whole object (from the JSON server response) that is chosen
		route: null,
		throttle: 200,
		
		// Init
		initialize: function () {
			_.bindAll(this);

			// Get the path to the controller.  If this is not specified via a
			// data attribtue of "controller-route" then we attempt to infer it from
			// the current URL.
			this.route = this.$el.data('controller-route');
			if (!this.route) this.route = window.location.pathname;
			
			// Cache selectors
			this.$input = this.$('input[type="text"]');

			// Initialize the Bootstrap typahead plugin, which generates the
			// autocomplete menu
			this.$input.typeahead({
				source: _.debounce(this.query, this.throttle) // Throttle requests
			});
				
		},
		
		// Register interaction events
		events: {
			'input input[type="text"]': 'match',
			'change input[type="text"]': 'match'
		},
		
		// Query the server for matches.  Defined as it's own method so it can be
		// overriden without having to replace the whole AJAX call.
		query: function(query, process) {
			this.execute({query:query}, process);
		},
		
		// Execute the query on the server.  In other words, do the ajax
		execute: function(request, process) {
			
			// Make the request
			$.ajax(this.route+'/autocomplete', {
				data: request,
				type:'GET',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) { this.response(data, process); }, this));
	
		},
		
		// The response from the server
		response: function(data, process) {
			
			// Loop through results and massage the results.  We need an array
			// of just labels for the typeahead.  And we need a key/val pairs
			// to get the id back from the label when saving it.
			this.data = {};
			var labels = [];
			if (_.isArray(data)) {
				_.each(data, function(row) {
					labels.push(row.title);
					this.data[row.title] = row;
				}, this);
			}
			
			// Tell typeahead about the labels
			process(labels);
			
			// Check again if there is a match in the textfield
			this.match();
			
		},
		
		// Callback from after the user inputs anything in the textfield.  Basically,
		// we want to constantly check if what they've entered is valid rather than
		// rely on bootstrap to tell us.  Cause their events to fire with every change
		// the user makes.
		match: function(e) {
			
			// Exact match selected
			if (this.data[this.$input.val()]) {
				this.found = true;
				this.title = this.$input.val();
				this.selection = this.data[this.title];
				this.id = this.selection.id;
				
			// No exact match
			} else {
				this.found = false;
				this.title = this.selection = this.id = null;
			}

		},
		
		// Add a new item to the data array.  Title is the key to the collection
		// dicitonary. Model is an object like: {id, title, columns:{}}
		add:function(title, model) {
			this.data[title] = model;
		}
		
	});
	
	return Autocomplete;
});
