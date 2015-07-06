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
		this.$permission_boxes = this.$controllers.find(':checkbox');

		// Parse the permission checkboxes into a mapping by role
		this.role_boxes = this.parseBoxesForRoles(this.$permission_boxes);

		// Listen for clicks on the override checkbox
		this.$customize.on('change', this.togglePermissionsOptions);

		// Check for the role to change and clear the custom permissions
		this.$role.on('change', this.changeRole);

		// Make clicking a controller name toggler all of it's checkboxes
		this.$controllers.find('.title').on('click', this.toggleActions);

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
	 * Update the permissions when the role changes
	 */
	View.changeRole = function(e) {
		this.clearCustomPermissions();
		this.checkRole(this.$role.filter(':checked').val());
	};

	/**
	 * Clear permissions customizations
	 */
	View.clearCustomPermissions = function() {
		if (!this.$customize.is(':checked')) return;
		this.$customize.prop('checked', false).trigger('change');
	};

	/**
	 * Parse the checkboxes into buckets by roles
	 *
	 * @param jQuery boxes
	 * @return object
	 */
	View.parseBoxesForRoles = function(boxes) {
		var map = {}, roles, $el;
		boxes.each(function(i, el) {
			roles = $(el).data('roles');
			if (roles) {
				_.each(roles.split(','), function(role) {
					map[role] = ( map[role] || $([]) ).add(el);
				});	
			}
		});
		return map;
	};

	/**
	 * Check all the boxes that are defaults for the role
	 *
	 * @param string role
	 */
	View.checkRole = function(role) {
		this.$permission_boxes.prop('checked', false);
		this.role_boxes[role].prop('checked', true);
	};

	/**
	 * Toggle all of the actions for a controller when the title is clicked
	 *
	 * @param mouseevent e
	 */
	View.toggleActions = function(e) {
		var $actions = $(e.currentTarget).next().find(':checkbox');
		$actions.prop('checked', $actions.filter(':checked').length != $actions.length);
	};

	// Return view class
	return Backbone.View.extend(View);
});