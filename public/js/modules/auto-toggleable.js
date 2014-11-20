/**
 * Automatically create toggleable sets using data attributes on elements.  Currently, 
 * the triggers must be radio buttons, like often used for type selectors.
 *
 * Example usage:
 * 
 * 	!= Former::radios('type')
 * 		->radios(Bkwld\Library\Laravel\Former::radioArray([
 * 			'internal' => 'Internal',
 *			'external' => 'External',
 *		]))->dataToggleable('type')
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
		, toggleable = require('bkwld/toggleable')
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
				, $rule = $('[data-show-when-'+key+'="'+val+'"]')
			;

			// If the trigger is a radio, assume it is wrapped by a label
			if ($trigger.is(':radio')) $trigger = $trigger.parent();

			// Add a mapping to toggleable that shows all .form-groups that
			// contain an element with a data element that looks like, for example:
			// data-show-when-type="internal".  Also add form groups INSIDE the element
			// with the rule
			return {
				on: $trigger,
				show: $rule.closest('.form-group').add($rule.find($rule.find('.form-group')))
			};
		}));

		// Enable the selected item on page load.  Assuming all triggers are the same 
		// type of element here.
		if ($triggers.is(':radio')) $triggers.filter(':checked').trigger('click');

	});
	

});