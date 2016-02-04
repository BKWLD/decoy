// --------------------------------------------------
// Image Fullscreen - Switch the uploaded image to a
// fullscreen view
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Create a backbone view to handle the tabs that switch between crops
	var Fullscreen = Backbone.View.extend({

		// Instance vars
		$shim: null,
		
		// Constructor
		initialize: function() {
			
			// Measure the bottom margin for use in the shim
			this.margin_bottom = this.$el.find('.control-group').css('margin-bottom');
			
		},

		// Events
		// events: {
		// 	'click .fullscreen-toggle': 'toggle'
		// },
		
		// On click
		toggle: function(e) {
			e.preventDefault();
			
			// Toggle the fullscreen state
			this.$el.toggleClass('fullscreen');
						
			// Insert an empty div in it's place to prop the container
			if (!this.$shim) {
				_.defer(_.bind(function() {
					this.$shim = $('<span>').css({
						display: 'block',
						height: (this.$el.height()+'px'),
						'margin-bottom': this.margin_bottom
					});
					this.$el.after(this.$shim);
				}, this));
			} else {
				this.$shim.remove();
				this.$shim = null;
			}
			
			// Notify other modules about the change
			if (this.$el.hasClass('fullscreen')) this.$el.trigger('expand');
			else this.$el.trigger('contract');
			
		}
		
	});
	
	return Fullscreen;
	
});