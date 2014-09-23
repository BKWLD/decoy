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
		enterMode : CKEDITOR.ENTER_P, // <br>s are not advisable ul/ol aren't allowed in <p>s

		// Allow everything but ...
		// http://docs.ckeditor.com/#!/guide/dev_disallowed_content-section-how-to-allow-everything-except...
		allowedContent: {
			$1: {
				elements: CKEDITOR.dtd,
				attributes: true,
				styles: true,
				classes: true
			}
		},
		disallowedContent: {

			// Don't allow inline width and height on image tags.  Base on
			// http://stackoverflow.com/a/18047106/59160
			img: {styles: ['width','height'] }
		},
		
		// Don't add entities, trust the input.  This was added so that entities in the
		// language conf file for fragments doesn't return `changed()` because CKEditor
		// modified it.
		entities: false, 
		htmlEncodeOutput: false,
		
		toolbar : [
			{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
			{ name: 'links', items : [ 'Link','Unlink'] },
			{ name: 'image', items : [ 'Image' ] },
			{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
			{ name: 'clipboard', items : [ 'PasteText','PasteFromWord' ] },
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