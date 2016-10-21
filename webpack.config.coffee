# Inspect how webpack is being run
minify = '-p' in process.argv # Compiling for production

# Deps
webpack      = require 'webpack'
autoprefixer = require 'autoprefixer'
moment       = require 'moment'
ExtractText	 = require 'extract-text-webpack-plugin'
_            = require 'lodash'

# Autoprefixer config
# https://github.com/ai/browserslist#queries
autoprefixerBrowsers = [
	'last 2 versions'
	'ie >= 10'
]

# Webpack configuration
config =

	# Set the dir to look for entry files
	context: "#{process.cwd()}/assets"

	# When minifying, use the autorunning entry point.	Otherwise, return the main
	# Decoy module that has a public API for configuration.
	entry: index: if minify then 'js/index.js' else 'js/decoy.js'

	# Where to put the files
	output:
		path:          "./dist"
		publicPath:    '/assets/decoy/'
		filename:      if minify then '[name].min.js' else '[name].js'
		chunkFilename: if minify then '[id].[hash:8].js' else '[id].js'

		# Make a UMD module
		library: 'decoy'
		libraryTarget: 'umd'
		umdNamedDefine: true

	# Setup properties that are set later (With less indentation)
	module: {}

	# Configure what shows up in the terminal output
	stats:
		children:     false # Hides the "extract-text-webpack-plugin" messages
		assets:       true
		colors:       true
		version:      false
		hash:         false
		timings:      true
		chunks:       false
		chunkModules: false

# Modules that shouldn't get parsed for dependencies.	These will probably be
# used in tandem with using aliases to point at built versions of the modules.
config.module.noParse = [

	# Using a built version of pixijs for Decoy to fix issues where it errors on
	# trying to use node's `fs`.	Another solution could have been to set
	# node: { fs: 'empty' }
	# https://github.com/pixijs/pixi.js/issues/1854#issuecomment-156074530
	# https://github.com/pixijs/pixi.js/issues/2078#issuecomment-137297392
	/bin\/pixi\.js$/
]


# ##############################################################################
# Resolve - Where to find files
# ##############################################################################
config.resolve =

	root: "#{process.cwd()}/assets"

	# Look for modules in the vendor directory as well as npm's directory.	The
	# vendor directory is used for third party modules that are committed to the
	# repo, like things that can't be installed via npm.	For example, Modernizr.
	modulesDirectories: ['workbench', 'vendor', 'node_modules']

	# Add coffee to the list of optional extensions
	extensions: ['', '.js', '.coffee', '.vue']

	# Aliases for common libraries
	alias:
		velocity: 'velocity-animate'
		underscore: 'lodash'

		# Decoy aliases
		bkwld: 'bkwld-js-library'
		bootstrap: 'bootstrap-sass'
		jcrop: 'jcrop-0.9.12'
		pixi: 'pixi.js/bin/pixi.js'
		'eventEmitter/EventEmitter': 'wolfy87-eventemitter'
		'bootstrap-timepicker': 'bootstrap-timepicker/js/bootstrap-timepicker'


# ##############################################################################
# Loaders - File transmogrifying
# ##############################################################################
config.module.loaders = [

	# Coffeescript #
	{ test: /\.coffee$/, loader: 'coffee-loader' }

	# HTML #
	{ test: /\.html$/, loader: 'html-loader' }

	# Images #
	# If files are smaller than the limit, becomes a data-url.	Otherwise,
	# copies the files into dist and returns the hashed URL.	Also runs imagemin.
	{
		test: /\.(png|gif|jpe?g|svg)$/
		loaders: [
			'url?limit=10000&name=img/[hash:8].[ext]'
			'img?progressive=true'
		]
	}

	# Fonts #
	# Not using the url-loader because serving multiple formats of a font would
	# mean inlining multiple formats that are unncessary for a given user.
	{
		test: /\.(eot|ttf|otf|woff|woff2)$/
		loader: 'file-loader?name=fonts/[hash:8].[ext]'
	}

	# JSON #
	{ test: /\.json$/, loader: 'json-loader' }

	# CSS #
	{
		test: /\.css$/
		loader:
			ExtractText.extract 'css-loader?-autoprefixer'
	}

	# Sass #
	{
		test: /\.scss$/
		loader:
			ExtractText.extract [
				'css-loader?sourceMap'
				'postcss-loader'
				'sass-loader?sourceMap'
				'import-glob-loader'
			].join('!')

	}

	# jQuery #
	# This adds jquery to window globals so that it's useable from the console
	# but also so that it can be found by jQuery plugins, like Velocity. This
	# "test" syntax is used to find the file in node_modules, the "expose"
	# loader's examples ("require.resolve") don't work because node is looking
	# in the app node_modules.
	{ test: /jquery\.js$/, loader: 'expose-loader?$!expose?jQuery' }

	# jQuery plugins #
	# Make sure jQuery is loaded before Velocity
	{
		test: /(velocity|redactor\/redactor)\.js$/,
		loader: 'imports-loader?$=jquery'
	}
]

# ############################################################################
# Plugins - Register extra functionality
# ############################################################################
config.plugins = [

	# Required config for ExtractText to tell it what to name css files. Setting
	# "allChunks" so that CSS referenced in chunked code splits still show up
	# in here. Otherwise, we would need webpack to DOM insert the styles on
	# which doesn't play nice with sourcemaps.
	new ExtractText (if minify then '[name].min.css' else '[name].css'),
		allChunks: true

	# Add some branding to all compiled JS files
	new webpack.BannerPlugin "📝 Bukwild 💾 #{moment().format('M.D.YY')} 👍"
]

# Minify only (`webpack -p`) plugins.
if minify then config.plugins = config.plugins.concat [

	# Turn off warnings during minifcation.	They aren't particularly helpfull.
	new webpack.optimize.UglifyJsPlugin compress: warnings: false

	# Make small ids
	new webpack.optimize.OccurenceOrderPlugin()

	# Try and remove duplicate modules
	new webpack.optimize.DedupePlugin()
]


# ##############################################################################
# Misc config - Mostly loader options
# ##############################################################################
_.assign config,

	# Configure autoprefixer using browserslist rules
	postcss: -> [ autoprefixer( browsers: autoprefixerBrowsers ) ]

	# Increase precision of math in SASS for Bootstrap
	# https://github.com/twbs/bootstrap-sass#sass-number-precision
	sassLoader: precision: 10


# ##############################################################################
# Export
# ##############################################################################
module.exports = config
