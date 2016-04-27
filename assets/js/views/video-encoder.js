define(function (require) {

	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
	;

	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);

		// Cache
		this.encode_id = this.$el.data('encode');
		this.$file = this.$(':file');
		this.$status = this.$('.status');
		this.$progress = this.$status.find('.progress');
		this.$bar = this.$progress.find('.progress-bar');
		this.$help = this.$('.help-block');
		this.$presetLabel = this.$('.dropdown-toggle .selected');
		this.$presetValue = this.$('[name^="_preset"]');
		this.$presetChoices = this.$('.presets a');

		// Start querying for updates
		if (this.$status.length) this.query();

		// Listen for preset changes
		this.$presetChoices.on('click', this.onPresetChange);
		this.updatePresetLabel();

	};

	// Query the server for updated status and progress.  But throttled.
	View.query = _.throttle(function() {
		$.get('/admin/encode/'+this.encode_id+'/progress', this.render);
	}, 4000);

	// Update the view with latest status
	View.render = function(data) {
		if (data.status == 'error' || data.status == 'canceled') this.renderError(data.message);
		else if (data.status == 'complete') this.renderPlayer(data.admin_player);
		else {
			this.renderProgress(data.status, data.progress);
			this.query();
		}
	};

	// Show an error
	View.renderError = function(message) {
		this.$status.remove();
		this.$el.addClass('has-error');
		this.$file.after('<span class="help-block">'+message+'</span>');
	};

	// Show the player
	View.renderPlayer = function(tag) {
		this.$status.after(tag);
		this.$status.remove();
	};

	// Update progress
	View.renderProgress = function(status, progress) {
		this.$bar.text(status).css('width', progress+'%');
	};

	// Handle preset changes
	View.onPresetChange = function(e) {
		var preset = $(e.currentTarget).data('val');
		this.$presetValue.val(preset);
		this.updatePresetLabel();
		this.$('.dropdown-toggle').dropdown('toggle');
	}

	// Update the the preset label using the current value
	View.updatePresetLabel = function() {
		var preset = this.$presetValue.val(),
			title = _.trim(this.$presetChoices.filter('[data-val="'+preset+'"]').text())
		this.$presetLabel.text(title);
	};

	// Return view class
	return Backbone.View.extend(View);
});
