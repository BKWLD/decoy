/**
 * Return the correct wysiwyg adapter by testing for which is loaded
 */
 define(function (require) {
	
	// CKEditor
	if (window.CKEDITOR) return require('decoy/wysiwyg/ckeditor');

	// Redactor is default
	else return require('decoy/wysiwyg/redactor');

});