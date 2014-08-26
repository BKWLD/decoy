<?php namespace Bkwld\Decoy\Models\Traits;

/**
 * Mix this into models that join to the Encoding model to
 * add the Laravel relationship and add helper methods
 */
trait Encodable {

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
	public function encode($field = 'video') {
		return $this->encodings()->where('encodable_attribute', '=', $field)->first();
	}
			
	/**
	 * A utitliy function to create status badges for Decoy listings
	 *
	 * @return string HTML
	 */
	public function adminColEncodeStatus() {
		if (!$encode = $this->encode()) return '<span class="label">Pending</span>';
		switch($encode->status) {
			case 'pending': return '<span class="label">'.ucfirst($encode->status).'</span>';
			case 'error':
			case 'cancelled': return '<span class="label label-important">'.ucfirst($encode->status).'</span>';
			case 'queued':
			case 'processing': return '<span class="label label-info">'.ucfirst($encode->status).'</span>';
			case 'complete': return '<span class="label label-success">'.ucfirst($encode->status).'</span>';
		}
	}

}