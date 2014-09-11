// --------------------------------------------------
// An alternative to the standard-list for displaying
// rows as a fixed height grid.
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	require('imagesloaded');
	
	// Public view module
	var Gallery = Backbone.View.extend({
		
		// Settings
		vertical_padding: 20, // Padding isn't taken into account when measuring the height, so this compensates for that
		rollover_height: 28,
		
		// Init
		initialize: function () {
			_.bindAll(this);
			
			// Cache selectors
			this.$items = this.$('.listing .item');
			
			// Throttle window resize events.  Resizing the window can affect
			// the natural height of elements, so this needs to trigger us to
			// examine the heights.
			var throttled_fix_heights = _.throttle(this.fix_heights, 100);
			$(window).resize(throttled_fix_heights);
			
			// Listen for images loading, which may incresae the heights
			var imgs = this.$items.find('img').imagesLoaded(throttled_fix_heights);
			
			// Initially set heights
			throttled_fix_heights();
			
		},
		
		// Find the tallest element in the listing OR recalculate the current
		// tallest item
		calculate: function() {
						
			// Loop through the listing items and find the tallest.  Unset any
			// manually set heights
			var $item, tallest = 0;
			_.each(this.$items, function(item) {
				$item = $(item);
				tallest = Math.max(tallest, this.measure($item));
			}, this);
			
			// Return the tallest
			return tallest;
			
		},
		
		// Measure the natural height of an element.  Note: this doesn't
		// include the affect of padding or margins
		measure: function($el) {
			var height = $el.css('height', '').height();
			if ($el.hasClass('text-layout')) height += this.rollover_height;
			return height;
		},
		
		// Fix the heights of all the items
		fix_heights: function() {
			
			// Calculate the tallest height
			var tallest = this.calculate();
			
			// Set this height on all elements
			this.$items.css('height', (tallest + this.vertical_padding) + 'px');
			
		}
		
	});
	return Gallery;
});