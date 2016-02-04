<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Illuminate\Container\Container;
use Former;
use Route;

/**
 * Creates a file upload field with addtional UI for tracking the status of video
 * encodes and the playback of a video.
 */
class VideoEncoder extends Upload {
	use Traits\Helpers;

	/**
	 * The encoding row for the field
	 *
	 * @var Bkwld\Decoy\Models\Encoding
	 */
	protected $encoding;

	/**
	 * The model attribute to find the video source value
	 * 
	 * @var string
	 */
	protected $model_attribute;

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
		parent::__construct($app, $type, $name, $label, $value, $attributes);

		// Add attributes for styling and JS views
		$this->addGroupClass('video-encoder');
		$this->group->data_js_view('video-encoder');

		// Set the model_attribute for the encoding
		$this->model_attribute = Route::is('decoy::fragments') 
			|| Route::is('decoy::elements') ? 'value' : $this->name;
	}

	/**
	 * Inform VideoEncoder of the attribtue name on the encodings to find the encode.  This is
	 * necessary if the form field is named different than the db column.
	 *
	 * @param string $name 
	 * @return this 
	 */
	public function setModelAttribute($name) {
		$this->model_attribute = $name;
		return $this;
	}

	/**
	 * Prints out the field, wrapped in its group.
	 * 
	 * @return string
	 */
	public function wrapAndRender() {

		// Check if the model has encodings
		if (($item = $this->model()) && method_exists($item, 'encodings')) {

			// If so, get it's encoding model instance
			$this->encoding = $item->encodings()
				->where('encodable_attribute', $this->model_attribute)
				->first();

			// Add the data attributes for JS view
			if ($this->encoding) $this->group->data_encode($this->encoding->id);
		}

		// Continue rendering
		return parent::wrapAndRender();
	}

	/**
	 * Show the video player with a delete checkbox
	 *
	 * @return string HTML
	 */
	protected function renderDestuctableReview() {
		return $this->renderPlayerOrStatus().parent::renderDestuctableReview();
	}

	/**
	 * Should only the video player with no checkbox
	 *
	 * @return string HTML
	 */
	protected function renderIndestructibleReview() {
		if ($this->encoding && $this->encoding->status == 'complete') return $this->renderPlayerOrStatus();
		else return $this->renderPlayerOrStatus().parent::renderIndestructibleReview();
	}

	/**
	 * Render the player if the encoding is complete.  Otherwise, show progress
	 *
	 * @return string HTML
	 */
	protected function renderPlayerOrStatus() {
		if (!$this->encoding) return $this->renderError('No encoding instance found.');
		switch($this->encoding->status) {
			case 'complete': return $this->renderPlayer();
			case 'error': return $this->renderError($this->encoding->message);
			case 'cancelled': return $this->renderError('The encoding job was manually cancelled');
			case 'pending': return $this->renderProgress('');
			case 'queued': return $this->renderProgress($this->encoding->status);
			case 'processing': return $this->renderProgress($this->encoding->status);
		}
	}

	/**
	 * Render a video player
	 *
	 * @return string HTML
	 */
	protected function renderPlayer() {
		return $this->encoding->getAdminPlayerAttribute();
	}

	/**
	 * Render error's the same way that normal errors are rendered.
	 */
	protected function renderError($message) {
		$this->addGroupClass('has-error');
		$this->help('Encoding error: '.$message);
	}

	/**
	 * Show the status message.  Div's have been converted to span's because
	 * they are rendered with blockHelp which is a "p"
	 *
	 * @return string HTML
	 */
	protected function renderProgress($status) {
		return '<div class="status">
			<div class="progress">
				<div class="progress-bar progress-bar-striped active" style="width: '
					.($this->encoding->getProgressAttribute())
					.'%;">'.$status.'</div>
				</div>
			</div>';
	}

}