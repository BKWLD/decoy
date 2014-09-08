<?php namespace Bkwld\Decoy\Fields\Traits;

/**
 * Store block help attributes so I can access them later in the
 * rendering of a field
 */
trait CaptureBlockHelp {

	/**
	 * Preserve blockhelp data
	 *
	 * @var string
	 */
	private $blockhelp;

	/**
	 * Preserve blockhelp attribues
	 *
	 * @var array
	 */
	private $blockhelp_attributes;

	/**
	 * Preserve the label text
	 *
	 * @var callable
	 */
	private $label_text;

	/**
	 * Store the block help locally so that we can append the review within it
	 * right before rendering.  If we decide to move the review UI out of block-help, the
	 * field constructor should replace the group with an instance of a subclass that
	 * adds some public methods to hook into the field wrapping logic.
	 *
	 * This method is ordinarily declared in the Former Group
	 *
	 * @param  string $help       The help text
	 * @param  array  $attributes Facultative attributes
	 */
	public function blockhelp($help, $attributes = array()) {
		$this->blockhelp = $help;
		$this->blockhelp_attributes = $attributes;
		return $this;
	}

} 