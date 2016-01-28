<?php namespace Bkwld\Decoy\Collections;

// Deps
use Bkwld\Decoy\Exceptions\Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The collection that is returned from queries on models that extend from
 * Decoy's base model.  Adds an API to register tranformations that get applied
 * to each item in the collection (like via `map()`) immediately before array
 * or json serialization.  It also adds
 */
class Base extends Collection {

	/**
	 * Stores pending transformations
	 *
	 * @var array
	 */
	protected $transforms = [];

	/**
	 * Add a serialization transformer.  The transformer should return the model
	 * instance like a mapping function would.  Or, if it returns null, that item
	 * will be removed from the collection.
	 *
	 * @param callable $func
	 * @return $this
	 */
	public function transform(callable $func) {
		$this->transforms[] = $func;
		return $this;
	}

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
		$this->transform(function(Model $model) use (
			$name, $property, $width, $height, $options) {

			// Make sure that the model uses the HasImages trait
			if (!method_exists($model, 'img')) {
				throw new Exception(get_class($model).' needs HasImages trait');
			}

			// Lookup up the image by name and set crop.
			$image = $model->img($name)->crop($width, $height, $options);

			// Create or fetch the container for all images on the model. The
			// container could not be "images" because that is used by the
			// relationship function and leads to trouble.
			if (!$model->getAttribute('imgs')) {
				$imgs = [];
				$model->addVisible('imgs');
			} else {
				$imgs = $model->getAttribute('imgs');
			}

			// Add the image to the container and set it.  Then return the model. It
			// must be explicitly converted to an array because Laravel won't
			// automatically do it during collection serialization. Another, more
			// complicated approach could have been to use the Decoy Base model to add
			// a cast type of "model" and then call toArray() on it when casting the
			// attribute.
			$imgs[$property] = $image->toArray();
			$model->setAttribute('imgs', $imgs);
			return $model;
		});

		// Support chaining
		return $this;
	}

	/**
	 * Override toArray() to fire transforms before serialization
	 *
	 * @return array
	 */
	public function toArray() {
		$this->runTransforms();
		return parent::toArray();
	}

	/**
	 * Override toArray() to fire transforms before serialization
	 *
	 * @param  int  $options
	 * @return array
	 */
	public function toJson($options = 0) {
		$this->runTransforms();
		return parent::toJson($options);
	}

	/**
	 * Apply all registered transforms to the items in the collection. If a
	 * transform returns falsey, that model will be removed from the collection
	 *
	 * @return void
	 */
	public function runTransforms() {
		$this->items = $this->map(function(Model $model) {

			// Loop through all the transform functions
			foreach($this->transforms as $transform) {

				// Call the transform and if nothing is retuned, clear that model
				// from the collection
				if (!$model = call_user_func($transform, $model)) return;
			}

			// Return the transformed model
			return $model;

		// Remove all the models whose transforms did not return a value. Then
		// convert back to an array.
		})->filter()->all();
	}


}
