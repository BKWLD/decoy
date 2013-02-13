// --------------------------------------------------
// Crop Styles - The UI to switch between corp styles
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Create a backbone view to handle the tabs that switch between crops
	var CropStyles = Backbone.View.extend({

		// Cache selecctors
		initialize: function() {
			this.$tabs = this.$el.children();
		},

		// Events
		events: {
			'click > *': 'clicked' // A click on a tab
		},
		
		// On click
		clicked: function(e) {
			var $tab = $(e.target);
			
			// Don't do anything if clicking on the currently active tab
			if ($tab.hasClass('active')) return;
			
			// Figure out the offset of the item that was clicked
			var i = $tab.index();
			
			// Make that image visible
			var imgs = this.$el.closest('.crops').find('.imgs > *');
			imgs.hide();
			imgs.eq(i).show();
			imgs.eq(i).find('img').trigger('active'); // The crop view happens on the img tag
			
			// Switch visiblity of the tabs
			this.$tabs.removeClass('active');
			this.$tabs.eq(i).addClass('active');
			
		}
		
	});
	
	return CropStyles;
	
});