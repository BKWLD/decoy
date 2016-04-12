define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, bootstrap = require('bootstrap')
		, tpl = _.template(require('decoy/templates/changes-modal.html'));
	;
	
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
				return html+'<dt>'+key+'</dt><dd>'+(val||'<em>Empty</em>')+'</dd>';
			}, '') + '</dl>';

		// Render markup and render
		$(tpl(data)).modal();
	}

	// Return API
	return { open: open };

});