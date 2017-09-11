/**
 * Apply Redactor to all wysiwyg fields
 *
 * @return {object} An hash of public methods
 */
define(function(require) {

	// Dependencies
	require('redactor/redactor.js');
	require('redactor/redactor.css');
	var $ = require('jquery')
		, _ = require('lodash')
	;

	// Default config
	var allow_uploads = false,
		config = {

		// Toolbar options
		buttons: ['bold', 'italic', 'link', 'image', 'file', 'horizontalrule', 'orderedlist', 'unorderedlist', 'html'],

		// Don't fix the toolbar when scrolled off page
		toolbarFixed: false,

		// Don't let the editor grow bigger than the browser height
		maxHeight: $(window).height() * .7

	}

	/**
	 * Enable file upload fields
	 *
	 * @return {void}
	 */
	function allowUploads() {
		allow_uploads = true;
	}

	/**
	 * Return the config
	 *
	 * @return {object}
	 */
	function getConfig() {
		return config;
	}

	/**
	 * Merge new config into default config
	 *
	 * @param {object} config
	 * @return {object}
	 */
	function mergeConfig(_config) {
		return config = _.extend(config, _config);
	}

	/**
	 * Merge new config into default config
	 *
	 * @param {object} config
	 * @return {object}
	 */
	function replaceConfig(_config) {
		return config = _config;
	}

	/**
	 * Initialize wysiwyg editors
	 *
	 * @param {string} selector jquery style selector string
	 * @return {jQuery}
	 */
	function init(selector) {

		// Enable file uploads
		if (allow_uploads) {
			config = _.defaults(config, {
				uploadImageFields: { _token: $('meta[name="csrf-token"]').attr('content') },
				uploadFileFields: { _token: $('meta[name="csrf-token"]').attr('content') },
				imageUpload: '/admin/redactor',
				fileUpload: '/admin/redactor'
			});
		}

		// Loop through items and init redactor
		return $(selector).each(function() {
			var $el = $(this);
			$el.redactor(_.defaults(config, {

				// Set it's min height to the inital height of the textarea
				minHeight: $el.outerHeight() - 35 // The height of the toolbar with borders

			}));
		});
	}

	// Expose public interface
	return {
		config: {
			get: getConfig,
			merge: mergeConfig,
			replace: replaceConfig,
			allowUploads: allowUploads
		},
		init: init
	};

});
