<?php namespace Bkwld\Decoy\Models\Traits;

// Dependencies
use Bkwld\Decoy\Models\Image;
use Illuminate\Database\Eloquent\Builder;

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

		// Automatically eager load the images relationship
		static::addGlobalScope('age', function(Builder $builder) {
			$builder->with('images');
		});
	}

	/**
	 * Polymorphic relationship
	 */
	public function images() {
		return $this->morphMany(Image::class, 'imageable');
	}

	/**
	 * Get a specific Image by searching the eager loaded Images collection for
	 * one matching the name.
	 *
	 * @param string $name The "name" field from the db
	 * @return Image
	 */
	public function image($name = null) {
		return $this->images->first(function($key, Image $image) use ($name) {
			return $image->getAttribute('name') == $name;
		});
	}

}
