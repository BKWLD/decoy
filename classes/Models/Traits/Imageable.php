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
	 * Boot events
	 *
	 * @return void
	 */
	public static function bootImageable() {

		// Delete all Images if the parent is deleted.  Need to use "each" to get
		// the Image deleted events to fire.
		static::deleted(function($model) {
			$model->images->each(function($image) {
				$image->delete();
			});
		});
	}

	/**
	 * Polymorphic relationship
	 */
	public function images() {
		return $this->morphMany(Image::class, 'imageable');
	}

}
