<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Croppa;
use Former;
use Illuminate\Container\Container;

/**
 * Creates an image file upload field with addtional UI for displaing previously uploaded
 * images, setting crop bounds, and deleting them.
 */
class Image extends Upload {
	use Traits\Helpers;

	/**
	 * The maximum width images are presented as in Decoy.  This is 820x2.  820 is max width
	 * of a horizontal layout with the a site wide max width of 1280.
	 */
	const MAX_WIDTH = 1640;

	/**
	 * Preserve crop data
	 *
	 * @var array
	 */
	private $crops;

	/**
	 * Track whether the field has blockhelp
	 *
	 * @var boolean
	 */
	private $has_blockhelp = false;

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

		// Add an extra class to allow styles to tweak ontop of the main "uploads" class
		$this->addGroupClass('image-upload');
		$this->group->setAttribute('data-js-view', 'image-fullscreen');

		// Make it accept only images
		$this->accept('image');		
	}

	/**
	 * Override the basic blockhelp function to keep track of whether it's
	 * been provided.  Former doesn't provide an API for this.
	 */
	public function blockhelp() {
		$this->has_blockhelp = true;
		return call_user_func_array(array('parent', 'blockhelp'), func_get_args());
	}

	/**
	 * ** Deprecated function **
	 *
	 * @param  string $help       The help text
	 * @return this 
	 */
	public function crops($crops) {
		\Log::info('Image::crops() is deprecated.  The item is now fetched automatically from Former::populate');
		return $this;
	}

	/**
	 * Show the preview UI with a delete checkbox
	 *
	 * @return string HTML
	 */
	protected function renderDestuctableReview() {
		return $this->renderImageReview().parent::renderDestuctableReview();
	}

	/**
	 * Should only the image review with no checkbox
	 *
	 * @return string HTML
	 */
	protected function renderIndestructibleReview() {
		return $this->renderImageReview();
	}

	/**
	 * Render the display of the currently uploaded item
	 *
	 * @return string HTML
	 */
	protected function renderImageReview() {

		// Check for a crops defintion.  The defintion is named after the model
		// attribute, not the input name
		if (($item = $this->model()) && method_exists($item, 'getUploadMap')) {

			// Convert array-like names to dot notation to match the supportsUploads
			// map
			$name = preg_match('#\]$#', $this->name) ? 
				trim(str_replace(array('[', ']'), array('.', ''), $this->name), '.') : 
				$this->name;

			// Get the model attribute for the name
			$map = $item->getUploadMap();
			$this->crops = @$item::$crops[$map[$name]];
		}

		// Show cropper
		if ($this->crops && $this->isInUploads()) return $this->renderCropper();

		// Else there were no crops defined, so display the single image
		else if ($this->isInUploads()) return $this->renderImageWithCroppa();

		// Else, it's not in the uploads directory, so we can't use croppa
		else return $this->renderImageWithoutCroppa();

	}

	/**
	 * Add markup needed for the cropping UI
	 *
	 * @return string HTML
	 */
	protected function renderCropper() {

		// Open container
		$html = '<div class="image-upload-cropper">';
			
		// Add the tabs
		if (count($this->crops) > 1) {
			$html .= '<div class="tabs" data-js-view="crop-styles">';
			$active = 'active';
			foreach($this->crops as $key => $val) {
				$label = is_numeric($key) ? $val : $key;
				$html .= '<span class="'.$active.'">'
					.Library\Utils\Text::titleFromKey($label)
					.'</span>';
				$active = null;
			}
			$html .= '</div>';
		}
	
		// Add fullscreen button
		$html .= '<span class="glyphicon glyphicon-fullscreen fullscreen-toggle"></span>';
		
		// Add the images
		$html .= '<div class="imgs js-tooltip" title="Click and drag on the image to create cropping bounds.">';
		foreach($this->crops as $key => $val) {
			
			// Figure out the raio and crop name
			if (is_numeric($key)) {
				$style = $val;
				$ratio = null;
			} else {
				$style = $key;
				$ratio = $val;
			}
			
			// Create the HTML
			$html .= '<a href="'.$this->value.'">
				<img src="'.Croppa::url($this->value, self::MAX_WIDTH).'" 
					class="'.$this->imgTag().'" 
					data-ratio="'.$ratio.'" 
					data-style="'.$style.'" 
					data-js-view="crop">
				</a>';
	
		}

		// Close
		$html .= '</div></div>';

		// Add hidden field to store cropping choices. If the field use array-like
		// naming, insert the _crops suffix inside the last bracket. 
		$name = preg_match('#\]$#', $this->name) ? 
			substr_replace($this->name, '_crops', -1, 0) : 
			$this->name.'_crops';
		$html .= Former::hidden($name);

		// After rendering a new Former field, the active former field must be reset 
		// for form validation errors to work.
		$this->app['former.field'] = $this;

		// Return HTML
		return $html;
	}
	
	/**
	 * Render the review image with croppa
	 *
	 * @return string HTML
	 */
	protected function renderImageWithCroppa() {
		return '<a href="'.$this->value.'">
			<img src="'.Croppa::url($this->value, self::MAX_WIDTH).'" 
				class="'.$this->imgTag().' fullscreen-toggle">
			</a>';
	}
	
	/**
	 * Render the 
	 *
	 * @return string HTML
	 */
	protected function renderImageWithoutCroppa() {
		return '<a href="'.$this->value.'">
			<img src="'.$this->value.'" 
				class="'.$this->imgTag().' fullscreen-toggle">
			</a>';
	}

	/**
	 * Make the class for the image tag
	 */
	protected function imgTag() {
		if ($this->has_blockhelp) return 'img-thumbnail';
		else return 'img-thumbnail no-help';
	}

}