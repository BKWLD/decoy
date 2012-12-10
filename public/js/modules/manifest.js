// --------------------------------------------------
// Manifest - hash map of JS modules
// --------------------------------------------------
define(function (require) {
	
	// list each module by ID and require path
	// (note: they must be required here for the build step)
	return {
		'editable-list': require('decoy/views/editable-list'),
		'moderation': require('decoy/views/moderation'),
		'many-to-many': require('decoy/views/many-to-many'),
		'task-method': require('decoy/views/task-method'),
		'ajax-progress': require('decoy/views/ajax-progress')
	};
});