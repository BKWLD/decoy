<?php namespace Bkwld\Decoy\Input;

// Dependencies


/**
 * Utilities that the Decoy base controller can use to generate
 * the related content sidebar
 */
class Sidebar {

	/**
	 * The array of listings
	 *
	 * @var array An array of Former::listing() objects
	 */
	private $listings;

	/**
	 * The model instance currently being worked on by Decoy 
	 * 
	 * @var Illuminate\Database\Eloquent\Model 
	 */
	private $parent;

	/**
	 * Inject dependencies
	 *
	 * @param array $listings An array of Former::listing() objects
	 * @param Illuminate\Database\Eloquent\Model $parent The model instance 
	 *        currently being worked on by Decoy
	 */
	public function __construct($listings, $parent) {
		if (!is_array($listings)) $listings = array($listings);
		$this->listings = $listings;
		$this->parent = $parent;
	}

	/**
	 * Render an array of listing objects to an HTML string
	 *
	 * @return string HTML
	 */
	public function render() {

		// Massage the response from base controller subclassings of sidebar
		$listings = array_map(function($listing) {

			// If a listing instance, apply defaults common to all sidebar instances
			if (is_a($listing, 'Bkwld\Decoy\Fields\Listing')) {
				return $listing->layout('sidebar')->parent($this->parent);

			// Allow string to be passed
			} else return $listing;
		}, $this->listings);

		// Combine all listing items into a single string and return
		return array_reduce($listings, function($carry, $item) {
			return $carry.$item;
		}, '');

	}

}