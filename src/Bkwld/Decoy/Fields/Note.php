<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Former\Traits\Field;
use HtmlObject\Element;
use Illuminate\Container\Container;

/**
 * A note field has no actual input elements.  It's a control group with just the passed
 * html where the inputs would be.
 */
class Note extends Field {

	/**
	 * Create a standard field
	 *
	 * @param Container $app        The Illuminate Container
	 * @param string    $type       text
	 * @param string    $label      Its label
	 * @param string    $value      Its value
	 * @param array     $attributes Attributes
	 */
	public function __construct(Container $app, $type, $label, $value, $attributes) {

		// Make setting a name unncessary
		parent::__construct($app, $type, '', $label, $value, $attributes);

		// Add a class to the group for extra styling
		$this->addGroupClass('note');
	}

	/**
	 * Output the value in a normal div
	 *
	 * @return string An input tag
	 */
	public function render() {
		return Element::create('div', $this->value, $this->attributes);
	}

}