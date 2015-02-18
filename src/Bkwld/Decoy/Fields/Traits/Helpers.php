<?php namespace Bkwld\Decoy\Fields\Traits;

// Dependencies
use Bkwld\Decoy\Models\Fragment;
use Route;

/**
 * Misc uitlities that multiple fields may use
 */
trait Helpers {

	/**
	 * Allow passing in of a model when inferring doesn't work
	 * 
	 * @var Illuminate\Database\Eloquent\Model
	 */
	private $model;

	/**
	 * Set the model
	 *
	 * @param Illuminate\Database\Eloquent\Model $model
	 * @return this 
	 */
	public function setModel($model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * Get the model instance for the form from Former's populator.  This takes 
	 * advantage of Populator extending from Collection
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function model() {

		// Use explicilty set model
		if ($this->model) return $this->model;

		// If a Fragment, build a model instance using the name of the field.  The input
		// field uses pipes instead of the dots that are in the DB.
		if (Route::is('decoy::fragments')) {
			$model = Fragment::where('key', '=', str_replace('|', '.', $this->name))->first();

		// Otherwise, just use the model that was passed to populator
		} else $model = app('former.populator')->all();

		// Make sure it's a model instance
		if (is_a($model, 'Illuminate\Database\Eloquent\Model')) return $model;
	}

	/**
	 * If there is a span class on the field, return it
	 *
	 * @return string 
	 */
	public function span() {
		if (!isset($this->attributes['class'])) return;
		preg_match('#span\d#', $this->attributes['class'], $matches);
		return $matches[0];
	}

} 