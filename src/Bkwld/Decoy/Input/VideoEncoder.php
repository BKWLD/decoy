<?php namespace Bkwld\Decoy\Input;

// Dependencies

/**
 * Render many to many checklists and process their submittal
 */
class VideoEncoder {

	/**
	 * The source model record
	 *
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * The source video
	 *
	 * @var string 
	 */
	protected $source;

	/**
	 * Encode a video file that is referenced in a attribute on a model instance
	 *
	 * @param Illuminate\Database\Eloquent\Model $model 
	 * @param  string $attribute 
	 * @return  void 
	 */
	public function add($model, $attribute) {

		// Save the source out
		$this->model = $model;
		$this->source = $model->$attribute;

		// If the model exists, encode immediately
		if ($model->exists) $this->requestEncode();

		// Otherwise, listen for creation before encoding.  This is necessary
		// so we can create the polymorphic 1-1 record.  We need an ID.
		else {
			$model->created(function($model) {
				$this->requestEncode();
			});
		}
	}

	/**
	 * Dial out to the encoding service to request an encode
	 *
	 * @return  void 
	 */
	protected function requestEncode() {
		\Log::info('request it!');
		\Log::info($this->model->getKey());
	}

}