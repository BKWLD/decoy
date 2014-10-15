// --------------------------------------------------
// Moderation view
// --------------------------------------------------
define(function (require) {
	
	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Gallery = require('decoy/views/gallery');
	
	// public view module
	var ModerateView = Gallery.extend({
		
		// Add in the height for the moderation controls
		vertical_padding: 60,
		
		// Initialize the set
		initialize: function () {
			
			// Parent constructor
			Gallery.prototype.initialize.call(this);

			// Get the path to the controller.  If this is not specified via a
			// data attribtue of "controller-route" then we attempt to infer it from
			// the current URL.
			this.controllerRoute = this.$el.data('controller-route');
			if (!this.controllerRoute) {
				this.controllerRoute = window.location.pathname;
			}
						
			// Cache selectors
			this.$pending_count = this.$('.pending-count');
			this.$approved_count = this.$('.approved-count');
			this.$denied_count = this.$('.denied-count');
			this.$pagination = $('.pagination-wrapper');
			
			// Create model collections from data in the DOM.  The URL is fetched from
			// the controller-route data attribute of the container.
			this.collection = new Backbone.Collection();
			this.collection.url = this.controllerRoute;
			_.each(this.$items, this.initItem);
			
			// Listen for collection changes and render view
			this.collection.on('change', this.render, this);
			
		},
		
		// Initialize a moderation item
		initItem: function (item) {
			
			// Vars
			var $item = $(item);
			
			// Create the model
			var model = new Backbone.Model({
				id: $item.data('model-id'),
				status: this.status($item)
			});
			this.collection.push(model);
			
			// Add the model to the DOM element
			$item.data('model', model);
		},
		
		// Register interaction events
		events: {
			'click .actions .approve': 'approve',
			'click .actions .deny': 'deny',
			'hide .item': 'hide',
			'approve .item': 'approve',
			'deny .item': 'deny'
		},
		
		// Set item to approved
		approve: function (e) {
			var model = this.model(e),
				$item = this.item(model);
				
			// Don't allow clicks if already denied
			if ($item.hasClass('approved')) return;
			
			// Update the server
			model.set('status', 'approved');
			this.save(model);
		},
		
		// Set item to denied
		deny: function (e) {
			var model = this.model(e),
				$item = this.item(model);
				
			// Don't allow clicks if already denied
			if ($item.hasClass('denied')) return;
			
			// Update the server
			model.set('status', 'denied');
			this.save(model);
		},
		
		// Get the status of an item
		status: function($item) {
			if ($item.hasClass('approved')) return 'approved';
			else if ($item.hasClass('denied')) return 'denied';
			else return 'pending';
		},
		
		// Get them model from an event
		model: function(e) {
			return $(e.target).closest('[data-model-id]').data('model');
		},
		
		// Get the jquery item given a model
		item: function(model) {
			return this.$('[data-model-id='+model.get('id')+']');
		},
		
		// Save the model
		save: function(model) {
			model.save();
		},
		
		// Increment of decrement one of the counts
		update_count: function($el, change) {
			$el.text(parseInt($el.text(), 10) + change);
		},
		
		// Swap the border color and fade out the element because it's been moved to
		// another tab.  This is triggered by an event so other views can trigger it.
		hide: function(e, status) {
			var $item = $(e.target);
			$item.parent().fadeOut(300);
			if (status && status != 'pending') $item.addClass(status+'-outro');
		},
		
		// Render view from model changes
		render: function (model) {
			
			// We only care to run this logic if there has been an actual change, not
			// after a sync event
			if (!model.hasChanged('status')) return;
			
			// Common vars
			var status = model.get('status'),
				$item = this.item(model),
				old = model.previousAttributes();
						
			// It's been moved to a new tab, so remove it
			$item.trigger('hide', [status]);
			
			// Update the counts on the page
			if (status == 'approved') {
				this.update_count(this.$approved_count, 1);
				if (old.status != 'pending') this.update_count(this.$denied_count, -1);
			} else if (status == 'denied') {
				this.update_count(this.$denied_count, 1);
				if (old.status != 'pending') this.update_count(this.$approved_count, -1);
			}
			if (old.status == 'pending') this.update_count(this.$pending_count, -1);
			
			// After any change, replace pagination with the refresh button
			if (this.$pagination && this.$pagination.length) {
				this.$pagination.remove();
				this.$pagination = null;
				this.$el.append('<div class="reload"><a href="'+window.location.href+'">Reload for more moderation options</a></div>');
			}
		}
		
	});
	
	return ModerateView;
});
