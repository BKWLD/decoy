# WYSIWYG

Decoy uses [Redactor](http://imperavi.com/redactor/) as its WYSIWYG editors.  To customize the editor, you can get a reference to the wysiwyg adapter from your /js/admin/start.js and customize their config like:

```js
// Redactor - Enable uploads and add "format" options
wysiwyg = require('decoy/assets/js/wysiwyg/factory')
wysiwyg.config.allowUploads();
wysiwyg.config.merge({
	buttons: ['formatting', 'bold', 'italic', 'link', 'file', 'image', 'horizontalrule', 'orderedlist', 'unorderedlist', 'html'],
	formatting: ['p', 'h2']
});
```
