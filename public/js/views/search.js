// --------------------------------------------------
// Helps with the search
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone'),
		storage = require('decoy/plugins/kizzy')('decoy.search');

	// Make the key used to save the state
	var state_key = 'state-'+location.pathname;

	// Public view module
	var Search = Backbone.View.extend({
		
		// Properties
		visible: false,
				
		// Init
		initialize: function () {
			_.bindAll(this);
			
			// Set the initial state
			this.visible = storage.get('visible') ? true : false;
			if (this.visible) this.$el.show();
			
			// Cache selectors
			this.schema = this.$el.data('schema');
			this.title = this.$el.data('title');
			this.$conditions = this.$('.conditions');
			this.$submit = this.$conditions.find('button[type="submit"]');
			this.$search_actions = $('h1 .search-toggle').closest('.btn-group');
			
			// Make the add and substract buttons
			this.$add = $('<button type="button" class="btn add"><i class="icon-plus">');
			this.$subtract = $('<button type="button" class="btn subtract"><i class="icon-minus">');
			
			// Listen for the clicks on the open/close and clear buttons
			this.$search_actions.find('.search-toggle').click(this.toggle);
			this.$search_actions.find('.search-clear').click(this.clear);
			
			// Add an initial row
			if (this.defrost() === false) this.add();
			
			// Defer animation of the clear button
			_.defer(_.bind(function() {
				this.$search_actions.addClass('initialized');
			}, this));
			
			// Redirect the page to apply the filter if there is no query in the
			// url but there is at state.
			// Not currently applied cause it was weird UX
			// this.applyState();
			
		},
		
		// Events
		events: {
			'change .fields': 'change',
			'click .add': 'add',
			'click .subtract': 'subtract',
			'change .comparisons': 'freeze',
			'change .input-field': 'freeze',
			'input .input-field': 'freeze',
			'submit': 'submit'
		},
		
		// Toggle the search open and close
		toggle: function(e) {
			e.preventDefault();
			
			// Remember the state
			this.visible = !this.visible;
			storage.set('visible', this.visible);
			
			// Animate
			this.$el.slideToggle();
			this.toggleClear();
			
		},
		
		// The user has changed a condition to a different field
		change: function(e) {
			if (e) e.preventDefault();
			
			// Get the condition
			var $condition;
			if (e) $condition = $(e.target).closest('.condition');
			else $condition = this.$conditions.find('.condition').last();
			
			// Get the type
			var $fields = $condition.find('.fields'),
				field = $fields.val();
			
			// Add comparison options
			this.removeComparisons($condition);
			$condition.find('.fields').after(this.addComparisons(this.schema[field]));
			
			// Remember the form
			this.freeze();

		},
		
		// Return type specific comparison options
		addComparisons: function(meta) {
			switch(meta.type) {
				
				// Text input
				case 'text':
					return '<select class="comparisons">'+
							'<option value="%*%">contains</option>'+
							'<option value="=">is exactly</option>'+
							'<option value="*%">begins with</option>'+
							'<option value="%*">ends with</option>'+
							'<option value="!%*%">doesn\'t contain</option>'+
						'</select>'+
						'<input type="text" class="input input-field"/>';
				
				// Date selector
				case 'date':
					return $('<select class="comparisons">'+
							'<option value=">">is after</option>'+
							'<option value="<">is before</option>'+
							'<option value="=">is on</option>'+
						'</select>'+
						'<div class="input-append input date">'+
							'<input class="date input-field" maxlength="10" placeholder="mm/dd/yyyy" type="text">'+
							'<span class="add-on"><i class="icon-calendar"></i></span>'+
						'</div>').datepicker();
				
				// Number selector
				case 'number':
					return '<select class="comparisons">'+
							'<option value="=">is</option>'+
							'<option value="!=">is not</option>'+
							'<option value="<">is less than</option>'+
							'<option value=">">is greater than</option>'+
						'</select>'+
						'<input type="number" class="input input-field">';
				
				// Select menu
				case 'select':
					var comparisons = '<select class="comparisons">'+
							'<option value="=">is</option>'+
							'<option value="!=">is not</option>'+
						'</select>';
					var $select = $('<select class="input input-field">');
					_.each(meta.options, function(label, value) {
						$select.append($('<option>').text(label).val(value));
					});
					return comparisons + $select[0].outerHTML;
			}
		},
		
		// Remove any existion comparisons on a condition
		removeComparisons: function($condition) {
			$condition.find('.comparisons,.input').remove();
		},
		
		// Add a new condition
		add: function(e) {
			if (e) e.preventDefault();
			
			// Figure out if this is the initial row
			var is_first = this.$conditions.find('.condition').length === 0;
			
			// Container
			var $condition = $('<div>').addClass('condition');
			
			// Add initial title
			if (is_first) $condition.append('<span class="intro">Find '+this.title+' where the</span>');
			else $condition.append('<span class="intro">and where the</span>');
						
			// Add the fields list
			if (_.size(this.schema) > 1) {
				$condition.append('</span>');
				var $fields = $('<select>').addClass('fields');
				_.each(this.schema, function(meta, field) {
					$fields.append($('<option>').text(meta.label.toLowerCase()).val(field));
				});
				$condition.append($fields);
				
			// There is only a single field
			} else {
				$condition.find('.intro').append(' <strong>'+_.values(this.schema)[0].label.toLowerCase()+'</strong>');
				$condition.append('<input type="hidden" class="fields" value="'+_.keys(this.schema)[0]+'"/>');
			}
			
			// Add the add/substract button
			if (is_first) $condition.append(this.$add.clone());
			else $condition.append(this.$subtract.clone());
			
			// Add the new condition to the dom
			this.$submit.before($condition);
			
			// Produce input fields for the first field
			this.change();
			
			// Update the cached version
			this.freeze();
			
			// Return the new condition
			return $condition;
			
		},

		// Remove a condition
		subtract: function(e) {
			if (e) e.preventDefault();
			var $condition = $(e.target).closest('.condition');
			$condition.remove();
			this.freeze();
		},
		
		// Freeze the state of the form in storage
		freeze: function() {
			storage.set(state_key, this.serialize());
			this.toggleClear();
		},
		
		// Restore the form from a frozen state
		defrost: function() {
			var conditions = storage.get(state_key);
			if (!conditions || conditions.length === 0) return false;
			
			// Loop through the conditions, add new rows, and then set them to what
			// was in the conditions
			_.each(conditions, function(condition) {
				var $condition = this.add();
				
				// Restore choices
				if ($condition.find('select.fields').length) $condition.find('.fields')[0].selectedIndex = condition[0];
				this.change(); // selectedIndex won't tigger the handler automatically
				$condition.find('.comparisons').val(condition[1]);
				$condition.find('.input-field').val(condition[2]);
				
			}, this);
			
			// Update the state after the last change
			this.freeze();
		},
		
		// Serialize the state of the form.  It's done in a terse form because
		// the serialized form will be converted to JSON and used as the query
		// for the page as well
		serialize: function(ignore_empty) {
			
			// Loop through the conditions
			var conditions = [];
			this.$conditions.find('.condition').each(function() {
				var $condition = $(this);
				
				// Lookup vals
				var field = $condition.find('select.fields').length ? $condition.find('.fields')[0].selectedIndex : 0,
					comparison = $condition.find('.comparisons').val(),
					input = $condition.find('.input-field').val();
					
				// Don't add empty items
				if (input) clearable = true;
				if (ignore_empty && !input) return;
				
				// Add the field choice, comparison chocie, and selected value
				conditions.push([field, comparison, input]);
				
			});
			
			// Return object that has the state
			return conditions;
		},
		
		// Submit the form by redirecting with the serialized query
		submit: function(e) {
			if (e) e.preventDefault();
			
			// Remove any existing query from the search
			var search = this.stripQuery(location.search);
			
			// Add the query
			var query = this.serialize(true);
			if (query.length) {
				if (!search) search = '?';
				else search += '&';
				search += 'query='+encodeURIComponent(JSON.stringify(query));
			}
			
			// Redirect the page
			location.href = location.origin+location.pathname+search;
			
		},
		
		// Remove the query from the search query
		stripQuery: function(search) {
			return search.replace(/&?query=[^&]+/, '');
		},
		
		// Toggle the clear button
		toggleClear: function() {
			
			// Get the conditions from the frozen state
			var conditions = storage.get(state_key);
			
			// Anytime we serialize, check if we should show or hide the clear button.
			// The form must be visible and have more than one condition or an input
			// value in the first condition.  This function gets called often but jquery
			// won't add a class more than once, so it won't be triggered too often.
			if (this.visible && (conditions.length > 1 || conditions[0][2])) this.$search_actions.removeClass('closed');
			else this.$search_actions.addClass('closed');
			
		},
		
		// Clear the form
		clear: function(e) {
			if (e) e.preventDefault();
			
			// Clear the state
			storage.set(state_key, null);
			
			// Redirect with no query
			var search = this.stripQuery(location.search);
			location.href = location.origin+location.pathname+search;
		},
		
		// Redirect the page to apply the filter if there is no query in the
		// url but there is at state
		applyState: function() {
			
			// If there is a query in the location, then do nothing
			if (location.search.match(/query=/)) return;
			
			// If there is no state, do nothing
			if (!storage.get(state_key)) return;
			
			// Otherwise, redirect the page by doing a submit
			this.submit();
			
		}
		
	});
	
	return Search;
	
});