<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Config;
use Former\Form\Fields\File;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;
use Str;

/**
 * Creates a file upload field with addtional UI for reviewing the last upload
 * and deleting it.
 */
class Upload extends File {

	/**
	 * Preserve blockhelp data
	 *
	 * @var array
	 */
	private $blockhelp;

	/**
	 * Create a regular file type field
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
		$this->addGroupClass('upload file-upload');
	}

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
		$this->blockhelp = func_get_args();
		return $this;
	}

	/**
	 * Prints out the field, wrapped in its group.  This is the opportunity
	 * to tack additional stuff into the blockhelp before it is rendered
	 * 
	 * @return string
	 */
	public function wrapAndRender() {

		// Wrap manually set help text in a wrapper class
		if (empty($this->blockhelp[0])) $this->blockhelp = array('');
		else $this->blockhelp[0] = '<span class="regular-help">'.$this->blockhelp[0].'</span>';

		// Append the review UI to the blockhelp
		if ($this->value) $this->blockhelp[0] .= $this->renderReview();

		// Apply all of the blockhelp arguments to the group
		if (!empty($this->blockhelp)) {
			call_user_func_array(array($this->group, 'blockhelp'), $this->blockhelp);
		}

		// Continue doing normal wrapping
		return parent::wrapAndRender();
	}

	/**
	 * Prints out the current tag. The hidden field with the current upload is
	 * prepended before the input so it can be overriden
	 *
	 * @return string An input tag
	 */
	public function render() {
		if ($this->value) return $this->renderHidden().parent::render();
		else return parent::render();
	}

	/**
	 * Render the hidden field that contains the currently uploaded file
	 *
	 * @return string A hidden field
	 */
	protected function renderHidden() {
		return HtmlInput::hidden($this->name, $this->value);
	}

	/**
	 * Render the display of the currently uploaded item
	 *
	 * @return string HTML
	 */
	protected function renderReview() {
		if (!$this->isRequired() && $this->isInUploads()) return $this->renderDestuctableReview();
		else return $this->renderIndestructibleReview();
	}

	/**
	 * Check if the file is in the uploads directory. The use case for this arose 
	 * with the Fragments system where the default images would usually be in the 
	 * img directory
	 *
	 * @return boolean
	 */
	protected function isInUploads() {
		$upload_dir = Library\Utils\File::publicPath(Config::get('decoy::upload_dir'));
		return Str::is($upload_dir.'*', $this->value);
	}

	/**
	 * Show the preview UI with a delete checkbox
	 *
	 * @return string HTML
	 */
	protected function renderDestuctableReview() {
		return '<label for="'.$this->name.'-delete" class="checkbox file-delete">
			<input id="'.$this->name.'-delete" type="checkbox" name="'.$this->name.'" value="">
			Delete '.$this->renderDownloadLink().'
			</label>';
	}

	/**
	 * Show the preview UI withOUT a delete checkbox
	 *
	 * @return string HTML
	 */
	protected function renderIndestructibleReview() {
		return '<label class="download">
			Currently '.$this->renderDownloadLink().'
			</label>';
	}

	/**
	 * Make the downloadable link to the file
	 *
	 * @return string HTML
	 */
	protected function renderDownloadLink() {
		return '<a href="'.$this->value.'">
			<code><i class="icon-file"></i>'.basename($this->value).'</code>
			</a>';
	}

}