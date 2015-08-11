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
		this.$currently = this.$help.find('.download, .upload-delete');

		// Start querying for updates
		if (this.$status.length) this.query();

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
		this.$status.remove();
		this.$currently.remove();
		if (this.$help.length) this.$help.after(tag);
		else this.$file.after(tag);
	};

	// Update progress
	View.renderProgress = function(status, progress) {
		this.$bar.text(status).css('width', progress+'%');
	};
	
	// Return view class
	return Backbone.View.extend(View);
});