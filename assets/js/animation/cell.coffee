define (require) ->

	# Dependencies
	$ = require "jquery"
	_ = require "underscore"
	Backbone = require "backbone"
	PIXI = require "pixi"
	chroma = require "chroma-js"

	# Init view
	Cell = {}

	# Constructor
	Cell.initialize = (options) ->
		_.bindAll this

		###
				properties
		###
		@isChanging = false
		@pos =
			x: options.x
			y: options.y
		@size = options.size
		@brightness = options.brightness || 15
		@color = options.color.brighter(@brightness)
		@flashSpeedIn = options.flashSpeedIn || 0.01
		@flashSpeedOut = options.flashSpeedOut || 0.02

		###
				building the pixi object needed to render
				###
		@graphics = new PIXI.Graphics()
		@graphics.alpha = 0

		###
				used to determine if the alpha anim is going up or coming down
		###
		@rising = true;

		###
				when dead, the cell will be cut out and garbage collected from the manager
		###
		@dead = false
		@render()

		return

	###
		draw the cell to the screen
	###
	Cell.render = ->
		@graphics.clear()
		@graphics.beginFill @stringToColor(@color)
		@graphics.drawRect @pos.x, @pos.y, @size, @size
		@graphics.endFill()
		return

	###
		update the animation color/alpha. called each from from the manager
	###
	Cell.update = ->
		@changeColor()
		@render()
		return

	###
		util function to strip the # from a string and convert hex characters to color int
	###
	Cell.stringToColor = (chromaColor) ->
		return parseInt('0x'+chromaColor.hex().replace('#',''))

	###
		animation logic for each cell
	###
	Cell.changeColor = ->
		if @graphics.alpha < 1 && @rising
			@graphics.alpha += @flashSpeedIn
		else if @graphics.alpha >= 1
			@rising = false
			@graphics.alpha -= @flashSpeedOut
		else if @rising == false
			@graphics.alpha -= @flashSpeedOut
			if @graphics.alpha <= 0
				@dead = true
		return

	###
		garbage collection for the cell when removed from the stage
	###
	Cell.close = ->
		@graphics.clear();
		@remove()
		@unbind()
		return

	# Return the view
	Backbone.View.extend Cell
