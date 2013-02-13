// --------------------------------------------------
// Crop - Adds jcrop where appropriate
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
	require('decoy/plugins/jquery.Jcrop');
	
	// Define a backbone view for each image
	var Crop = Backbone.View.extend({
		
		// Instance vars
		jcrop: null, // A jcrop API instance
		width: null,
		height: null,
		style: null, // The style of the crop
		ratio: undefined,
		initted: false,
		$input: null, // The hidden field that stores the output
		
		// Constructor
		initialize: function() {
			_.bindAll(this);
			
			// Cache selectors
			this.$input = this.$el.closest('.image-upload').find(':hidden[name*="_crops"]');
			
			// Remove clicking on the parent a tag
			this.$el.parent('a').click(function(e) { e.preventDefault(); });
			
			// Cache configruation variables
			this.style = this.$el.data('style');
			var ratio = this.$el.data('ratio');
			if (ratio) {
				ratio = ratio.split(':');
				this.ratio = ratio[0]/ratio[1];
			}
			
			// Listen for window resizing as a way to check whether the img has been resized
			// since it resizes responsively.  We listen on the leading edge for the start
			// and the tailing edge for the end
			var $window = $(window), delay = 400;
			$window.resize(_.debounce(this.destroy, delay, true));
			$window.resize(_.debounce(this.init, delay, false));
			
			// Listen for fullscreen events
			var $fullscreen = this.$el.closest('[data-js-view="image-fullscreen"]');
			$fullscreen.on('expand contract', _.debounce(this.destroy, delay, true));
			$fullscreen.on('expand contract', _.debounce(this.init, delay, true));
						
			// Start jcrop up
			this.init();
			
			//debug
			this.$el[0].init = this.init;
			this.$el[0].destroy = this.destroy;
			
		},
		
		// Events
		events: {
			'active': 'activate' // This image has now been activated
		},
		
		// Activated, meaning a tab has been clicked to reveal it.  Note: the first
		// image is assumed to be activated on load.
		activate: function() {
			if (!this.initted) this.init();
		},
		
		// Add jcrop to the element
		init: function() {
			
			// Only init once and only add jcrop if the image is visible.  Otherwise, it will
			// wait until it is first activated by the style tabs.  It needs to be visible
			// so the size can be measured correctly
			if (this.initted || !this.visible()) return;
			this.initted = true;
			
			// Cache the visible dimensions of the image
			this.width = this.$el.width();
			this.height = this.$el.height();
			
			// Check if there is any selection defined
			var val = this.input_to_json();
			var selection = val[this.style] ? this.convert_from_perc(val[this.style]) : undefined;

			// Init jcrop
			var self = this;
			this.$el.Jcrop({
				onSelect: this.select,
				onRelease: this.select,
				aspectRatio: this.ratio,
				setSelect: selection
				
			// Store a reference to jcrop and call the ready function
			}, function() {
				self.jcrop = this;
				
				// Put all of the jcrop instances in a parent to give them the polariod effect
				self.$el.siblings('.jcrop-holder').wrap('<div class="img-polaroid" style="display: inline-block;"/>');
			});
			
		},
		
		// Remove jcrop from the element
		destroy: function() {
			
			// Only valid if currently initted
			if (!this.jcrop || !this.initted) return;
			
			// Unset everything
			this.jcrop.destroy();
			this.$el.siblings('.img-polaroid').remove();
			this.jcrop = null;
			this.initted = false;
			
			// Jcrop will have set width/height on the style
			this.$el.css({width:'', height:''});
			
		},
		
		// Perist the user's selection
		select: function(c) {
			
			// Convert the coordinates from jcrop into percentages.  It may be undefined if the user
			// cleared the crop
			if (c) c = this.convert_to_perc(c);
			
			// Add the coordinates to the input's value
			var val = this.input_to_json();
			val[this.style] = c;
			this.$input.val(JSON.stringify(val));
			
		},
		
		// Check if the this crop is visible
		visible: function() {
			return this.$el.is(":visible");
		},
		
		// Convert the coordinates from jcrop into percentages.  So an x value of 10
		// in a 100px wide image would become .1.  This is done because Croppa will already
		// be serving an image that is resized from it's source, so a perc offset
		// is the only thing that will be useful.
		convert_to_perc: function(c) {
			return {
				x1 : c.x / this.width,
				x2 : c.x2 / this.width,
				y1 : c.y / this.height,
				y2 : c.y2 / this.height
			};
		},
		
		// Convert from perc back to pixels of the current image size
		convert_from_perc: function(c) {
			return [
				c.x1 * this.width,
				c.y1 * this.height,
				c.x2 * this.width,
				c.y2 * this.height
			];
		},
		
		// Get JSON from the $input
		input_to_json: function() {
			var val = this.$input.val();
			if (!val) return {};
			else return JSON.parse(val);
		}
		
	});

	return Crop;
	
});