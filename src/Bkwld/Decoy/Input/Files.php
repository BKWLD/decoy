<?php namespace Bkwld\Decoy\Input;

// Dependencies
use App;
use Croppa;
use Exception;
use Input;
use Request;
use Str;

/**
 * Handling of file uploads from Decoy controllers
 */
class Files {
	
	/**
	 * Remove the required restriction if a file has already been uploaded
	 */
	public function preValidate($item) {

		// Get all the fields for this model instance that should have files
		foreach($this->fields($item) as $field) {
			
			// If the field is required but there is a new file being uploaded or we're
			// re-saving the previous file (the delete checkbox was not checked) then
			// remove the required validation
			if (!empty($rules)
				&& array_key_exists($field, $rules)
				&& Str::contains($rules[$column], 'required')
				&& (Input::hasFile($field) || Input::has('field'))) {
				
				// Delete the required validation and get rid of any double pipes or starting
				// or ending pipes that may have resulted from the str_replace
				$item::$rules[$column] = str_replace('required', '', $item::$rules[$field]);
				$item::$rules[$column] = str_replace('||', '|', $item::$rules[$field]);
				$item::$rules[$column] = trim($item::$rules[$field], '|');
			}
		}
	}
	
	/**
	 * Loop through all file fields and delete any files that are present in the old
	 * item instance and are being replaced by a new file or who have been deleted by
	 * checkbox (setting their value to empty).
	 */
	public function delete($item) {
		$all = Input::all();
		foreach($this->fields($item) as $field) {
			$old = $item->getOriginal($field);
			if (empty($old)) continue; // Nothing to delete found
			if (!array_key_exists($field, $all)) continue; // Not touching this file field (probably AJAX positioning)
			if (Input::hasFile($field) || !Input::has($field)) {

				// Try and delete the image with Croppa.  If Croppa won't delete, then
				// do a regular delete.  The croppa route is preferred because it will delete
				// all the crops that were found.
				if (Croppa::delete($old) === false) unlink(public_path().$old);
				
				// Remove crop data if it exits
				if (isset($item->{$field.'_crops'})) $item->{$field.'_crops'} = null;
				
			}
		}
	}
	
	/**
	 * Loop through all the files in the input and save out the files.
	 */
	public function save($item) {
		$fields = $this->fields($item);
		$files = App::make('request')->files;
		foreach($files->all() as $field => $file) {
			
			// If files isn't a file object, ignore it.  This may happen if there is a file input
			// field that is labeled like an array, i.e. <input name="some[1][thing]>".  In this case,
			// don't try to handle it.
			if (!is_a($file, 'SplFileInfo')) continue;

			// Require there to be an entry in the rules array for all files.  This will matter
			// when deleting later
			if (!in_array($field, $fields)) throw new Exception('A file was uploaded to "'.$field.'" but this was not added in the model $rules array as a file with an "image", "mimes", or "file" rule.  Decoy requires all files to have an entry in the $rules array.');
			
			// Double check there is data and not just a key
			if (!Input::hasFile($field)) continue; 
			
			// The base model has the logic that saves the file
			$item->$field = $item->saveFile($field);

			// Remove this file from the input, it's already been processed.  This prevents
			// other models that may be touched during the processing of this request (like because
			// of event handlers) from trying to act on this file
			$files->remove($field);
		}	
	}
	
	/**
	 * Get a list of the fields that have files by looking at validation rules
	 */
	private function fields($item) {
		$fields = array();
		foreach($item::$rules as $field => $rules) {
			if (preg_match('#file|image|mimes#i', $rules)) $fields[] = $field;
		}
		return $fields;
	}
}