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
		
		// Initial state
		found: false,
		data: {},
		id: null,
		title: null,
		
		// Init
		initialize: function () {
			_.bindAll(this);

			// Get the path to the controller.  If this is not specified via a
			// data attribtue of "controller-route" then we attempt to infer it from
			// the current URL.
			this.route = this.$el.data('route');
			if (!this.route) this.route = window.location.pathname;
			
			// Cache selectors
			this.$input = this.$('input[type="text"]');

			// Initialize the Bootstrap typahead plugin, which generates the
			// autocomplete menu
			this.data = {}; // This is where the response data will get stored
			this.$input.typeahead({
				source: _.debounce(this.query, 200) // Throttle rquests
			});
			
		},
		
		// Register interaction events
		events: {
			'input input[type="text"]': 'match', // I *think* "input" is well supported
			'change input[type="text"]': 'match'
		},
		
		// Query and parse the sever data
		query: function(query, process) {
			
			// Make the request
			$.ajax(this.route, {
				data: {query:query},
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
					labels.push(row.title);
					this.data[row.title] = row;
				}, this);
				
				// Tell typeahead about the labels
				process(labels);
				
				// Check again if there is a match in the textfield
				this.match();
				
			}, this));
		},
		
		// Callback from after the user inputs anything in the textfield.  Basically,
		// we want to constantly check if what they've entered is valid rather than
		// rely on bootstrap to tell us.  Cause their events to fire with every change
		// the user makes.
		match: function() {
			
			// Exact match selected
			if (this.data[this.$input.val()]) {
				this.found = true;
				this.title = this.$input.val();
				this.id = this.data[this.title].id;
				
			// No exact match
			} else {
				this.found = false;
				this.title = null;
				this.id = null;
			}
		}
		
	});
	
	return Autocomplete;
});