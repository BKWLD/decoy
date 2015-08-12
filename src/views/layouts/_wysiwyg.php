<? 
/**
 * Add the JS and CSS for the WYSYIWYG editor.  This is kept a seperate script so
 * that we don't compile both when only one at a time is ever used.
 */

switch(config('decoy.wysiwyg.vendor')) {

	case 'ckeditor': ?>

		<script src="/packages/bkwld/decoy/ckeditor/ckeditor.js"></script>
		<script src="/packages/bkwld/decoy/ckfinder/ckfinder.js"></script>

	<? break; 
	case 'redactor':
	default:  ?>

		<link rel="stylesheet" href="/packages/bkwld/decoy/redactor/redactor.css" />
		<script src="/packages/bkwld/decoy/redactor/redactor.min.js"></script>

<? } ?>
