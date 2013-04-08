// --------------------------------------------------
// Notify - Generate GROWL style notifications
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery');
	require('decoy/plugins/bootstrap-notify'); // This is used to display them
	
	// Show an error message
	function error(msg) {
		$(function() { // Make sure DOM is loaded
			$('.top-right').notify({
				message: { text: msg },
				fadeOut: { enabled: true, delay: 3000 },
				type: 'danger'
			}).show();
		});
	}
	
	// Expose public methods.  Wrap in jQuery so they wait till
	// the DOM is loaded
	return {
		error: error
	};
});