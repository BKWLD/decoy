<?php namespace Bkwld\Decoy\Models;

class Element extends Base {
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'key';

	/**
	 * No timestamps necessary
	 *
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * Switch between different formats when rendering to a view
	 *
	 * @return string 
	 */
	public function format() {
		switch($this->type) {
			default: return $this->value;
		}
	}

	/**
	 * Render the element in a view
	 *
	 * @return string 
	 */
	public function __toString() {
		return $this->format();

	}

}