# --------------------------------------------------
# Preview the selected image to upload
# --------------------------------------------------
define (require) ->

	# Dependencies
  $ = require('jquery')
  _ = require('underscore')
  Backbone = require('backbone')

  # Define a backbone view for each image
  Preview = Backbone.View.extend(

    initialize: ->

      _.bindAll this
      @$file = @$el.find('[type="file"]')
      @$holder = @$el.find('.image-holder')
      @$imagePreview = @$holder.find('.img-thumbnail')
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
      @$imagePreview.attr 'src', ''
      @$el.removeClass 'has-image'
      @$file.removeClass 'hidden'
      @$file.replaceWith @$file = @$file.clone(true)
      @trigger 'deleteImage'

      return

  )

  Preview
