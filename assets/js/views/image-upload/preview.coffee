# --------------------------------------------------
# Preview the selected image to upload
# --------------------------------------------------

# Dependencies
$ = require('jquery')
_ = require('underscore')
Backbone = require('backbone')

module.exports = Backbone.View.extend

	initialize: ->
		_.bindAll @

		@$file = @$el.find('[type="file"]')
		@$holder = @$el.find('.image-holder')
		@$imagePreview = @$holder.find('.source')
		@$delete = @$el.find('.delete')

		# Listener
		@$file.on 'change', @onImageChange
		@$delete.on 'click', @onDelete
		return

	onImageChange: (input) ->
		if @$file[0].files and @$file[0].files[0]
			reader = new FileReader

			reader.onload = (e) =>
				@$file.addClass 'hidden'
				@$el.addClass 'has-image'
				@$imagePreview.attr 'src', e.target.result
				@trigger 'previewImage'

				return

			reader.readAsDataURL @$file[0].files[0]
		return

	onDelete: () ->

		# Clear the old preview
		@$imagePreview.attr 'src', ''
		@$el.removeClass 'has-image'
		@$file.removeClass 'hidden'

		# Replace the file input with a clone because you can't clear a file field
		@$file.replaceWith @$file = @$file.clone(true)
		@$file.trigger 'change'
		@$file.prop('required', true) if @$file.hasClass('required')

		# Add a hidden field of the same name that tells Decoy to delete the
		# previous.
		$('<input type="hidden" value="">')
			.attr('name', @$file.attr('name'))
			.insertBefore(@$file)

		@trigger 'deleteImage'
		return
