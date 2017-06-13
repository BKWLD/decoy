// --------------------------------------------------
// Apply listeners to delete buttons that require an
// admin to confirm the delete.  This must be manually
// opted into by requiring this module from an app's
// admin/start.js before the decoy is started.
//
// require('decoy/modules/chicken-switch').register();
// decoy.trigger('ready');
//
// --------------------------------------------------
define(function(require) {

	// Dependencies
	var $ = require('jquery');
    var __ = require('../localize/translated');

	// Add listeners to all listing delete links and all delete
	// links on edit pages
	function register() {
		$('.listing')
		.find('.delete-now:not([disabled]), .remove-now:not([disabled])')
		.add('.form-actions .delete:not([disabled])')
		.on('click', prompt);
	}

	// Prompt user to confirm deletes
	function prompt(e, bypass) {
		var $el = $(this);

		// Don't prevent if "bypass" was in the data.
		if (bypass === true) {
			var href = $el.attr('href');
			if (href && href != '#') return window.location.href = href;
			else return;
		}

		// Prevent default behavior
		e.preventDefault();
		e.stopImmediatePropagation();

		// Hide tooltips
		var title;
		if ($el.hasClass('js-tooltip')) {
			$el.tooltip('destroy');

			// Remove the title so that the popover doesn't have one. I don't
			// like how it looks and this is the only way to clear it.
			title = $el.data('original-title');
			$el.removeAttr('data-original-title');
		}

		// Show the dialog
		$el.popover({
			trigger: 'manual',
			content: '<p class="text-center">'+__('chicken_switch.text')+'</p><div><a href="#" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> '+__('chicken_switch.confirm')+'</a> <a href="#" class="btn btn-default">'+__('chicken_switch.cancel')+'</a></div>',
			html: true,

			// If a butto, it's an edit button, so put the popup on top
			placement: $el.hasClass('btn') ? 'top' : 'left'

		}).popover('show');

		// Listen on clicks
		var data = {
			$target: $el,
			title: title
		};
		$(this).next('.popover').find('.btn-danger').on('click', null, data, destroy);
		$(this).next('.popover').find('.btn-default').on('click', null, data, close);

	}

	// Close the popup and retrigger the click on the target but bypass the prompt
	function destroy(e) {
		close(e);
		e.data.$target.trigger('click', true);
	}

	// Remove the prompt and re-add the tooltip
	function close(e) {
		e.data.$target.popover('hide');
		if (e.data.$target.hasClass('js-tooltip')) {
			e.data.$target.tooltip({
				animation: false,
				title: e.data.title
			});
		}
	}

	// Public API
	return {
		register: register
	};

});
