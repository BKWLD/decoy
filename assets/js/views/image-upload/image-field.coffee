define (require) ->

  # Dependencies
  $ = require "jquery"
  _ = require "lodash"
  Backbone = require "backbone"
  Preview = require "./preview"
  Crop = require "./crop"

  # Init view
  View =
    initialize: (options) ->
      _.bindAll @

      @preview = new Preview { el: @el, parent: @ }
      @crop = new Crop { el: @$('.img-thumbnail') }

      @preview.on 'previewImage', @onPreviewImage
      @preview.on 'deleteImage', @onDeleteImage

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

  return Backbone.View.extend View
