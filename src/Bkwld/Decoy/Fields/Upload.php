<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library\Utils;
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
	use Traits\InsertMarkup;

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
		$this->addGroupClass('upload');

		// Add the max upload info
		$this->addClass('js-tooltip');
		$this->title('Max upload size: <b>'.Utils\String::humanSize(Utils\File::maxUpload(), 1).'</b>');
	}

	/**
	 * Prints out the field, wrapped in its group.  Additional review UI is tacked on here.
	 * 
	 * @return string
	 */
	public function wrapAndRender() {

		// Get the rendered control group
		$html = parent::wrapAndRender();

		// Add extra markup
		return $this->appendToGroup($html, $this->renderReview());
	}

	/**
	 * Prints out the current file upload field. The hidden field with the current upload is
	 * prepended before the input so it can be overriden
	 *
	 * @return string An input tag
	 */
	public function render() {

		// A file has already been uploaded
		if ($this->value) {

			// If it's required, show the icon but don't enforce it.  There is already
			// a file uploaded after all
			if ($this->isRequired()) $this->setAttribute('required', null);

			// Add hidden field and return
			return $this->renderHidden().parent::render();
		
		// The field is empty
		} else return parent::render();
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
		if (!$this->value) return;
		else if (!$this->isRequired() && $this->isInUploads()) return $this->renderDestuctableReview();
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
		$upload_dir = Utils\File::publicPath(Config::get('decoy::core.upload_dir'));
		return Str::is($upload_dir.'*', $this->value);
	}

	/**
	 * Show the preview UI with a delete checkbox
	 *
	 * @return string HTML
	 */
	protected function renderDestuctableReview() {
		return '<label for="'.$this->name.'-delete" class="checkbox-inline upload-delete">
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
		return '<a class="download-link" href="'.$this->value.'">
			<span class="glyphicon glyphicon-file"></span>'.basename($this->value).'
			</a>';
	}

}