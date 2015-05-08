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
	 * Check if a model should be encoded
	 *
	 * @param Bkwld\Decoy\Models\Base $model 
	 * @return boolean 
	 */
	public function isEncodable($model) {
		if (!method_exists($model, 'getDirtyEncodableAttributes')) return false;
		if (is_a($model, 'Bkwld\Decoy\Models\Element') 
			&& $model->getAttribute('type') != 'video-encoder') return false;
		return true;
	}

}