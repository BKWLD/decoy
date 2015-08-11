/**
 * Notification panel for alert message. 
 * 
 * Triggered upon CRUD / Validation of models 
 * and during AJAX Error events
 */
define(function(require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');

	// Init view
	var View = {};
	
	// Constructor
	View.initialize = function() {
		_.bindAll(this);
		
		// Alert type settings
		this.alertGlyphs = {
			danger: 'warning-sign',
			success: 'ok-sign',
			info: 'info-sign',
			warning: 'question-sign'
		}

		// Cached child selections
		this.$alertGlyph = this.$el.find('p span.glyphicon');
		this.$message = this.$el.find('span.message');
		this.$close = this.$el.find('.close');

		// Hook into global AJAX error events
		$(document).ajaxError( this.onError );

		// Handler for close event
		this.$close.on('click', this.close);

		// Open up the pane if a notification exists
		if(this.$el.data('display')) {
			this.setAlertType( this.$el.data('alert-type') )
			this.open();
		}
	};

	/**
	 * Sets the notification pane text, alert type and may or may not open the pane
	 * 
	 * @param String message       	HTML-friendly text to set in the notification pane
	 * @param String alert_type     Type of bootstrap alert (success, danger, warning, info)
	 * @param bool should_reopen 	After updating, should the pane open? Defaults to true
	 */
	View.set = function( message, alert_type, should_reopen ) {
		// default reopen to true
		if(typeof should_reopen == 'undefined') should_reopen = true;

		// update the pane classes based on the type of alert
		this.setAlertType(alert_type);

		// update the message text
		this.$message.empty()
		this.$message.html(message);

		// repen the pane if requested
		if(should_reopen) this.open();
	};

	/**
	 * Updates the notification pane alert class and glyphicon 
	 * 
	 * @param String alert_type     Type of bootstrap alert (success, danger, warning, info)
	 */
	View.setAlertType = function( alert_type ) {
		// remove glyphicon classes and add current type
		this.$alertGlyph.removeClass (function (index, css) {
			return (css.match (/\bglyphicon-\S+/g) || []).join(' ');
		}).addClass('glyphicon-'+this.alertGlyphs[alert_type]);

		// update the alert stylings
		this.$el.removeClass (function (index, css) {
			return (css.match (/\balert-\S+/g) || []).join(' ');
		}).addClass('alert-'+alert_type);
	};
	
	/**
	 * Opens the notification pane
	 */
	View.open = function() {
		this.$el.addClass('show');
	};

	/**
	 * Closes the notification pane
	 */
	View.close = function() {
		this.$el.removeClass('show');
	};

	/**
	 * Global AJAX hook on errors events. Will open the notification pane 
	 * with a server failure message and any response text if it exists
	 * 
	 * @param  Object event    AJAX event object
	 * @param  Object response Server reponse object
	 */
	View.onError = function(event, response) {
		var message = "<strong>Server request failed</strong>";
		if( response !== null && typeof response != 'undefined') {
			if( response.responseJSON !== null && typeof response.responseJSON != 'undefined')
				message += '<pre>' + JSON.stringify(response.responseJSON) + '</pre>';
		}
		this.set( message, 'danger', true );
	};

	// Return the view
	return Backbone.View.extend(View);
});