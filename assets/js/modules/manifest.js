// --------------------------------------------------
// Manifest - hash map of JS modules
// --------------------------------------------------
define(function (require) {
	
	// list each module by ID and require path
	// (note: they must be required here for the build step)
	return {
		'standard-list': require('decoy/views/standard-list'),
		'moderation': require('decoy/views/moderation'),
		'autocomplete': require('decoy/views/autocomplete'),
		'belongs-to': require('decoy/views/relationships/belongs-to'),
		'many-to-many': require('decoy/views/relationships/many-to-many'),
		'task-method': require('decoy/views/task-method'),
		'progress': require('decoy/views/progress'),
		'search': require('decoy/views/search'),
		'crop': require('decoy/views/image_upload/crop'),
		'crop-styles': require('decoy/views/image_upload/crop-styles'),
		'image-fullscreen': require('decoy/views/image_upload/image-fullscreen'),
		'worker': require('decoy/views/worker'),
		'login': require('decoy/views/login'),
		'redirect-select': require('decoy/views/redirect-select'),
		'video-encoder': require('decoy/views/video-encoder'),
		'notification' : require('decoy/views/notification')
	};
});