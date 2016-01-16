<?php namespace Bkwld\Decoy\Models;

/**
 * Polymorphic one to many class that stores images for any model.
 */
class Image extends Base {

	/**
	 * Validation rules
	 *
	 * @return array
	 */
	public static $rules = [
		// 'file' => 'image',
	];

	/**
	 * Uploadable attributes
	 *
	 * @var array
	 */
	protected $upload_attributes = ['file'];

	/**
	 * Register events
	 *
	 * @return void
	 */
	protected static function boot() {
		parent::boot();

		// Need to process file meta before Upchuck converts the UploadFile object
		// to a URL string
		static::saving(function(Image $image) {
			$image->populateFileMeta();
		}, config('upchuck.priority', 0) + 1);
	}

	/**
	 * Polymorphic relationship
	 */
	public function imageable() { return $this->morphTo(); }

	/**
	 * Store file meta info in the database
	 *
	 * @return void
	 */
	public function populateFileMeta() {
		$file = $this->getAttribute('file');
		$size = getimagesize($file->getPathname());
		$this->fill([
			'file_type' => $this->guessFileType($file),
			'file_size' => $file->getSize(),
			'width'     => $size[0],
			'height'    => $size[1],
		]);
	}

	/**
	 * Get file type
	 *
	 * @param Symfony\Component\HttpFoundation\File\UploadedFile
	 * @return string
	 */
	protected function guessFileType($file) {
		$type = $file->guessClientExtension();
		switch($type) {
			case 'jpeg': return 'jpg';
			default: return $type;
		}
	}


}
