/**
 * Return the correct wysiwyg adapter by testing for which is loaded
 */
 define(function (require) {

	// Redactor is default (and, currently, only provider)
	return require('./redactor');

});
