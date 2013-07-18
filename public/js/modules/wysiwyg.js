// Setup CKEditor
define(function(require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore');
	var CKEDITOR = window.CKEDITOR; // CK isn't currently loaded via requirejs
	
	// Default config
	var config = {
		customConfig: '', // Don't load external config js file
		enterMode : CKEDITOR.ENTER_BR,
		toolbar :
		[
			{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
			{ name: 'links', items : [ 'Link','Unlink'] },
			{ name: 'image', items : [ 'Image' ] },
			{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
			{ name: 'source', items : [ 'Source' ] }
		]
	};
	
	// Return the config
	function getConfig() { return config; }

	// Merge new config into default config
	function mergeConfig(_config) { config = _.extend(config, _config); }
	
	// Replace default config with passed one
	function replaceConfig(_config) { config = _config; }
	
	// Apply CKeditor to the selector
	function replace(selector) {
		$(selector).each(function() {
		
			// Make the WYSIWYGs the same dimension as the textareas by wrapping it in the same span
			var span = $(this).closest('.span9').length ? 'span9' : 'span6';
			$(this).wrap('<div class="'+span+'" style="margin-left:0">');
		
			// Init CK editor	
			CKEDITOR.replace(this, config);
		});
	}
	
	// Expose public interface
	return {
		config: {
			get: getConfig,
			merge: mergeConfig,
			replace: replaceConfig
		},
		replace: replace
	};
	
});