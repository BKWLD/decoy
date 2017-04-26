/**
 * Automatically create toggleable sets using data attributes on elements.  Currently,
 * the triggers must be radio buttons, like often used for type selectors.
 *
 * Example usage:
 *
 * 	!= Former::radiolist('type')
 * 		->from([
 * 			'internal' => 'Internal',
 *			'external' => 'External',
 *		])->dataToggleable('type')
 * 	!= Former::textarea('body')->class('wysiwyg')->dataShowWhenType('internal')
 * 	!= Former::text('url', "URL")->dataShowWhenType('external')
 *
 * Note: the value of the `dataToggleable` call is used to form the data key on
 * the toggleable fields.
 */
define(function (require) {

	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, toggleable = require('toggleable')
	;

	/**
	 * Get all the elements with a "toggleable" data element and group them
	 * by their values.  We'll be producing seperate toggleable sets using each
	 * unique group
	 *
	 * @param  DOMElement el An element with a `data-toggleable` attribute
	 * @return The value of `data-toggleable` on `el`
	 */
	_.each(_.groupBy($('[data-toggleable]'), function(el) {
		return $(el).data('toggleable');

	/**
	 * Loop through all toggleable triggers, now grouped by their data-toggleable
	 * value
	 *
	 * @param  array triggers An array of DOMElements of the triggers
	 * @param  string key The toggleable data value that groups sets
	 * @return void
	 */
	}), function(triggers, key) {
		var $triggers = $(triggers);

		/**
		 * Call toggleampe.map for each set, which creates the toggleable mapings.
		 * Specifically, it is passed an array of objects in the format it expects, by
		 * running the triggers through _.map() and looking up the elements to be toggled
		 * using the key for the set.
		 *
		 * @param  DOMElement trigger A specific on click trigger for the set
		 * @return object
		 */
		toggleable.map(_.map(triggers, function(trigger) {

			// Get $ ref and the value
			var $trigger = $(trigger)
				, val = $trigger.val()
				, $matches = $('[data-show-when-'+key+']')
			;

			// Loop through all the elements that have the right show-when key and see
			// if their data contains the trigger value.
			$matches = $matches.filter(function() {
				return $(this).data('show-when-'+key).split(',').indexOf(val) >= 0;
			});

			// If the trigger is a radio, assume it is wrapped by a label
			if ($trigger.is(':radio')) $trigger = $trigger.parent();

			// Add mapping, inlucding any form-groups that contain the matches.
			return {
				on: $trigger,
				show: $matches.add($matches.closest('.form-group'))
			};
		}));

		// Enable the selected item on page load.  Assuming all triggers are the same
		// type of element here.
		if ($triggers.is(':radio')) $triggers.filter(':checked').trigger('click');

	});
});
