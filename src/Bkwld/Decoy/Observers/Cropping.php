<?php namespace Bkwld\Decoy\Observers;

/**
 * Cleanup old cropping data if an image is changed
 * or removed
 */
class Cropping {

	/**
	 * Called on model saving
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 */
	public function handle($model) {

		// Loop through all file attributes
		foreach($model->file_attributes as $attribute) {
			$crops = $attribute.'_crops';

			// If a file_attribtue is changed and has crops data, null it.
			// This will come after Upchuck touches the attribute because
			// Upchuck has a higher listener priority.
			if ($model->isDirty($attribute) && isset($model->$crops)) {
				$model->$crops = null;
			} 
		}
	}

}