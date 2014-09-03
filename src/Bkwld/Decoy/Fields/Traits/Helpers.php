<?php namespace Bkwld\Decoy\Fields\Traits;

/**
 * Misc uitlities that multiple fields may use
 */
trait Helpers {

	/**
	 * Get the model instance for the form from Former's
	 * populator.  This takes advantage of Populator
	 * extending from Collection
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function model() {
		return app('former.populator')->all();
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