define (require) ->


	# parameterize allllllll options with comments
	# listen to resize for resetting
	# set size on reinit to parent container


	# Dependencies
	$ = require "jquery"
	_ = require "underscore"
	Backbone = require "backbone"
	PIXI = require "pixi"
	chroma = require "chroma-js"
	Cell = require "./cell"
	requestAnimationFrame = require "raf" # Polyfill

	# Init view
	DecoyAnim = {}

	# Constructor
	DecoyAnim.initialize = (options) ->
		_.bindAll this
		@$parent = @$el
		@$parent.css 'overflow', 'hidden'

		###
				OPTIONS
				================
				el              (jquery selector string|no default) must be defined in the options,
												this is the parent container for the animation
		squareSize      (int|20) - the pixel size of each square grid cell
		baseColor       (string|'#67bfb6') - the base color of the screen
		spawnRate       (int|3) - the number of frames before a cell spawn trigger
		cellRate        (int|3) - the number of animating cells to spawn on the trigger
		cellBrightness  (int|1) - this amount to brighten the animating cells from the base color
		colorRange      (int|0.5) - the depth of variation in the random grid background
		flashSpeedIn    (float|0.01) - incremental alpha change in the cell animation
		flashSpeedOut   (float|0.02) - decremental alpha change in the cell animation
				###
		window.anim = @
		@squareSize = options.size || 20
		@baseColor = chroma(options.color || '#67bfb6')
		@spawnRate = options.spawnRate || 3
		@cellRate = options.cellRate || 3
		@cellBrightness = options.cellBrightness || 1
		@colorRange = options.colorRange || 0.5
		@flashSpeedIn = options.flashSpeedIn || 0.01
		@flashSpeedOut = options.flashSpeedOut || 0.02

		###
			stores the currently animating cells
		###
		@cells = []

		###
				used to count frames for cell spawning
		###
		@frameCount = 0

		###
				Used to pause hte animations
				###
		@paused = false

		###
			build the stage and setup of the pixi renderer
		###
		@stage = new PIXI.Stage 0xFFFFFF, true
		@stage.interactive = true
		@graphics = new PIXI.Graphics()
		@renderer = PIXI.autoDetectRenderer @$parent.width(), @$parent.height(), null, true, true
		@renderer.view.id = "decoy-animation"
		@$canvas = $ '#' + @renderer.view.id
		@resetAnimation()
		@$parent.append @renderer.view

		###
				kick off the animation loop
		###
		requestAnimationFrame @animate

		###
				listen to resizing events
				###
		$(window).on('resize', _.throttle( @resetAnimation, 200))

		return

	###
		Paints the random grid to the entire parent container
	###
	DecoyAnim.buildGrid = ->
		for x in [0..@count.x]
			for y in [0..@count.y]
				@graphics.beginFill parseInt('0x'+@baseColor.darker(Math.random()*@colorRange).hex().replace('#',''))
				@graphics.drawRect @squareSize * x, @squareSize * y, @squareSize, @squareSize
				@graphics.endFill()
		@stage.addChild @graphics
		return

	###
	Animation loop updates cell animation rendering. Some garbage collection happens here.
		When the cells are finished animating, the cell is pulled from the array, the backbone view
		is destroyed, and the cell is nulled out.
	###
	DecoyAnim.animate = ->
		@checkMakeCell()
		@renderer.render @stage
		for cell in [@cells.length-1..0] by -1
			@cells[cell].update()
			if @cells[cell].dead
				deadcell = (@cells.splice cell, 1)[0]
				@graphics.removeChild deadcell.graphics
				deadcell.close()
				deadcell = null

		if !@paused
			requestAnimationFrame @animate

		return

	###
		Determine is cells should be spawn for this frame. If so, create the number of cells
		specified in the cellRate options
	###
	DecoyAnim.checkMakeCell = ->
		@frameCount++
		if @frameCount >= @spawnRate
			@frameCount = 0
			for i in [1..@cellRate]
				@makeCell()
		return

	###
		Creates an animating cells randomly on the grid and adds it to the array
	###
	DecoyAnim.makeCell = ->
		cell = new Cell
			x: Math.floor(Math.random()*@count.x) * @squareSize
			y: Math.floor(Math.random()*@count.y) * @squareSize
			size: @squareSize
			color: @baseColor
			brightness: @cellBrightness
			flashSpeedIn: @flashSpeedIn
			flashSpeedOut: @flashSpeedOut

		@graphics.addChild cell.graphics
		cell.render()
		@cells.push cell
		return

	###
		Pause the animation
	###
	DecoyAnim.pause = ->
		@paused = true
		return

	###
	Play the animation
	###
	DecoyAnim.play = ->
		@paused = false
		requestAnimationFrame @animate
		return

	###
		sets all current cells to 'dead', which will remove them in the next animation frame
		###
	DecoyAnim.killAllCells = ->
		if @cells.length > 0
			for cell in [0..@cells.length-1]
				@cells[cell].dead = true
		return

	###
		resets the canvas size and clears the grid. this happens on window resize, too
		###
	DecoyAnim.resetAnimation = ->
		@graphics.clear()
		@killAllCells()

		###
				the number of cells to paint based on the parent container's size
		###
		@count =
			x: Math.ceil(@$parent.width() / @squareSize)
			y: Math.ceil(@$parent.height() / @squareSize)

		###
			paint the random grid initially, once
		###
		@buildGrid()

		@renderer.resize @$parent.width(), @$parent.height()
		@$canvas.css 'width', @$parent.width()
		@$canvas.css 'height', @$parent.height()

		#set the canvas width and height to fill the screen
		@renderer.view.style.display = "block"
		@renderer.view.style.position = "absolute"
		@renderer.view.style.top = 0
		@renderer.view.style.bottom = 0
		@renderer.view.style.left = 0
		@renderer.view.style.right = 0


		return

	# Return the view
	Backbone.View.extend DecoyAnim
