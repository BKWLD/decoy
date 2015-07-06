define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
	;
	
	/**
	 * Setup view 
	 */
	var View = {};
	View.initialize = function() {
		_.bindAll(this);

		// Cache
		this.$role = $('[name="role"]');
		this.$customize = this.$('[name="_custom_permissions"]');
		this.$permissions = this.$('.permissions-list');
		this.$permissions_inner = this.$('.permissions-list-inner');
		this.$controllers = this.$permissions.find('.controller');		

		// Listen for clicks on the override checkbox
		this.$customize.on('change', this.togglePermissionsOptions);

		// Check for the role to change and clear the custom permissions
		this.$role.on('change', this.clearCustomPermissions);

	};

	/**
	 * Toggle the permissions options
	 */
	View.togglePermissionsOptions = function() {

		// Inspect the clicked box
		var show = this.$customize.is(':checked');

		// Manually set the height whenever it's moving and then clear it when
		// animation is done.  The animation is defined in CSS.
		this.$permissions.height(this.$permissions_inner.outerHeight());
		_.delay(_.bind(function() { this.$permissions.height(''); }, this), 300);

		// Toggle the open state of the permissions
		_.defer(_.bind(function() { this.$el.toggleClass('closed', !show); }, this));
	};
	
	/**
	 * Clear permissions customizations
	 */
	View.clearCustomPermissions = function() {
		this.$customize.attr('checked', false).trigger('change');
	};

	// Return view class
	return Backbone.View.extend(View);
});