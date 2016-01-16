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
      @$file = @$el.find('.file')
      @$holder = @$el.find('.image-holder')
      @$imagePreview = @$holder.find('.img-thumbnail')

      # Listener
      @$file.on 'change', @onImageChange

      return

    onImageChange: (input) ->

      if @$file[0].files and @$file[0].files[0]
        reader = new FileReader

        reader.onload = (e) =>

          @$file.addClass('hidden')
          @$holder.addClass('visible')
          @$imagePreview.attr 'src', e.target.result
          return

        reader.readAsDataURL @$file[0].files[0]

      return
  )

  Preview
