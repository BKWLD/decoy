<?php namespace Bkwld\Decoy\Observers;

// Deps
use Croppa;

/**
 * Cleanup old cropping data if an image is changed or removed
 */
class Cropping {

	/**
	 * @var string Image regex
	 */
	const REGEX = '#\.(gif|jpe?g|png)$#i';

	/**
	 * Delete crop coordinates and Croppa crops
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 */
	public function onSaving($model) {

		// Don't act if no file attributes
		if (empty($model->file_attributes)) return;

		// Loop through all file attributes
		foreach($model->file_attributes as $attribute) {
			if (!$model->isDirty($attribute)) continue;

			// If there are crop coordinates, null them. This will come after Upchuck 
			// touches the attribute because Upchuck has a higher listener priority.
			$crops = $attribute.'_crops';
			if (isset($model->$crops)) $model->$crops = null;

			// Delete Croppa crops.  Upchuck will take care of the src image.
			$this->reset($model->getOriginal($attribute));
		}
	}

	/**
	 * Delete Croppa crops
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return void 
	 */
	public function onDeleted($model) {
		foreach($model->file_attributes as $attribute) {
			$this->reset($model->$attribute);
		}
	}

	/**
	 * Delete Croppa crops if the file attribute looks like an image.  On error,
	 * silently fail.
	 *
	 * @param string $url
	 * @return void 
	 */
	public function reset($url) {
		if (!$url) return;
		if (preg_match(self::REGEX, $url)) {
			try { Croppa::reset($url); }
			catch (\Exception $e) {}
		}
	}

}