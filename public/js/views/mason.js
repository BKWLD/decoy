// --------------------------------------------------
// Apply Masonry to a container
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		Masonry = require('decoy/plugins/masonry'),
		CKEditor = window.CKEDITOR;
	
	// Create view
	var View = {};
	
	// Constructor
	View.initialize = function() {
		
		// THIS IS DISABLED FOR NOW.  MASONRY DOES NOT PLAY FRIENDLY WITH REQUIRE.JS
		// http://masonry.desandro.com/appendix.html#requirejs
		
		// Init Masonry
		this.masonry = new Masonry(this.el, {
			transitionDuration: 0, // Looks cheap when things go ontop of each other
		});

		// If the Masonry container is a Boostrap tab, listen for it's toggle button
		// to be clicked.  This assumes that the two are linked via the href="#id" style
		if (this.$el.hasClass('tab-pane')) {
			$('a[data-toggle="tab"][href="#'+this.$el.attr('id')+'"]').on('shown', _.bind(function(e) {
				this.masonry.layout();
			},this));
		}

	};
	
	// Return it
	return Backbone.View.extend(View);
	
});