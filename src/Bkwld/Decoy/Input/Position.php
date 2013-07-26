<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Input;
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
		if ($relationship && Input::has('parent_id')) {
			$relation = $this->item->{$relationship}();
			if ($relation instanceof BelongsToMany) {
				$this->pivot = $relation->where($relationship.'.id', '=', Input::get('parent_id'))->first()->pivot;
			}
		}
	}
	
	/**
	 * Check if we have all dependencies for an position change
	 */
	public function has() {
		if (!Input::has('position')) return false;
		if (isset($this->item->position)) return true;
		else if (!empty($this->pivot) && isset($this->pivot->position)) return true;
		return false;
	}
	
	/**
	 * Set new position
	 */
	public function update() {

		// Write position value to the item
		if (isset($this->item->position)) {
			$this->item->position = Input::get('position');
			$this->item->save();
		
		// Write the position value to the pivot table
		} else if (isset($this->pivot->position)) {
			$this->pivot->position = Input::get('position');
			$this->pivot->save();
		}
		
	}
	
}