# Customizing CSS & JS

To customize the JS or CSS for Decoy, you can build your own js and css file and then tell Decoy to load it by editing the `core.php` config file, changing the `stylesheet` and `script` paths.  [Here is an example](https://gist.github.com/weotch/153e5d6ab03b7c9f927e57562e8d2fe7) Webpack config.

## Add additional JS

The minified JS that Decoy ships with exposes it's internal jQuery, Backbone, and Lodash as properties of it's module.  For instance:

```coffee
# Load Decoy js from the public/assets directory where it was installed by
# `php artisan vendor:publish`
decoy = require '../../../public/assets/decoy/index'
{ $, _, Backbone } = decoy

# Prevent clicks of disabled buttons
$('.btn[disabled]').on 'click', (e) -> e.preventDefault()
```


## Customize the WYSIWYG

Decoy uses [Redactor](http://imperavi.com/redactor/) as its WYSIWYG editors.  Here is an example (in coffeescript and expecting to be built using Webpack) of how to change the Redactor config.  This script would be built into a standalone js file that would be referenced via editing the `script` value in the `core.php` config file.

```coffee
# Load Decoy js from the public/assets directory where it was installed by
# `php artisan vendor:publish`
decoy = require '../../../public/assets/decoy/index'

# Make a single stylesheet with Decoy and extended styles
require '../../../public/assets/decoy/index.css'
require './start.styl'

# Customize wysiwyg options
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
