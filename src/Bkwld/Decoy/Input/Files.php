<?php namespace Bkwld\Decoy\Input;

// Dependencies
use App;
use Bkwld\Decoy\Models\Encoding;
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
	 * Massage validation rules when editing a file
	 */
	public function preValidate($item) {

		// Get all the fields for this model instance that should have files
		foreach($this->fields($item) as $field) {
			
			// If the field has a value but not a file, then we can assume that we're
			// re-saving the page with no image changes.  Since the incoming value for the
			// field is a simple string, remove the mime validations
			if (Input::has($field) && !Input::hasFile($field)) {
				$item::$rules[$field] = preg_replace('#(image|mimes|video)[^|]*#', '', $item::$rules[$field]);

				// Cleanup extra pipes
				$item::$rules[$field] = preg_replace('#\|{2,}#', '|', $item::$rules[$field]);
				$item::$rules[$field] = trim($item::$rules[$field], '|');
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

				// If the file has an image suffix, use Croppa to delete
				if (Str::endsWith($old, array('jpg', 'jpeg', 'gif', 'png', 'bmp'))) Croppa::delete($old);

				// Otherwise, do a normal delete
				elseif (file_exists(public_path().$old)) unlink(public_path().$old);
				
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
			if (!in_array($field, $fields)) throw new Exception('A file was uploaded to "'.$field.'" but this was not added in the model $rules array as a file with an "image", "mimes", "video", or "file" rule. Decoy requires all files to have an entry in the $rules array.');
			
			// Double check there is data and not just a key
			if (!Input::hasFile($field)) continue; 
			
			// The base model has the logic that saves the file
			$item->$field = $item->saveFile($field);

			// Remove this file from the input, it's already been processed.  This prevents
			// other models that may be touched during the processing of this request (like because
			// of event handlers) from trying to act on this file
			$files->remove($field);

			// If the validation rules include a request to encode a video, add it to the encoding queue
			if (Str::contains($item::$rules[$field], 'video:encode') 
				&& method_exists($item, 'encodeOnSave')) $item->encodeOnSave($field);
			
		}	
	}
	
	/**
	 * Get a list of the fields that have files by looking at validation rules
	 */
	private function fields($item) {
		$fields = array();
		foreach($item::$rules as $field => $rules) {
			if (preg_match('#file|image|mimes|video#i', $rules)) $fields[] = $field;
		}
		return $fields;
	}
}