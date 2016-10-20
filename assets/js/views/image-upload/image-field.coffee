# Dependencies
$ = require "jquery"
_ = require "lodash"
Backbone = require "backbone"
Preview = require "./preview"
Crop = require "./crop"

module.exports = Backbone.View.extend

	initialize: (options) ->
		_.bindAll @

		@preview = new Preview { el: @el, parent: @ }
		@crop = new Crop { el: @$('img.source') }

		@preview.on 'previewImage', @onPreviewImage
		@preview.on 'deleteImage', @onDeleteImage

		# If on Elements, (or any Boostrap tab UI), try to re-init jcrop when
		# switching pages. I'm listening to Boostrap's events here.
		$('a[data-toggle="tab"]').on 'shown.bs.tab', (e) => @crop.init()
		return

	onPreviewImage: () ->
		@crop.destroy()
		@crop.initialize()
		return

	onDeleteImage: () ->
		@crop.destroy()
		@crop.initialize()
		@crop.clear()
		return
