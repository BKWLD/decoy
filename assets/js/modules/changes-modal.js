define(function (require) {

	// Dependencies
	var $ = require('jquery'),
		__ = require('../localize/translated'),
		_ = require('lodash'),
		bootstrap = require('bootstrap'),
		tpl = _.template('\
			<div class="modal fade changes-modal">\
				<div class="modal-dialog">\
					<div class="modal-content">\
						<div class="modal-header">\
								<button type="button" class="close" data-dismiss="modal" \
								aria-label="' + __('changes.close') + '"><span aria-hidden="true">&times;</span></button>\
								<h4 class="modal-title">' + __('changes.changes_to') + ' "<%=title%>"</h4>\
						</div>\
						<div class="modal-body">\
								<%=body%>\
						</div>\
						<div class="modal-footer">\
								<%=action%> ' + __('changes.on') + ' <%=date%> ' + __('changes.by') + ' \
								<% if (admin_edit) { %>\
									<a href="<%= admin_edit %>"><%= admin %></a>\
								<% } else { %>\
									<%= admin %>\
								<% } %>\
						</div>\
					</div>\
				</div>\
			</div>');

	/**
	 * Request the attribtues from the server
	 *
	 * @param {MouseEvent} $e
	 * @return {void}
	 */
	function open(e) {
		e.preventDefault();
		var url = $(e.currentTarget).attr('href');
		$.get(url).done(render);
	}

	/**
	 * Render the content and finish opening the modal
	 *
	 * @param {object} data The changes edit response
	 * @return {void}
	 */
	function render(data) {

		// Capitalize the action
		data.action = data.action.charAt(0).toUpperCase() + data.action.slice(1);

		// Create the body from the list of attributes
		data.body = '<dl class="dl-horizontal">' +
			_.reduce(data.attributes, function(html, val, key) {
				return html+'<dt>'+key+'</dt><dd>'+(val||'<em>' + __('changes.empty') + '</em>')+'</dd>';
			}, '') + '</dl>';

		// Render markup and render
		$(tpl(data)).modal();
	}

	// Return API
	return { open: open };

});
