<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Request;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Handle drag-and-drop position updates
 */
class Position {

	/**
	 * Constructor
	 * @param Eloquent $item A model isntance
	 * @param string $relationship The name of the relationship function on the instance
	 */
	private $item;
	private $pivot;
	public function __construct($item, $relationship = null) {
		$this->item = $item;
		if ($relationship && Request::has('parent_id')) {
			$relation = $this->item->{$relationship}();
			if ($relation instanceof BelongsToMany) {
				$this->pivot = $relation->where($relation->getOtherKey(), '=', Request::get('parent_id'))->first()->pivot;
			}
		}
	}

	/**
	 * Check if we have all dependencies for an position change
	 */
	public function has() {
		if (!Request::has('position')) return false;
		if (isset($this->item->position)) return true;
		else if (!empty($this->pivot) && isset($this->pivot->position)) return true;
		return false;
	}

	/**
	 * Set new position
	 */
	public function fill() {

		// Write the position value to the pivot table
		if (isset($this->pivot->position)) {
			$this->pivot->position = Request::get('position');
			$this->pivot->save();

		// Write position value to the item
		} else if (isset($this->item->position)) {

			// Visiblity may be set at the same time and would be ignored otherwise
			if (Request::has('public')) {
				$this->item->public = request('public', 0);
			}

			// Do position
			$this->item->position = request('position');

		}

	}

}
