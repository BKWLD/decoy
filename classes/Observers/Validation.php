<?php namespace Bkwld\Decoy\Observers;

// Deps
use Config;
use Bkwld\Decoy\Exceptions\ValidationFail;
use Illuminate\Validation\Validator;
use Log;
use Request;
use Redirect;
use Response;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Handle failed validations by redirecting or returning a special response
 */
class Validation {

	/**
	 * Massage validation handling
	 *
	 * @param Bkwld\Decoy\Models\Base $model
	 * @param Illuminate\Validation\Validator $validation
	 * @return void
	 */
	public function onValidating($model, $validation) {
		$this->allowValidatingOfExistingFiles($validation);
	}

	/**
	 * When a form is updated (as opposed to created) the previous files are
	 * strings and their mime validations would fail.  This creates file instances
	 * for them that can be validated
	 *
	 * @param Illuminate\Validation\Validator $validation
	 * @return void
	 */
	public function allowValidatingOfExistingFiles($validation) {

		// Only act on locally hosted files
		if (Config::get('upchuck.disk.driver') != 'local') return;

		// Get all the file related rules
		// https://regex101.com/r/oP4kD2/1
		$rules = array_filter($validation->getRules(), function($rules) {
			foreach($rules as $rule) {
				if (preg_match('#^image|file|video|mimes\:[\w,]+$#', $rule)) return true;
			}
		});

		// For each of the file rules, if the input has a value, make a file
		// instance for it if it's a local path.
		$files = $validation->getFiles();
		$data = $validation->getData();
		foreach($rules as $attribute => $rules) {

			// Skip if a file was uploaded for this attribtue or if the existing data
			// is undefined
			if (isset($files[$attribute]) || empty($data[$attribute])) continue;

			// Create the file instance and clear the data instance
			$data[$attribute] = new File(Config::get('upchuck.disk.path')
				.'/'.app('upchuck')->path($data[$attribute]));
		}

		// Replace the files and data with the updated set. `setData()` expects the
		// data to contain files in it.  But `getData()` strips out the files.  Thus,
		// they need to be merged back in before being set.
		$validation->setData(array_merge($files, $data));
	}

}
