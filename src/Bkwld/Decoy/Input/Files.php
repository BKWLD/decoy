<?php namespace Bkwld\Decoy\Input;

use Croppa;
use Input;
use Request;
use Str;

/**
 * Handling of file uploads from Decoy controllers
 */
class Files {
	
	/**
	 * Files in an edit state have a number of supplentary fields.  This
	 * prepares the file for validation.  This should only be called during
	 * the handling of an @update request
	 * @param array $rules
	 */
	public function preValidateFiles($rules) {
		
		// Loop through the input and and look for certain input fields by checking for other inputs
		// with specific suffices.
		foreach(Input::get() as $field => $value) {
			
			// Check if this field is for a file.  All files on edit view have an 'old-*' hidden field
			if (!Str::is(UPLOAD_OLD.'*', $field)) continue;
			$column = substr($field, 4);
			
			// If someone has said to delete a file, do nothing.  Input will look like
			// there is no input for this file.  Required validators can do their thing
			if (Input::has(UPLOAD_DELETE.$column)) continue;
			
			// If someone has uploaded a new file, use it's value as the field and continue.
			if (Input::hasFile(UPLOAD_REPLACE.$column)) {
				$this->moveReplaceFileInput($column);
				continue;
			}

			// The user has not specified to delete and has not uploaded a file (these conditions would be
			// caught above and the code wouldn't have reached this point).  If the field
			// is required, there must have been one uploaded originally.  So skip the required
			// validation.  We don't want to just use the old value because the validator would still
			// look for a file possibly and we'd be passing a single string.  Only files have an 'old-*'
			// field so checking for it is equivalent to seeing looking for a file
			if (!empty($rules)
				&& array_key_exists($column, $rules)
				&& strpos($rules[$column], 'required') !== false) {
				
				// Delete the required validation and get rid of any double pipes or starting
				// or ending pipes that may have resulted from the str_replace
				$rules[$column] = str_replace('required', '', $rules[$column]);
				$rules[$column] = str_replace('||', '|', $rules[$column]);
				$rules[$column] = trim($rules[$column], '|');
			}
		}
	}
	
	/**
	 * On edit pages, the file input for replacing a file is labeled like
	 * "replace-COLUMN" (i.e. replace-image) so that it plays nice with 
	 * valiations with require and Former.  This function takes the data from
	 * the replace-* input and moves it to the more expected, just column name
	 * parameter of the FILES array.
	 * @param string $column The column name (i.e. 'image', not 'replace-image')
	 */
	public function moveReplaceFileInput($column) {
		if (!array_key_exists(UPLOAD_REPLACE.$column, $_FILES)) return;
		$_FILES[$column] = $_FILES[UPLOAD_REPLACE.$column];
		unset($_FILES[UPLOAD_REPLACE.$column]);
	}
	
	/**
	 * Loop through inputs looking for checked-boxes for deleting a file.
	 * If found, act.  For some documentation on some of these lines, check out
	 * preValidateFiles() which bares similarities
	 * 
	 */
	public function deleteFiles(&$item) {
		foreach(Input::get() as $field => $value) {
			
			// If there is a delete checkbox and it has a value, that means it was clicked
			if (!(Str::is(UPLOAD_DELETE.'*', $field) && Input::get($field))) continue;
			$column = substr($field, 7);
			
			// Remove the file and unset the column in the model instance
			if (!empty($item->$column)) Croppa::delete($item->$column);
			$item->$column = null;
		}
	}
	
	/**
	 * Loop through all the files in the input and save out the files
	 */
	public function saveFiles(&$item) {
		foreach($_FILES as $column => $file_data) {
			if (!Input::hasFile($column)) continue;
			
			// Delete the old file, if it exists
			if (!empty($item->$column)) Croppa::delete($item->$column);
			
			// Save the incoming file out
			$item->$column = $item->saveFile($column);
		}	
	}

	/**
	 * On edit pages with file inputs, there are some extra fields that should be
	 * stripped out of the input so that they don't confuse mass assignment
	 * 
	 */
	public function unsetFileEditInputs() {
		$input = Input::get();
		foreach($input as $field => $value) {
			
			// Check if this field is for a file.  All files on edit view have an 'old-*' hidden field
			if (!(Str::is(UPLOAD_OLD.'*', $field))) continue;
			$column = substr($field, 4);
			
			// Remove the columns that don't exist in the db
			unset($input[UPLOAD_OLD.$column]);
			unset($input[UPLOAD_DELETE.$column]);
			unset($input[UPLOAD_REPLACE.$column]);
		}	
		
		// Replace the Input::get() with the new values
		Input::replace($input);
	}
	
}