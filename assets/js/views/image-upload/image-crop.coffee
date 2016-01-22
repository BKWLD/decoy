# --------------------------------------------------
# Crop tool for the image
# --------------------------------------------------
define (require) ->

	# Dependencies
  $ = require('jquery')
  _ = require('underscore')
  Backbone = require('backbone')
  require('jcrop')
  require('imagesloaded')

  # Define a backbone view for each image
  ImageCrop = Backbone.View.extend(

    jcrop: null
    activeCrop: false
    width: null
    height: null
    style: null
    ratio: undefined
    initted: false
    $input: null

    initialize: ->

      _.bindAll this
			@$upload = @$el.closest('.image-upload')
			@$crop = @$upload.find('.input-crop')
			@$focus = @$upload.find('.input-focal_point')
			@$cropTool = @$upload.find('.crop.btn')
			@$focusTool = @$upload.find('.focal.btn')

      @$el.parent('a').click (e) ->
        e.preventDefault()

          

      return

  )

  ImageCrop
