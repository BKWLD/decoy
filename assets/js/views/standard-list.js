// --------------------------------------------------
// Editable list view
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		__ = require('../localize/translated'),
		Backbone = require('backbone');

	// Bring in just enough jQuery UI for drag and drop
	require('jquery-ui');

	// Bring in the template for new rows.  Currently, the only need to do this
	// is for many-to-many row insertion
	var row_template = _.template(require('../templates/standard-list-row.html'));

	// Static vars
	var app,
		dataId = 'data-model-id',
		publicIconClass = 'glyphicon-eye-open',
		privateIconClass = 'glyphicon-eye-close';

	// View
	var StandardList = Backbone.View.extend({

		initialize: function (options) {
			_.bindAll(this);
			app = options.app;

			// Get the path to the controller.  If this is not specified via a
			// data attribtue of "controller-route" then we attempt to infer it from
			// the current URL.
			this.controllerRoute = this.$el.data('controller-route');
			if (!this.controllerRoute) {
				this.controllerRoute = window.location.pathname;
			}

			// Cache
			this.$deleteBtn = this.$('.delete-selected');
			this.$deleteAlert = this.$('.delete-alert');
			this.$bulkActions = this.$('.bulk-actions');
			this.$total = this.$('.legend .badge');
			this.$trs = this.$el.find('[' + dataId + ']');
			this.parent_controller = this.$el.data('parent-controller');
			this.position_offset = this.$el.data('position-offset');
			this.$no_results = this.$('.no-results');

			// Create model collection from table rows.  The URL is fetched from
			// the controller-route data attribute of the container.
			this.rows = [];
			this.collection = new Backbone.Collection();
			this.collection.url = this.controllerRoute;
			_.each(this.$trs, this.initRow);

			// listen for collection changes and render view
			this.collection.on('change', this.render, this);
			this.collection.on('change:featured', this.updateFeatured, this);
			this.collection.on('remove', this.onRemove);

			// Add drag and drop if there is position data on the first
			// row.  It expects the current position of the a row to be stored
			// as a data value on the row
			if (this.$trs.first().data('position') !== undefined) {
				this.$sortContainer = this.initSortable();
			}
		},

		// Init an individual row in the list
		initRow: function (row) {

			// Find vars
			var $row = $(row),
				modelId = $row.attr(dataId),
				$inputs = $row.find('input'),
				featured = $inputs.filter('[name=set-featured]').prop('checked'),
				$visibility = $row.find('.visibility'),
				visible_state = $visibility.find('.'+publicIconClass).length > 0,
				position = $row.data('position'),
				parent_id = $row.data('parent-id');

			// Define the model data
			var data = {
				id: modelId,
				selected: false,
				featured: featured,
				position: position,
				parent_id: parent_id
			};

			// If this item supports visibility, add it to the model
			if ($visibility.length) {
				data.public = visible_state;
			}

			// If there was a parent_controller, add it to the model so sync
			// requests can make use of it
			if (this.parent_controller) {
				data.parent_controller = this.parent_controller;
			}

			// Create the model and store a reference
			var model = new Backbone.Model(data);
			model.whitelist = ['position']; // Only sync position
			if ($visibility.length) model.whitelist.push('public');
			if (this.parent_controller) model.whitelist.push('parent_controller');
			if (parent_id) model.whitelist.push('parent_id');

			// Add the model to the collection
			this.collection.push(model);
			$inputs.data('model', model);
			this.rows.push($row);

			// reset checkboxes on reload (for Firefox)
			$inputs.filter('[name=select-row]').prop('checked', false);
		},

		// Turn on sortability
		initSortable: function() {

			// Cache some selectors
			var $sortable = this.$el.find('tbody')
				, $table = $sortable.parent('table')
			;

			// Tell the server of the new sorting rules by looping through
			// all rows, looking up the model for the id, and then updating
			// it's position attribute.
			var update = _.bind(function(event, ui) {
				var id,
					$sortableRows = $sortable.find('[' + dataId + ']');
				_.each($sortableRows, function(el, i) {
					id = $(el).attr(dataId);
					this.collection.get(id).set({position: (i + this.position_offset) });
				}, this);
			}, this);

			// Define options
			var options = {
				tolerance: 'pointer',
				revert: 100,
				containment: $sortable,

				// Prevent no-results from being sortable
				items: 'tr:not(.no-results, .bulk-actions)',

				// Create the placeholder with the right column span
				// http://stackoverflow.com/a/8707306
				placeholder: 'placeholder',
				forcePlaceholderSize: true,
				start: function(event, ui) {
					ui.placeholder
						.html("<td colspan='999'></td>")
						.height(ui.helper.height())
					;
				},

				// Preserve the widths of columns during dragging by freezing widths
				// From http://cl.ly/170d0h291V10
				helper: function(e, $tr) {

					// Take advantage of this being early in the call to fix the height of
					// the table so that the containment measures the currect height of
					// the table
					$table.css('height', $table.height());

					// Set the widths
					$tr.children().each(function(index) {
						$(this).width($(this).width());
					});
					return $tr;
				},

				// Clear the fixed height when sorting is finished
				stop: function(event, ui) {
					$table.css('height', '');
				},

				// Callback function after sorting happens.
				update: update
			};

			// Turn on sorting
			$sortable.sortable(options).disableSelection();

			// Listen for changes and persist them to the server
			this.collection.on('change:position', function(model, position) {
				model.save();
			}, this);

			// Return a reference to the sortable item
			return $sortable;
		},

		events: {
			'click .select-all': 'toggleAll',
			'click .delete-selected': 'deleteSelected',
			'click .remove-confirm': 'removeConfirm',
			'click .delete-confirm': 'deleteConfirm',
			'click .delete-cancel': 'deleteCancel',
			'click input[name=select-row]': 'toggleSelect',
			'click input[name=set-featured]': 'setFeatured',
			'click .delete-now': 'deleteNow',
			'click .remove-now': 'removeNow',
			'click .visibility': 'toggleVisibility',
			'insert': 'insertNew',
			'insertEl': 'insertEl'
		},

		// Delete the row via JS
		deleteNow: function(e) {
			e.preventDefault();

			// Find the model
			var $row = $(e.target).closest('tr'),
				modelId = $row.attr(dataId),
				model = this.collection.get(modelId);

			// Hide while waiting
			if ($row.data('deleting')) return;
			$row.data('deleting', true);
			$row.animate({opacity:0.2}, 100);

			// Delete it
			model.destroy({

				// Fade out on success
				success: _.bind(function() {
					this.hideRow($row);

					// Decrement the counter
					this.$total.text(parseInt(this.$total.first().text(),10) - 1);

					// Notify listeners that there was a change
					this.$el.trigger('change');

				},this),

				// Show error on failure
				error:function() {
					$row.animate({opacity:1}, 300);
					$row.data('deleting', false);
				}
			});
		},

		// Remove the pivot row via JS
		removeNow: function(e) {
			e.preventDefault();

			// Find the model
			var $a = $(e.target).closest('a'),
				$row = $a.closest('tr'),
				model_id = $row.attr(dataId),
				parent_id = $row.data('parent-id');

			// Hide while waiting
			if ($row.data('removing')) return;
			$row.data('removing', true);
			$row.animate({opacity:0.2}, 100);

			// Call the remove route
			$.ajax(this.controllerRoute+'/'+model_id+'/remove', {
				data: {
					parent_controller: this.parent_controller,
					parent_id: parent_id},
				type: 'DELETE',
				dataType: 'JSON'
			})

			// Fade out on success
			.done(_.bind(function() {
				this.hideRow($row);

				// Decrement the counter
				this.$total.text(parseInt(this.$total.first().text(),10) - 1);

				// Notify listeners that there was a change
				this.$el.trigger('change');

				// Update collection
				this.collection.remove(this.collection.get(model_id));

			}, this))

			// Show error on failure
			.fail(function() {
				$row.animate({opacity:1}, 300);
				$row.data('removing', false);
			});
		},

		// Hide a row, a in a delete
		hideRow: function($row) {

			// If a soft deleting controller, set to the trashed appearance instead
			if (this.$el.data('with-trashed')) {
				return this.trashRow($row);
			}

			// Loop through the columns
			$row.find('td').each(function() {

				// Animate out the padding of the cells
				$(this).animate({paddingTop: 0, paddingBottom:0}, 300);

				// Add a div inside the cell and animate the hight going to 0 if it
				// (since we can't aniamte the row itself)
				$(this).wrapInner("<div/>").children("div").animate({height: 0}, 300, function() {
					$row.remove();
				});
			});
		},

		// Mark a row as trashed
		trashRow: function($row) {

			// Add trashed style to row and rmemove the faded out state
			$row.addClass('is-trashed').animate({ opacity:1 }, 300);

			// Swap can icon for a non-interactive one
			$row.find('.delete-now')
			.after('<span class="glyphicon glyphicon-trash">')
			.tooltip('destroy')
			.remove();

			// Disable the checkbox
			$row.find('[name="select-row"]').prop('disabled', true);
		},

		toggleAll: function () {
			var anyFalse = this.collection.where({ selected: false }).length;
			this.collection.invoke('set', 'selected', anyFalse ? true : false);
		},

		deleteSelected: function () {
			this.$deleteAlert.removeClass('hide');
			this.render();
		},

		// Delete rows using ajax.
		deleteConfirm: function () {

			// Vars
			var models = this.collection.where({ selected: true }),
				ids = _.pluck(models, 'id'),
				$rows = this.findRows(ids);

			// Hide while waiting
			_.each($rows, function($row) {
				if ($row.data('deleting')) return;
				$row.data('deleting', true);
				$row.animate({opacity:0.2}, 100);
			}, this);

			// Delete them
			_.each(models, function(model, i) {
				model.destroy({

					// Fade out on success
					success: _.bind(function() {

						// Update the editable list controls
						this.render();

						// The delay is so it happens after the controls disapear
						var $row = this.findRows(model.id)[0];
						_.delay(this.hideRow, i*100 + 300, $row);

						// Decrement the counter
						this.$total.text(parseInt(this.$total.first().text(),10) - 1);

					},this),

					// Show error on failure
					error:function() {
						_.each($rows, function($row) {
							$row.animate({opacity:1}, 300);
							$row.data('deleting', false);
						}, this);
					}
				});
			}, this);

			// Notify listeners that there was a change.  Out here rather than on success
			// callback so that it doesn't get invoked once for each delete.
			this.$el.trigger('change');

		},

		// Remove a many-to-many relationship.  This should be dried up so we're just changing
		// statuses on the model and it's handling the presentation.
		removeConfirm: function() {

			// Vars
			var ids = _.pluck(this.collection.where({ selected: true }), 'id'),
				$rows = this.findRows(ids),
				url = this.controllerRoute+'/'+ids[0]+'/remove',
				parent_id = $rows[0].data('parent-id');

			// Hide while waiting
			_.each($rows, function($row) {
				if ($row.data('removing')) return;
				$row.data('removing', true);
				$row.animate({opacity:0.2}, 100);
			}, this);

			// Call the bulk remove route
			$.ajax(url, {
				data: {
						parent_controller: this.parent_controller,
						parent_id: parent_id,
						ids: ids.join(',')
				},
				type: 'DELETE',
				dataType: 'JSON'
			})

			// Fade out on success
			.done(_.bind(function() {

				// Remove the models from the collection
				this.collection.remove(this.collection.where({ selected: true }));
				this.render();

				// Hide all the rows.  The delay is so it happens after the controls disapear
				_.each($rows, function($row, i) {
					_.delay(this.hideRow, i*100 + 300, $row);
				}, this);

				// Decrement the counter
				this.$total.text(parseInt(this.$total.first().text(),10) - ids.length);

				// Notify listeners that there was a change
				this.$el.trigger('change');

			}, this))

			// Show error on failure
			.fail(function() {
				_.each($rows, function($row) {
					$rows.animate({opacity:1}, 300);
					$rows.data('removing', false);
				}, this);
			});

		},

		// Get all of the DOM elements as jquery elements that have the passed ids
		findRows: function(ids) {
			if (!_.isArray(ids)) ids = [ids];
			return _.filter(this.rows, function($row) {
				return _.contains(ids, $row.attr(dataId));
			}, this);
		},

		deleteCancel: function () {
			this.$deleteAlert.addClass('hide');
			this.render();
		},

		toggleSelect: function (e) {
			var model = $(e.target).data('model');
			model.set('selected', !model.get('selected'));
		},

		setFeatured: function (e) {
			var model = $(e.target).data('model');
			model.set('featured', true);
		},

		// Toggle the visibility of the model
		toggleVisibility: function(e) {
			e.preventDefault();

			// Find the model
			var $row = $(e.target).closest('tr'),
				modelId = $row.attr(dataId),
				model = this.collection.get(modelId);

			// Set the visibility status
			model.set('public', model.get('public') ? 0 : 1);
			model.save();

			// Update the UI
			this.render(model);

		},

		// update featured state and deal with server request
		updateFeatured: function (model) {
			// tell other models to not be featured
			var others = this.collection.without(model);
			_.invoke(others, 'set', 'featured', false, { silent: true });
			// gather model data for backend request
			var output = _.map(this.collection.models, function (model) {
				return model.get('id') + ':' + model.get('featured');
			});
			window.console.log('TODO: tell backend about featured', output);
		},

		// Insert a new row into the list.  This may be triggered by many-to-many
		insertNew: function(e, data) {

			// Get all of the column values as an array
			var columns = _.values(data.columns);

			// Build the row.  Note, the id must be unique for each row.  This means
			// that we can't insert multiple rows for the same join or the bulk
			// actions won't work
			var $row = $(row_template({
				id: data.id,
				parent_id: data.parent_id,
				label: columns.shift(),
				controller: this.controllerRoute
			}));

			// Add additional columns if we're not in a related sidebar
			if (!this.$el.closest('.related').length) {
				_.each(columns, function(html) {

					// Add a new cell before the last one (which is the actions cell)
					if (!html) html = ''; // Handle NULL
					$row.find('td:last').before('<td>'+html+'</td>');

				});
			}

			// Register it
			this.addInsertedRow($row);

			// Fade it in
			$row.hide().fadeIn();

		},

		// Insert a new row by passing a reference to its DOM element
		insertEl: function(e, el) {
			this.addInsertedRow($(el));
		},

		// Add the row to the DOM, reigster it with Backbone, and run other common
		// methods upon it.
		addInsertedRow: function($row) {

			// If there are no rows, there now will be, so hide the no-results message.
			this.$no_results.addClass('remove');

			// Add the template to the list above the no-results row.
			this.$no_results.before($row);

			// Add the backbone brains (it's not expecting a jquery object)
			this.initRow($row[0]);

			// Increment the counter
			this.$total.text(parseInt(this.$total.first().text(),10) + 1);

			// Enable tooltips
			$row.find('.js-tooltip').tooltip({ animation: false });

			// Notify listeners that there was a change
			this.$el.trigger('change');

		},

		// An item has been removed from the collection
		onRemove: function(model, collection, status) {
			if (!collection.length) this.$no_results.removeClass('remove');
		},

		// render view from model changes
		render: function (model) {

			// if there's a model, iterate through row elements using model id and make the
			// checkbox checked if the model thinks it should be
			model && _.each(this.rows, function ($row) {
				if ($row.attr(dataId) !== model.get('id')) return;
				$row.find('input[name=select-row]').prop('checked', model.get('selected'));
			});

			// Update the visibilty state
			if (model && model.has('public')) {
				var $row = this.findRows(model.id)[0],
					$icon = $row.find('.visibility .glyphicon');

				// Toggle icon
				if (model.get('public')) {
					$icon.addClass(publicIconClass);
					$icon.removeClass(privateIconClass);
					$icon.attr('title', __('standard_list.private'));
					$row.find('.visibility.js-tooltip').attr('data-original-title', __('standard_list.private'));
				} else {
					$icon.removeClass(publicIconClass);
					$icon.addClass(privateIconClass);
					$icon.attr('title', __('standard_list.publish'));
					$row.find('.visibility.js-tooltip').attr('data-original-title', __('standard_list.publish'));
				}
			}

			// Update the disabled state of the delete/remove buttons
			var anySelected = this.collection.where({ selected: true }).length,
				enableDelete = anySelected && (!this.$deleteAlert.length || this.$deleteAlert.hasClass('hide'));
			if (enableDelete) {
				this.$deleteBtn.removeClass('disabled');
				this.$bulkActions.removeClass('hide');
			} else {
				this.$deleteBtn.addClass('disabled');
				this.$bulkActions.addClass('hide');
			}

			// Toggle the sortability
			if (this.$sortContainer) {
				if (anySelected) this.$sortContainer.sortable('disable');
				else this.$sortContainer.sortable('enable');
			}

			// Hide the alert if none are selected
			if (!anySelected) this.$deleteAlert.addClass('hide');
		}
	});

	return StandardList;
});
