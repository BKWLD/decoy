# Customizing CSS & JS

To customize the JS or CSS for Decoy, you can build your own js and css file and then tell Decoy to load it by editing the `core.php` config file, changing the `stylesheet` and `script` paths.

## Customize the WYSIWYG

Decoy uses [Redactor](http://imperavi.com/redactor/) as its WYSIWYG editors.  Here is an example (in coffeescript and expecting to be built using Webpack) of how to change the Redactor config.  This script would be built into a standalone js file that would be referenced via editing the `script` value in the `core.php` config file.

```coffee
# Deps
decoy = require 'decoy'

# Make a single stylesheet with Decoy and extended styles
require 'decoy/dist/index.css'
require './start.scss'

# Customize wysiwyg options
require './fontcolor.js'
decoy.wysiwyg.config.allowUploads()
decoy.wysiwyg.config.merge({
	plugins: ['fontcolor'],
	buttons: ['formatting', 'bold', 'italic', 'link', 'file', 'image', 'horizontalrule', 'orderedlist', 'unorderedlist', 'html'],
	formatting: ['p', 'h2', 'h3', 'blockquote'],
	formattingAdd: [
		{
			tag: 'p',
			title: 'Full Width Image',
			class: 'full-width'
		},
		{
			tag: 'h3',
			title: 'Header 2 subtitle',
			class: 'subtitle'
		}
	],
})

# Start up decoy
decoy.init()
```
