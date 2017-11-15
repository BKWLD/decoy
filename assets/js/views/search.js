// --------------------------------------------------
// Helps with the search
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
     	__ = require('../localize/translated'),
		Backbone = require('backbone');

	// Make the key used to save the state
	var state_key = 'state-'+location.pathname;

	// Public view module
	var Search = Backbone.View.extend({

		// Properties
		visible: false,

		// Init
		initialize: function () {
			_.bindAll(this);

			// Parse the query string
			this.query = this.parseQuery();
			this.visible = !!this.query

			// Cache selectors
			this.schema = this.$el.data('schema');
			this.$conditions = this.$('.conditions');
			this.$submit = this.$conditions.find('button[type="submit"]');
			this.$search_actions = $('.search-controls');

			// Set initial state
			if (this.visible) this.$el.show();
			this.toggleClear();

			// Make the add and substract buttons
			this.$add = $('<button type="button" class="btn btn-sm outline add"><span class="glyphicon glyphicon-plus">');
			this.$subtract = $('<button type="button" class="btn btn-sm outline subtract"><span class="glyphicon glyphicon-minus">');

			// Listen for the clicks on the open/close and clear buttons
			this.$search_actions.find('.search-toggle').click(this.toggle);
			this.$search_actions.find('.search-clear').click(this.clear);

			// Add an initial row
			if (this.defrost() === false) this.add();

			// Defer animation of the clear button
			_.defer(_.bind(function() {
				this.$search_actions.addClass('initialized');
			}, this));
		},

		// Parse the query string
		// Modified from https://gist.github.com/ryoppy/5780748
		parseQuery: function() {
			var query = window.location.search.substring(1);
			if (!query) return;
			query = _.chain(query.split('&'))
				.map(function(params) {
					var p = params.split('=');
					return [p[0], decodeURIComponent(p[1])];
				})
				.object().value();
			if (query.query) return JSON.parse(query.query);
		},

		// Events
		events: {
			'change .fields': 'change',
			'click .add': 'add',
			'click .subtract': 'subtract',
			'submit': 'submit'
		},

		//----------------------------------------------------------------
		// Render the UI
		//----------------------------------------------------------------

		// Toggle the search open and close
		toggle: function(e) {
			e.preventDefault();

			// Remember the state
			this.visible = !this.visible;

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
		},

		// Return type specific comparison options
		addComparisons: function(meta) {
			switch(meta.type) {

				// Text input
				case 'text':
					return '<select class="comparisons form-control">'+
							'<option value="%*%">'+__('search.text_field.contains')+'</option>'+
							'<option value="=">'+__('search.text_field.is_exactly')+'</option>'+
							'<option value="*%">'+__('search.text_field.begins_with')+'</option>'+
							'<option value="%*">'+__('search.text_field.ends_with')+'</option>'+
							'<option value="!%*%">'+__('search.text_field.does_not_contain')+'</option>'+
						'</select>'+
						'<input type="text" class="input input-field form-control"/>';

				// Date selector
				case 'date':
					return $('<select class="comparisons form-control">'+
							'<option value=">">'+__('search.date_field.is_after')+'</option>'+
							'<option value="<">'+__('search.date_field.is_before')+'</option>'+
							'<option value="=">'+__('search.date_field.is_on')+'</option>'+
						'</select>').add($(''+
						'<div class="input input-group date-field date">'+
							'<input class="date input-field form-control" maxlength="10" placeholder="' + __('date.placeholder') + '" type="text">'+
							'<span class="input-group-btn"><button class="btn btn-default" type="button"><span class="glyphicon glyphicon-calendar"></button></span></span>'+
						'</div>').datepicker({
							todayHighlight: true,
              format: __('date.format')
						}));

				// Number selector
				case 'number':
					return '<select class="comparisons form-control">'+
							'<option value="=">'+__('search.number_field.is')+'</option>'+
							'<option value="!=">'+__('search.number_field.is_not')+'</option>'+
							'<option value="<">'+__('search.number_field.is_less_than')+'</option>'+
							'<option value=">">'+__('search.number_field.is_greater_than')+'</option>'+
						'</select>'+
						'<input type="number" class="input input-field form-control">';

				// Select menu
				case 'select':
					var comparisons = '<select class="comparisons form-control">'+
							'<option value="=">'+__('search.select_field.is')+'</option>'+
							'<option value="!=">'+__('search.select_field.is_not')+'</option>'+
						'</select>';
					var $select = $('<select class="input input-field form-control">');
					_.each(meta.options, function(label, value) {
						$select.append($('<option>').text(label).val(value));
					});
					return comparisons + $select[0].outerHTML;
			}
		},

		// Remove any existing comparisons on a condition
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
			if (is_first) $condition.append('<span class="intro">'+__('search.filter_where')+'</span>');
			else $condition.append('<span class="intro">'+__('search.and_where')+'</span>');

			// Add the fields list
			if (_.size(this.schema) > 1) {
				$condition.append('</span>');
				var $fields = $('<select class="form-control fields">');
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

			// Return the new condition
			return $condition;

		},

		// Remove a condition
		subtract: function(e) {
			if (e) e.preventDefault();
			var $condition = $(e.target).closest('.condition');
			$condition.remove();
		},

		// Toggle the clear button
		toggleClear: function() {
			if (this.visible) this.$search_actions.removeClass('closed');
			else this.$search_actions.addClass('closed');

		},

		//----------------------------------------------------------------
		// Store and recall the state of the form
		//----------------------------------------------------------------

		// Restore the form from a frozen state
		defrost: function() {
			var conditions = this.query;
			if (!conditions || conditions.length === 0) return false;

			// Loop through the conditions, add new rows, and then set them to what
			// was in the conditions
			_.each(conditions, function(condition) {
				var $condition = this.add();

				// Restore choices
				$condition.find('.fields').val(condition[0]);
				this.change(); // Have to trigger handler manually
				$condition.find('.comparisons').val(condition[1]);
				$condition.find('.input-field').val(condition[2]);

				// Update date picker to highlight current day
				$condition.find('.date-field').datepicker('update');

			}, this);
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
				var field = $condition.find('.fields').val(),
					comparison = $condition.find('.comparisons').val(),
					input = $condition.find('.input-field').val();

				// Don't add empty items
				if (ignore_empty && !input) return;

				// Add the field choice, comparison choice, and selected value
				conditions.push([field, comparison, input]);

			});

			// Return object that has the state
			return conditions;
		},

		//----------------------------------------------------------------
		// Act on the contents of the form
		//----------------------------------------------------------------

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
			location.href = location.pathname+search;

		},

		// Clear the form
		clear: function(e) {
			if (e) e.preventDefault();

			// Redirect with no query
			var search = this.stripQuery(location.search);
			location.href = location.pathname+search;
		},

		// Redirect the page to apply the filter if there is no query in the
		// url but there is at state
		applyState: function() {

			// If there is a query in the location, then do nothing
			if (location.search.match(/query=/)) return;

			// Otherwise, redirect the page by doing a submit
			this.submit();

		},

		//----------------------------------------------------------------
		// Utils
		//----------------------------------------------------------------

		// Remove the query from the search query
		stripQuery: function(search) {
			return search.replace(/\??&?query=[^&]+/, '');
		}

	});

	return Search;

});
