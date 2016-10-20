// --------------------------------------------------
// Add file preview to selected images
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
		require('jcrop');
		require('imagesloaded');

	// Define a backbone view for each image
	var Crop = Backbone.View.extend({

		// Instance vars
		jcrop: null, // A jcrop API instance
		activeCrop: false,
		width: null,
		height: null,
		ratio: undefined,
		initted: false,
		$input: null, // The hidden field that stores the output

		// Constructor
		initialize: function() {
			_.bindAll(this);

			// Cache selectors
			this.$upload = this.$el.closest('.image-upload');
			this.$crop = this.$upload.find('.input-crop_box');
			this.$focus = this.$upload.find('.input-focal_point');
			this.$title = this.$upload.find('input.title');
			this.$cropTool = this.$upload.find('.crop.btn');
			this.$focusTool = this.$upload.find('.focal.btn');
			this.$file = this.$upload.find('[type="file"]');

			// Remove clicking on the parent a tag
			this.$el.parent('a').click(function(e) { e.preventDefault(); });

			// Cache configruation variables
			var ratio = this.$upload.data('aspect-ratio');
			this.ratio = ratio;

			// Listen for window resizing as a way to check whether the img has been
			// resized since it resizes responsively.	We listen on the leading edge
			// for the start and the tailing edge for the end
			var $window = $(window), delay = 400;
			$window.resize(_.debounce(this.destroy, delay, true));
			$window.resize(_.debounce(this.init, delay, false));

			this.$cropTool.on('click', this.beginCrop);
			this.$focusTool.on('click', this.beginFocus);

			// Start jcrop up once the images loaded
			this.$el.imagesLoaded(this.init);
		},

		// Add jcrop to the element
		init: function() {

			// Only init once and only add jcrop if the image is visible.	Otherwise,
			// it will  wait until it is first activated by the style tabs.	It needs
			// to be visible so the size can be measured correctly
			if (this.initted || !this.visible()) return;
			this.initted = true;

			// Cache the visible dimensions of the image
			this.width = this.$el.width();
			this.height = this.$el.height();

			// Check if there is any crop selection defined
			var cropVal = this.input_to_json(this.$crop);
			if (cropVal['x1'] != null) var selection = this.convert_from_perc(cropVal);

			// Init jcrop
			var self = this;
			this.$el.Jcrop({
				onSelect: this.select,
				onRelease: this.select,
				aspectRatio: this.ratio,
				setSelect: selection,
				keySupport: false // Stop the page scroll from jumping: http://cl.ly/0e1e1615262h

			// Store a reference to jcrop and call the ready function
			}, function() {
				self.jcrop = this;
				self.activeCrop = true;
			});

			// Check if focal point is set
			var focalVal = this.input_to_json(this.$focus);
			if(this.$focusTool.length != 0) {
				this.$el.next('div').append('<div class="focal-point glyphicon glyphicon-screenshot"></div>');
				this.$focalPoint = this.$el.next('div').find('.focal-point');
				this.$focalPoint.css({'left' : focalVal.x * this.$el.outerWidth(), 'top' : focalVal.y * this.$el.outerHeight()});

				if(!$.isEmptyObject(focalVal)) {
					this.$focalPoint.css('opacity', 1);
				}
			}

		},

		// Set up cropping when crop tool is clicked
		beginCrop: function() {
			if (this.activeCrop == true) return;

			// remove the set focus listener
			this.$el.next('div').unbind();

			// make the crop tool active
			this.$cropTool.addClass('active');
			this.$focusTool.removeClass('active');
			$('.jcrop-holder').css('pointer-events', 'auto');

			this.jcrop.enable();
			this.activeCrop = true;
		},

		// Switch to set focal point
		beginFocus: function() {
			if (this.activeCrop != true) return;

			this.$cropTool.removeClass('active');
			this.$focusTool.addClass('active');
			// $('.jcrop-holder').css('pointer-events', 'none');

			this.jcrop.disable();
			this.activeCrop = false;

			this.$el.next('div').on('click', this.setFocus);
		},

		setFocus: function(e) {
			var image = $(e.currentTarget);
			var offset = image.offset();

			var pointX = e.pageX - offset.left - 7;
			var pointY = e.pageY - offset.top - 7;

			var location = {
				x : pointX / image.outerWidth(),
				y : pointY / image.outerHeight()
			};

			var cropVal = this.input_to_json(this.$crop);
			var selection = this.convert_from_perc(cropVal);
			this.$focus.val(JSON.stringify(location));

			if(!$.isEmptyObject(cropVal)) {
				if(pointX < selection[0] || pointY < selection[1] || pointX > selection[2] || pointY > selection[3]) {
					console.log('Focal point must be inside crop')
					return;
				}
			}

			this.$focalPoint.css({'left' : location.x * this.$el.outerWidth(), 'top' : location.y * this.$el.outerHeight(), 'opacity' : 1 });
		},

		// Remove jcrop from the element
		destroy: function() {

			// Only valid if currently initted
			if (!this.jcrop || !this.initted) return;

			// Unset everything
			this.jcrop.destroy();
			this.jcrop = null;
			this.initted = false;

			// Jcrop will have set width/height on the style
			this.$el.css({width:'', height:''});

		},

		// Perist the user's selection
		select: function(c) {

			// Convert the coordinates from jcrop into percentages.	It may be
			// undefined if the user cleared the crop
			if (c) c = this.convert_to_perc(c);

			// Add the coordinates to the input's value
			var val = this.input_to_json(this.$crop);
			val = c;
			this.$crop.val(JSON.stringify(val));

		},

		// Clear out the hidden input values for a new image
		clear: function() {
			this.$crop.val("");
			this.$focus.val("");
			this.$title.val("");
			this.$focusTool.removeClass('active');
			this.$cropTool.addClass('active');
		},

		// Check if the this crop is visible
		visible: function() {
			return this.$el.is(":visible");
		},

		// Convert the coordinates from jcrop into percentages.	So an x value of 10
		// in a 100px wide image would become .1.	This is done because Croppa will
		// already be serving an image that is resized from it's source, so a perc
		// offset  is the only thing that will be useful.
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
		input_to_json: function(input) {
			var val = $(input).val();
			if (!val) return {};
			else return JSON.parse(val);
		}

	});

	return Crop;

});
