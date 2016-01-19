<?php namespace Bkwld\Decoy\Models;

// Deps
use Croppa;
use Bkwld\Decoy\Markup\ImageElement;

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
		'file' => 'image',
	];

	/**
	 * Uploadable attributes
	 *
	 * @var array
	 */
	protected $upload_attributes = ['file'];

	/**
	 * Stores config from chained transformations while a url or tag is generated
	 *
	 * @var array
	 */
	private $config = [];

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

	/**
	 * Set the crop dimenions
	 *
	 * @return $this
	 */
	public function crop($width = null, $height = null, $options = null) {
		$this->config = [
			'width'   => $width,
			'height'  => $height,
			'options' => $options,
		];
		return $this;
	}

	/**
	 * Output the image URL with any queued Croppa transformations.  Note, it's
	 * possible that "file" is empty, in which case this returns an empty string.
	 *
	 * @return string
	 */
	public function getUrlAttribute() {

		// Merge the config with defaults
		$config = array_merge([
			'width'   => null,
			'height'  => null,
			'options' => null,
		], $this->config);

		// Clear the instance config so that subsequent calls don't inherit anything
		$this->config = [];

		// Return the URL
		return Croppa::url($this->getAttribute('file'),
			$config['width'],
			$config['height'],
			$config['options']
		);
	}

	/**
	 * Output image for background style
	 *
	 * @return string
	 */
	public function getBkgdAttribute() {
		return sprintf('background-image: url(\'%s\');', $this->getUrlAttribute())
			.$this->getBackgroundPositionAttribute();
	}

	/**
	 * Output an image tag.  The ?: was necessary because HtmlObject sets NULL
	 * values to "true" in the rendered attribute.
	 *
	 * @return Element
	 */
	public function getImgAttribute() {
		return ImageElement::img()
			->isSelfClosing()
			->src($this->getUrlAttribute() ?: false)
			->alt($this->getAttribute('title') ?: false);
	}

	/**
	 * Output a div tag.
	 * https://www.w3.org/TR/wai-aria/roles#img
	 *
	 * @return Element
	 */
	public function getDivAttribute() {
		return ImageElement::div()
			->style($this->getBkgdAttribute())
			->role('img')
			->ariaLabel($this->getAltAttribute());
	}

	/**
	 * Convert the focal_point attribute to a CSS background-position
	 *
	 * @return String
	 */
	public function getBackgroundPositionAttribute() {
		$point = $this->getAttribute('focal_point');
		if (empty($point)) return null;
		$point = json_decode($point);
		return sprintf('background-position: %s%% %s%%;',
			$point->x*100, $point->y*100);
	}

	/**
	 * Convenience accessor for the title attribute
	 *
	 * @return String
	 */
	public function getAltAttribute() {
		return $this->getAttribute('title');
	}

}
