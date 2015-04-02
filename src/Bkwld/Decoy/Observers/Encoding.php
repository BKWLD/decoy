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
		foreach($model->getDirtyEncodableAttributes() as $attribute) {

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
	 * Check if a model is using the encodable trait
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return boolean 
	 */
	public function isEncodable($model) {
		return in_array('Bkwld\Decoy\Models\Traits\Encodable', class_uses($model));
	}

}