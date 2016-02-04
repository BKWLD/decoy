<?php namespace Bkwld\Decoy\Controllers;

// Deps
use Illuminate\Routing\Controller;

/**
 * Actions that support Redactor WYSIWYG integration
 * http://imperavi.com/redactor/
 */
class Redactor extends Controller {

	/**
	 * Handle uploads of both images and files.  Relying on Decoy to enforce
	 * auth checks.
	 *
	 * @return array This array gets auto-converted to JSON by Laravel
	 */
	public function store() {
		return response()->json([
			'filelink' => app('upchuck.storage')->moveUpload(request()->file('file'))
		]);
	}

}
