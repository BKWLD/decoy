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
	 * The different rules that all imply files
	 */
	const RULES = 'file|image|mimes|video';

	/**
	 * Massage validation rules when editing a file
	 */
	public function preValidate($item) {

		// Get all the fields for this model instance that should have files
		foreach($item->file_attributes as $field) {
			
			// If the field has a value but not a file, then we can assume that we're
			// re-saving the page with no image changes.  Since the incoming value for the
			// field is a simple string, remove the mime validations
			if (Input::has($field) && !Input::hasFile($field)) {
				$item::$rules[$field] = preg_replace('#('.self::RULES.')[^|]*#i', '', $item::$rules[$field]);

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
	 *
	 * @param Illuminate\Database\Eloquent\Model $item 
	 * @param boolean $force Bypass many of the checks and definitely delete if it's found
	 */
	public function delete($item, $force = false) {
		$all = Input::all();
		foreach($item->file_attributes as $field) {
			$old = $item->getOriginal($field);

			// Nothing to delete found
			if (empty($old)) continue;

			// Not touching this file field (probably AJAX positioning)
			if (!array_key_exists($field, $all) && !$force) continue;

			// Delete if a new file was uploaded or there it was cleared or if we're being
			// forced (like if the model was deleted)
			if (Input::hasFile($field) || !Input::has($field) || $force) {

				// Delete the file using method on the base model
				$item->deleteFile($old);
				
				// Remove crop data if it exits
				if (isset($item->{$field.'_crops'})) $item->{$field.'_crops'} = null;
				
			}
		}
	}
	
	/**
	 * Loop through all the files in the input and save out the files.
	 */
	public function save($item) {
		$fields = $item->file_attributes;
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
}