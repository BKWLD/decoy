// --------------------------------------------------
// Decoy app init and events
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, FastClick = require('fastclick')
		, Affixable = require('./modules/affixable')
		, Sidebar = require('./modules/sidebar')
		, LocalizeCompare = require('./localize/compare')
		, manifest = require('./modules/manifest')
		, bootstrap = require('bootstrap')
	;

	// Modules that add mojo globally
	require('console-polyfill'); // Console polyfill for older IEs
	require('jquery-backbone-views'); // Add backbone views plugin for jquery
	require('./modules/datepicker'); // Init datepickers created with Former::date()
	require('./modules/timepicker'); // Init timepickers created with Former::time()
	require('./modules/datetimepicker'); // Init datepickers created with Former::datetime()
	require('./modules/auto-toggleable'); // Scan make for attributes that enable toggling
	require('./modules/chicken-switch').register(); // Enable chicken switches on delete

	// Private static vars
	var app = _.extend({}, Backbone.Events),
		$body = $('body'),
		$doc = $(document);

	// --------------------------------------------------
	// Pre-ready init

	// Disable # links because they're not links silly
	$doc.on('click', '[href="#"]', false);

	// Enable fast click
	FastClick.attach(document.body);

	// Pass CSRF token with all ajax requests
	var csrfToken = $('meta[name="csrf-token"]').attr('content');
	$.ajaxPrefilter(require('jquery-csrf-prefilter')(csrfToken));

	// --------------------------------------------------
	// Configure Backbone

	// Massage attribtues of the sync process
	var oldSync = Backbone.sync;
	Backbone.sync = function(method, model, options){

		// If there is a 'whitelist' attribute on the model and it's
		// an array, delete all attributes except for the ones from the
		// whitelist
		if (model.whitelist && _.isArray(model.whitelist)) {

			// Add id by default, needed in Backbone 1.1.1
			if (!_.contains(model.whitelist, 'id')) model.whitelist.push('id');

			// Don't operate on the real model.  But, for whatever reason, the
			// collection didn't get brought along on the clone, so do that manually
			var oldModel = model;
			model = oldModel.clone();
			model.collection = oldModel.collection;

			// Remove attribtues
			_.each(model.attributes, function(value, key, list) {
				if (!_.contains(oldModel.whitelist, key)) delete model.attributes[key];
			});
		}

		// Finish up with the traditional sync
		return oldSync(method, model, options);
	};

	// Find all valid data-js-views and create them.  A data-js-view tag may specify
	// multiple backbone views by seperating them with a space
	app.initalizeViews = function(manifest) {
		_.each($body.find('[data-js-view]'), function (elem) {
			var views = $(elem).attr('data-js-view').split(' ');
			_.each(views, function(identifier) {
				var view = manifest[identifier];
				if (view) new view({ el: elem, app: app });
			});
		});
	};

	// Newer style view declaration
	$('body.elements.field #main').views(require('./elements/field'));
	$('body.elements.index .tab-sidebar').views(require('./views/tab-sidebar'));
	$('body > .sidebar').views(Sidebar); // The nav sidebar
	if ($('.form-group.compare').length) $('.related-left-col .form-group').views(LocalizeCompare);
	$('.admin-permissions').views(require('./views/admin-permissions'));
	$('.image-upload').views(require('./views/image-upload/image-field'));
	$('input[type="file"]').views(require('./views/bootstrap-file'));

	// Launch change modal
	var changes_modal = require('./modules/changes-modal');
	$('.changes-modal-link').on('click', changes_modal.open);

	// --------------------------------------------------
	// DOM ready
	app.init = function() {

		// Initalize views
		app.initalizeViews(manifest);

		// Add "Required" icons to file input fields where we're manually applying
		// a required class with Former, which puts it on the input rather than the
		// control group.  We want these fields to look required but not actually be
		// enforced by the browser.
		var required_html = ' <span class="glyphicon glyphicon-exclamation-sign js-tooltip required" title="Required field"></span>';
		$('input.required, textarea.required')
			.closest('.form-group')
			.find('.control-label')
			.append(required_html);

		// Enable bootstrap tooltips
		$body.find('.js-tooltip').tooltip({
			animation: false,
			html: true,
			container: '#main' // Add them out here to prevent some z-index issues
		});

		// Turn WYSIWYGs on.
		require('./wysiwyg/factory').init('textarea.wysiwyg');

		// Enable affix globally
		$('.affixable').views(Affixable);

	};

	// Deprecated method of init-ing Decoy
	app.on('ready', app.init);

	// Return public module
	return app;
});
