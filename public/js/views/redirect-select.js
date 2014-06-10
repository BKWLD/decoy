define(function (require) {
  
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
	;
	
	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);
		this.$el.on('change', function() {
			document.location.href = $(this).find('option:selected').val();
		})
	};
	
	// Return view class
	return Backbone.View.extend(View);
});