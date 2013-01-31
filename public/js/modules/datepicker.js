// --------------------------------------------------
// Datepicker - Setup datepickers and format new
// selections for MySQL
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery');
	require('decoy/plugins/bootstrap-datepicker'); // http://cl.ly/1N401g3z3M0E
	
	// Enable date picker.  The container needs to have a date class
	var $input_append = $('.input-append:has(.date)');
	$input_append.addClass('date').datepicker();
	
	// Listen for changes to the datepicker and update the related hidden field
	// with the date in the mysql format
	$input_append.find('input').on('change', function() {
		
		// Make sure the date is valid
		var parts = $(this).val().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
		if (!parts) return;
		
		// Update the hidden field
		var id = $(this).attr('name');
		$(':hidden#'+id).val(parts[3]+'-'+parts[1]+'-'+parts[2]);
	});
	
});