// --------------------------------------------------
// Manifest - hash map of JS modules
// --------------------------------------------------
define(function (require) {

	// list each module by ID and require path
	// (note: they must be required here for the build step)
	return {
		'standard-list': require('../views/standard-list'),
		'moderation': require('../views/moderation'),
		'autocomplete': require('../views/autocomplete'),
		'belongs-to': require('../views/relationships/belongs-to'),
		'many-to-many': require('../views/relationships/many-to-many'),
		'task-method': require('../views/task-method'),
		'progress': require('../views/progress'),
		'search': require('../views/search'),
		'worker': require('../views/worker'),
		'login': require('../views/login'),
		'redirect-select': require('../views/redirect-select'),
		'video-encoder': require('../views/video-encoder'),
		'notification' : require('../views/notification')
	};
});
