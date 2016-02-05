<?php namespace Bkwld\Decoy\Models\Traits;

// Deps
use Bkwld\Decoy\Exceptions\Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds helpers for registering serialization transforms that add Images to the
 * serialized output within an `imgs` property at given crop sizes.
 */
trait SerializeWithImages {

	/**
	 * Convenience method for adding the default, null, named image crop
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  array   $options
	 * @return $this
	 */
	public function withDefaultImage(
		$width = null,
		$height = null,
		$options = null) {
		$this->withRenamedImage(null, null, $width, $height, $options);
		return $this;
	}

	/**
	 * Convenience method for specifying the name of the image to add
	 *
	 * @param  string  $name
	 * @param  integer $width
	 * @param  integer $height
	 * @param  array   $options
	 * @return $this
	 */
	public function withImage(
		$name = null,
		$width = null,
		$height = null,
		$options = null) {
		$this->withRenamedImage($name, $name, $width, $height, $options);
		return $this;
	}

	/**
	 * Add an Image instance with the provided crop in instructions to every
	 * item in the collection.
	 *
	 * @param  string  $name      The `name` attribute to look for in Images
	 * @param  string  $property  The property name to use in the JSON output
	 * @param  integer $width
	 * @param  integer $height
	 * @param  array   $options
	 * @return $this
	 *
	 * @throws Exception
	 */
	public function withRenamedImage(
		$name = null,
		$property = null,
		$width = null,
		$height = null,
		$options = null) {

		// The json needs a property name
		if (empty($property)) $property = 'default';

		// Add a transform that adds and whitelisted the attribute as named
		$this->serializeTransform(function(Model $model) use (
			$name, $property, $width, $height, $options) {
			$base_model = $model;

			// Make sure that the model uses the HasImages trait
			if (!method_exists($model, 'img')) {
				throw new Exception(get_class($model).' needs HasImages trait');
			}

			// If the name contains a period, treat it as dot notation to get at an
			// image on a related model
			if (strpos($name, '.') > -1) {
				$relations = explode('.', $name);

				// If the name and property are identical, use just the image name for
				// the property.
				if ($name == $property) $name = $property = array_pop($relations);
				else $name = array_pop($relations);

				// Step through the relationship chain to get at the model with the
				// image. If a relationship is absent, don't add the image.
				foreach($relations as $relation) {
					if (!$model = $model->$relation) return $base_model;
				}

				// If the name is "default", look for it with a NULL name
				if ($name == 'default') $name = null;
			}

			// Lookup up the image by name and set crop and append it to the model
			$image = $model->img($name)->crop($width, $height, $options);
			$model->appendToImgs($image, $property);

			// Return the model being transformed
			return $base_model;
		});

		// Support chaining
		return $this;
	}

}
