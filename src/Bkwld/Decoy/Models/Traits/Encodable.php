<?php namespace Bkwld\Decoy\Models\Traits;

// Dependencies
use Bkwld\Decoy\Models\Encoding;

/**
 * Mix this into models that join to the Encoding model to
 * add the Laravel relationship and add helper methods
 */
trait Encodable {

	/**
	 * Define a `private $encodable_attributes` property like:
	 *
	 * 		private $encodable_attributes = [
	 * 			'video',
	 * 		];
	 */

	/**
	 * Polymorphic relationship definition
	 *
	 * @return Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function encodings() { 
		return $this->morphMany('Bkwld\Decoy\Models\Encoding', 'encodable'); 
	}

	/**
	 * Find the encoding for a given database field
	 * 
	 * @param  string $field
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function encoding($field = 'video') {
		return $this->encodings()->where('encodable_attribute', '=', $field)->first();
	}

	/**
	 * Get all the attributes on a model who support video encodes and are dirty
	 *
	 * @return array 
	 */
	public function getDirtyEncodableAttributes() {
		if (empty($this->encodable_attributes)) return [];
		return array_filter($this->encodable_attributes, function($attribute) {
			return $this->isDirty($attribute);
		});
	}
			
	/**
	 * A utitliy function to create status badges for Decoy listings
	 *
	 * @return string HTML
	 */
	public function adminColEncodeStatus() {
		if (!$encode = $this->encoding()) return '<span class="label">Pending</span>';
		switch($encode->status) {
			case 'pending': return '<span class="label">'.ucfirst($encode->status).'</span>';
			case 'error':
			case 'cancelled': return '<span class="label label-important">'.ucfirst($encode->status).'</span>';
			case 'queued':
			case 'processing': return '<span class="label label-info">'.ucfirst($encode->status).'</span>';
			case 'complete': return '<span class="label label-success">'.ucfirst($encode->status).'</span>';
		}
	}

	/**
	 * Create an encoding instance which, in affect, begins an encode.  This 
	 * should be invoked before the model is saved.  For instance, from saving() 
	 * handler
	 *
	 * @param  string $attribute The name of the attribtue on the model that 
	 *                           contains the source for the encode
	 * @return void
	 */
	public function encodeOnSave($attribute) {

		// Preserve the key for the saved callback.  It's a mystery to me why, but
		// when Elements are being saved, the key would become '0' between here
		// and the `saved()` callback.
		$key = $this->getKey();

		// Create a new encoding model instance. It's callbacks will talk to the 
		// encoding provider. Save it after the model is fully saved so the foreign 
		// id is available for the  polymorphic relationship.
		$this->saved(function($model) use ($attribute, $key) {

			// Make sure that that the model instance handling the event is the one
			// we're updating.
			if ($this != $model) return;

			// Restore the key value (see above)
			$model->setAttribute($this->getKeyName(), $key);

			// Create the new encoding
			$model->encodings()->save(new Encoding(array(
				'encodable_attribute' => $attribute,
			)));
		});

	}

	/**
	 * Delete all the encodings individually so model callbacks can respond
	 *
	 * @return void 
	 */
	public function deleteEncodings() {
		$this->encodings()->get()->each(function($encode) {
			$encode->delete();
		});
	}

}