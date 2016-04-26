###
Populate the filename fields for the custom file inputs.  This was done so they
look consistent across browsers and more like the rest of the inputs. This is
based on:

http://www.abeautifulsite.net/whipping-file-inputs-into-shape-with-bootstrap-3/
###

# Deps
$ = require 'jquery'
_ = require 'lodash'
Backbone = require 'backbone'

# Return the View class
module.exports = Backbone.View.extend

	# Constructor
	initialize: ->
		_.bindAll @

		# Create new markup
		@render()

		# Add listeners and do initial population
		@$el.on 'change', @onChange
		@onChange()

	# Wrap the elemnt in bootstrap styling classes
	render: ->

		# Check if there are any input-group-btns that follow the element.  They
		# might exist if the Field render adds them.  The VideoEncoder does this.
		$btns = @$el.nextAll('.input-group-btn')

		# Wrap the file
		@$el.wrap('<div class="input-group bootstrap-file"></div>')
		.wrap('<span class="input-group-btn"></span>')
		.wrap('<span class="btn btn-default btn-file">Browse&hellip; </span>')
		$inputGroup = @$el.closest('.input-group')

		# Add the filename readonly textfield
		@$filename = $('<input type="text" class="form-control" readonly>')
		.appendTo($inputGroup)

		# Add other btns that may have been rendered by php
		$inputGroup.append($btns)

	# When the user selects a file, update the filename preview
	onChange: (e) ->

		# The image UI will clone the element and replace it to clear it's value.
		# This updates this view with the new element
		@setElement(e.currentTarget) if e?.currentTarget and @el != e.currentTarget

		# Update the name
		name = @$el.val().replace(/\\/g, '/').replace(/.*\//, '')
		@$filename.val name
