<?php namespace Bkwld\Decoy\Controllers;

// Deps
use Illuminate\Routing\Controller;
use Request;

/**
 * Actions that support Redactor WYSIWYG integration
 * http://imperavi.com/redactor/
 */
class Redactor extends Controller {

	/**
	 * Handle uploads of both images and files.  Relying on Decoy to enforce
	 * auth and csrf checks.
	 *
	 * @return array This array gets auto-converted to JSON by Laravel
	 */
	public function upload() {
		return ['filelink' => app('upchuck.storage')->moveUpload(Request::file('file'))];	
	}

}