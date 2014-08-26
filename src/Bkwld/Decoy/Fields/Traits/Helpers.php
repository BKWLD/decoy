<?php namespace Bkwld\Decoy\Fields\Traits;

/**
 * Misc uitlities that multiple fields may use
 */
trait Helpers {

	/**
	 * Get the model instance for the form from Former's
	 * populator.  This takes advantage of Populator
	 * extending from Collection
	 */
	public function model() {
		return app('former.populator')->all();
	}

} 