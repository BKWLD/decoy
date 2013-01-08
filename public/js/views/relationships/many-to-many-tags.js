// --------------------------------------------------
// Many to Many tags are like Many To Manys but they
// let the user create new rows.
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		ManyToMany = require('decoy/views/relationships/many-to-many'),
		Autocomplete = require('decoy/views/autocomplete');
			
	// Public view module
	var ManyToManyTags = ManyToMany.extend({

		// Init
		initialize: function () {
			ManyToMany.prototype.initialize.call(this);
		
			// Add extra events
			this.events = _.clone(this.events);
			this.events.submit = 'create';
			
			// Track the total number of active queries
			this.requests = 0;
			
			// Make a version of enable that waits until all keyboard input has stopped
			// and the requests counter has incremented before delivery a verdict on whether
			// to enable.
			this.delayedEnable = _.debounce(this.enable, this.throttle*2);
		
		},
		
		// Track the nuber of concurrent requests
		query: function(query, process) {
			this.requests++;
			ManyToMany.prototype.query.call(this, query, process);
		},
		

		// Check if the response from the server notifies us that the tag
		// already has been attached.  Without this special notification we
		// couldn't distinguish between this state and the tag not being
		// created at lot
		response: function(data, process) {
			
			// The request has finished
			this.requests--;
			
			// Disable submitting cause the tag is attached
			if (data.exists) this.exists = true;
			else this.exists = false;
			
			// Then, normal execution
			ManyToMany.prototype.response.call(this, data, process);
			
		},

		// Overide the match function to toggle the state of the add button
		match: function() {

			// Skip the ManyToMany prototype because we don't want disable the form
			Autocomplete.prototype.match.call(this);

			// Disable the input unless the user is engaging with the autocomplete.  Then, only
			// enable it again after we wait for the server requests to finish.
			this.disable();
			if (this.found || this.hasMatches()) this.enable();
			else this.delayedEnable();

			// There is a match on the server, so show the attach UI
			if (this.found || this.hasMatches()) {
				this.$submit.html(function(i, old) { $(this).html(old.replace('New', 'Add')); });
				this.$icon = this.$submit.find('i');
				this.$icon.removeClass('icon-plus').addClass('icon-tag');
				
			// No match found, show the create UI
			} else {
				this.$submit.html(function(i, old) { $(this).html(old.replace('Add', 'New')); });
				this.$icon = this.$submit.find('i');
				this.$icon.removeClass('icon-tag').addClass('icon-plus');
			}
		},
		
		// Are there matches in the autocomplete?
		hasMatches: function() {
			return _.size(this.data) > 0;
		},
		
		// Conditionally enable the submit field
		enable: function() {
			if (this.$input.val().length > 0 && this.requests === 0 && !this.exists) {
				ManyToMany.prototype.enable.call(this);
			}
		},
		
		// Tell the server to create the new tag
		create: function(e) {
			if (e) e.preventDefault();

			// Disabling submitting if the submit is disabled
			if (this.disabled()) return;
			
			// Do an attach instead if a match is found.  Call the base match
			// because we don't want any UI logic now.
			Autocomplete.prototype.match.call(this);
			if (this.found) return this.attach();
			
			// Cache values for the model we'll be creating
			var model = {
				title: this.$input.val(),
				columns: {
					title: this.$input.val()
				}
			};
				
			// Make the request
			$.ajax(this.route+'/new', {
				data: {value: this.$input.val() },
				type:'POST',
				dataType: 'JSON'
			})
			
			// Success
			.done(_.bind(function(data) {
				
				// Add the new row to the collection so it
				// matches now
				model.id = data.id;
				this.add(model.title, model);
				
				// Now, attach the new row
				this.attach();
				
			}, this));
		}
		
	});
	
	return ManyToManyTags;
});