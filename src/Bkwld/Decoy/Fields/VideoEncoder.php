<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Illuminate\Container\Container;

/**
 * Creates a file upload field with addtional UI for tracking the status of video
 * encodes and the playback of a video.
 */
class VideoEncoder extends Upload {
	use Traits\CaptureBlockHelp;

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
		$this->addGroupClass('video-encoder');
	}

}