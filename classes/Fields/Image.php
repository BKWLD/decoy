<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Croppa;
use Former;
use Former\Form\Fields\File;
use Illuminate\Container\Container;

/**
 * Creates an image file upload field with addtional UI for displaing previously
 * uploaded images, setting crop bounds, and deleting them.
 */
class Image extends File {
	use Traits\InsertMarkup, Traits\Helpers;

	/**
	 * Whether to show the focal point UI
	 *
	 * @var bool
	 */
	private $add_focal_point = false;

	/**
	 * The unique id for this Image instance
	 *
	 * @var string
	 */
	private $input_id;

	/**
	 * The aspect ratio jCrop should use
	 *
	 * @var number
	 */
	private $ratio;

	/**
	 * Create an image upload field
	 *
	 * @param Container $app        The Illuminate Container
	 * @param string    $type       text
	 * @param string    $name       Field name
	 * @param string    $label      Its label
	 * @param string    $value      Its value
	 * @param array     $attributes Attributes
	 */
	public function __construct(Container $app, $type, $name, $label, $value, $attributes) {
		parent::__construct($app, 'file', $name, $label, $value, $attributes);

		// Add an extra class to allow styles to tweak ontop of the main "uploads"
		// class
		$this->addGroupClass('image-upload');

		// Make it accept only images
		$this->accept('image');
	}

	/**
	 * Give the file input the prefixed name
	 *
	 * @return string An input tag
	 */
	public function render() {
		$this->name = $this->inputName('file');
		return parent::render();
	}

	/**
	 * Prints out the field, wrapped in its group.  Additional review UI is tacked
	 * on here.
	 *
	 * @return string
	 */
	public function wrapAndRender() {

		// Get additional UI first so it can modify the normal form-group UI.
		$html = $this->renderEditor();

		// Add the aspect ratio choice
		$this->dataAspectRatio($this->ratio);

		// Add extra markup
		return $this->appendToGroup(parent::wrapAndRender(), $html);
	}

	/**
	 * Render the display of the currently uploaded item
	 *
	 * @return string HTML
	 */
	protected function renderEditor() {
		$html = '';

		// Will store the name
		$html .= $this->createHidden('name', $this->name);

		// Will store the crop coordinates
		$html .= $this->createHidden('crop',
			$this->image()->getAttributeValue('crop'));

		// Begin the image upload section
		$html .= '
			<div class="image-holder">
				<div class="toolbar input-group">
					<div class="btn-group btn-group-sm">
						<div class="crop btn js-tooltip active" title="Click a point in the
							image that should stay in view despite browser resizing">
							<div class="glyphicon glyphicon-scissors"></div></div>';

		// Will add the focal point ui
		if ($this->add_focal_point) {
			$html .= '<div class="focal btn js-tooltip" title="Click a point in the
				image that should stay in view despite browser resizing">
					<div class="glyphicon glyphicon-screenshot"></div></div>';

			// Will store the focal point
			$html .= $this->createHidden('focal_point',
				$this->image()->getAttributeValue('focal_point'));
		}

		// Will close out the button group
		$html .= '</div>';

		// Add the title input
		$html .= '<input class="title js-tooltip" placeholder="Title" type="text"
			title="Title">';

		// Close out all divs and add image holder
		$html .= '</div><img class="img-thumbnail no-help" src=""></div>';

		return $html;
	}

	/**
	 * Make a hidden field
	 *
	 * @param  string $name
	 * @param  string $value
	 * @return string
	 */
	protected function createHidden($name, $value) {
		return sprintf('<input type="hidden" name="%s" value="%s" class="input-%s">',
			$this->inputName($name), $value, $name);
	}


	/**
	 * Wrap an input name in the nested / array-like structure
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function inputName($name) {
		return sprintf('_images[%s][%s]', $this->inputId(), $name);
	}

	/**
	 * Get the id to use in all inputs for this Image
	 *
	 * @return string Either a model id or an arbitrary number prefixed by _
	 */
	protected function inputId() {

		// If we're editing an already existing image, return it's Image id
		if (($image = $this->image()) && $image->id) return $image->id;

		// Otherwise, use a unique id for this image instance.  It must be unique
		// compared to other Former:image() instances on this page.
		if (!$this->input_id) $this->input_id = '_'.str_random(4);
		return $this->input_id;
	}

	/**
	 * Get the Image model we're editing or an empty Image class
	 *
	 * @return Image
	 */
	protected function image() {
		return $this->model()->image($this->name);
	}

	/**
	 * Store the aspect ratio to use with jCrop
	 *
	 * @param  number $ratio The result of an expression like 16/9
	 * @return $this
	 */
	public function aspect($ratio) {
		$this->ratio = $ratio;
		return $this;
	}

	/**
	 * Toggle the presence of the focal point ui
	 *
	 * @param  bool $bool
	 * @return $this
	 */
	public function addFocalPoint($bool = true) {
			$this->add_focal_point = $bool;
			return $this;
	}

}
