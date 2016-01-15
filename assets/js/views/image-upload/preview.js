// --------------------------------------------------
// Preview the selected image to upload
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Define a backbone view for each image
	var Preview = Backbone.View.extend({

		// Constructor
		initialize: function() {
			_.bindAll(this);

			this.$file = this.$el.find('.file');
			this.$imagePreview = this.$el.find('.image-holder .img-thumbnail');

			console.log(this.$imagePreview);

			// Listener
			this.$file.on('change', this.onImageChange);

		},

		onImageChange: function(input) {

			if (this.$file[0].files && this.$file[0].files[0]) {
				var reader = new FileReader();

				reader.onload = function(e) {
					$('.image-holder .img-thumbnail').attr('src', e.target.result);
				}

				reader.readAsDataURL(this.$file[0].files[0]);
			}
		}

	});

	return Preview;

});
