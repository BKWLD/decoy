// --------------------------------------------------
// Moderation view
// --------------------------------------------------
define(function (require) {

	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Gallery = require('./gallery');

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
			this.$tabs = this.$('.nav-tabs li');
			this.$pagination = $('.pagination-wrapper');

			// Create model collections from data in the DOM.  The URL is fetched from
			// the controller-route data attribute of the container.
			this.collection = new Backbone.Collection();
			this.collection.url = this.controllerRoute;
			_.each(this.$items, this.initItem);

			// Listen for collection changes and render view
			this.collection.on('change', this.render, this);

			// Listen for clicks on action buttons
			this.$('.actions .btn').on('click', this.moderate);

		},

		// Initialize a moderation item
		initItem: function (item) {

			// Vars
			var $item = $(item);

			// Create the model
			var model = new Backbone.Model({
				id: $item.data('model-id'),
				status: $item.data('status'),
			});
			this.collection.push(model);

			// Add the model to the DOM element
			$item.data('model', model);
		},

		// Set that new status on the model
		moderate: function(e) {
			var $target = $(e.currentTarget),
				status = $target.data('status'),
				model = $target.closest('[data-model-id]').data('model');
			model.save({status:status});
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
		updateCount: function($el, change) {
			$el.text(parseInt($el.text(), 10) + change);
		},

		// Swap the border color and fade out the element because it's been moved to
		// another tab.  This is triggered by an event so other views can trigger it.
		hide: function($item) {
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
			this.hide($item);

			// Update the statuses
			this.updateCount(this.$tabs.filter('.'+status).find('.count'), 1);
			this.updateCount(this.$tabs.filter('.'+old.status).find('.count'), -1);

			// After any change, replace pagination with the refresh button
			if (this.$pagination && this.$pagination.length) {
				this.$pagination.empty();
				this.$pagination.append('<a class="reload btn btn-default" href="'+window.location.href+'">Reload for more moderation options</a>');
				this.$pagination = null;
			}
		}

	});

	return ModerateView;
});
