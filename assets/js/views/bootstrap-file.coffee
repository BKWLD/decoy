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

		# Wrap the file
		@$el.wrap('<div class="input-group bootstrap-file"></div>')
		.wrap('<span class="input-group-btn"></span>')
		.wrap('<span class="btn btn-primary btn-file">Browse&hellip; </span>')

		# Add the filename readonly textfield
		@$filename = $('<input type="text" class="form-control" readonly>')
		.appendTo(@$el.closest('.input-group'))

	# When the user selects a file, update the filename preview
	onChange: ->
		name = @$el.val().replace(/\\/g, '/').replace(/.*\//, '')
		@$filename.val name
