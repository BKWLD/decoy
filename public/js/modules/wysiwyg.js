// Setup CKEditor
define(function(require) {
	
	// Dependencies
	var $ = require('jquery')
		, _ = require('underscore')
		, CKEDITOR = window.CKEDITOR // CK isn't currently loaded via requirejs
		, CKFINDER = window.CKFinder // CK isn't currently loaded via requirejs
	;
	
	// Default config
	var config = {
		customConfig: '', // Don't load external config js file
		enterMode : CKEDITOR.ENTER_BR,
		allowedContent: true, // Allow all HTML tags
		
		// Don't add entities, trust the input.  This was added so that entities in the
		// language conf file for fragments doesn't return `changed()` because CKEditor
		// modified it.
		entities: false, 
		htmlEncodeOutput: false,
		
		toolbar : [
			{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
			{ name: 'links', items : [ 'Link','Unlink'] },
			{ name: 'image', items : [ 'Image' ] },
			{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
			{ name: 'source', items : [ 'Source' ] }
		]
	};
	
	// Enable CKFinder
	var allow_uploads = false;
	function allowUploads() {
		allow_uploads = true;
	}
	
	// Return the config
	function getConfig() { return config; }

	// Merge new config into default config
	function mergeConfig(_config) { config = _.extend(config, _config); }
	
	// Replace default config with passed one
	function replaceConfig(_config) { config = _config; }
	
	// Apply CKeditor to the selector
	function replace(selector) {
		$(selector).each(function() {
			var $el = $(this);
		
			// Make the WYSIWYGs the same dimension as the textareas by wrapping it in the same span.
			// The float setting makes block-help be on it's own line (spans get a float otherwise) 
			var span = $el.closest('.span9').length ? 'span9' : 'span6';
			$el.wrap('<div class="'+span+' wysiwyg-wrap">');
		
			// Init CK Editor	and CK Finder
			var editor = CKEDITOR.replace(this, config);
			if (allow_uploads) CKFINDER.setupCKEditor(editor, '/packages/bkwld/decoy/ckfinder/');
			
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
		replace: replace
	};
	
});