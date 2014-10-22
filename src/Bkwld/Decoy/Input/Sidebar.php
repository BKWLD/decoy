<?php namespace Bkwld\Decoy\Input;

// Dependencies


/**
 * Utilities that the Decoy base controller can use to generate
 * the related content sidebar
 */
class Sidebar {

	/**
	 * The array of items to show in the sidebar
	 *
	 * @var array
	 */
	private $items = [];

	/**
	 * The model instance currently being worked on by Decoy 
	 * 
	 * @var Illuminate\Database\Eloquent\Model 
	 */
	private $parent;

	/**
	 * Inject dependencies
	 *
	 * @param Illuminate\Database\Eloquent\Model $parent The model instance 
	 *        currently being worked on by Decoy
	 */
	public function __construct($parent = null) {
		$this->parent = $parent;
	}

	/**
	 * Add an item to the sidebar
	 *
	 * @param mixed Generally an Bkwld\Decoy\Fields\Listing object or a string
	 */
	public function add($item) {
		$this->items[] = $item;
	}

	/**
	 * Return whether the sidebar is empty or not
	 *
	 * @return boolean 
	 */
	public function isEmpty() {
		return empty($this->items);
	}

	/**
	 * Render an array of listing objects to an HTML string
	 *
	 * @return string HTML
	 */
	public function render() {

		// Massage the response from base controller subclassings of sidebar
		$items = array_map(function($item) {

			// If a listing instance, apply defaults common to all sidebar instances
			if (is_a($item, 'Bkwld\Decoy\Fields\Listing')) {
				return $item->layout('sidebar')->parent($this->parent);

			// Anything else will be converted to a string in the next step
			} else return $item;

		}, $this->items);

		// Combine all listing items into a single string and return
		return array_reduce($items, function($carry, $item) {
			return $carry.$item;
		}, '');

	}

}