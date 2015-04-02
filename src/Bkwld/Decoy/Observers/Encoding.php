<?php namespace Bkwld\Decoy\Observers;

/**
 * Trigger encoding or delete the encodings rows
 */
class Encoding {

	/**
	 * Start a new encode if a new encodable file was uploaded
	 * 
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return void 
	 */
	public function onSaving($model) {
		if (!$this->isEncodable($model)) return;
		foreach($this->getDirtyEncodableAttributes($model) as $attribute) {

			// If the attribute has a value, encode the attribute
			if (isset($model->$attribute)) $model->encodeOnSave($attribute);

			// Otherwise delete encoding references
			else $model->encoding($attribute)->delete();
		}
	}

	/**
	 * Delete all encodes on the model
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return void 
	 */
	public function onDeleted($model) {
		if (!$this->isEncodable($model)) return;
		$model->deleteEncodings();
	}

	/**
	 * Get all the attributes on a model who support video encodes
	 * and are dirty
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return array 
	 */
	public function getDirtyEncodableAttributes($model) {
		$attributes = [];
		foreach($model::$rules as $attribute => $rule) {
			if (preg_match('#video:encode#i', $rule) && $model->isDirty($attribute)) {
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	/**
	 * Check if a model is using the encodable trait
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return boolean 
	 */
	public function isEncodable($model) {
		return in_array('Bkwld\Decoy\Models\Traits\Encodable', class_uses($model));
	}

}