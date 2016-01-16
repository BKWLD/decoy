<?php namespace Bkwld\Decoy\Models\Traits;

// Dependencies
use Bkwld\Decoy\Models\Image;

/**
 * All models that should support images should inherit this trait.  This gets
 * used by the Base Model.  This logic lives as a trait mostly to keep the
 * base model cleaner.
 */
trait Imageable {

	/**
	 * Polymorphic relationship
	 */
	public function images() {
		return $this->morphMany(Image::class, 'imageable');
	}

}
