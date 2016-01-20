<?php namespace Bkwld\Decoy\Models;

// Deps
use Croppa;
use Bkwld\Decoy\Markup\ImageElement;

/**
 * Polymorphic one to many class that stores images for any model.
 */
class Image extends Base {

	/**
	 * JSON serialization
	 *
	 * @var array
	 */
	protected $visible = ['low', 'medium', 'high', 'background_position', 'title'];
	protected $appends = ['low', 'medium', 'high', 'background_position'];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'file_size'   => 'integer',
		'width'       => 'integer',
		'height'      => 'integer',
		'crop'        => 'object',
		'focal_point' => 'object',
	];

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
	 * @param  integer $width
	 * @param  integer $height
	 * @param  array   $options Croppa options array
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
	 * Get the config, merging defaults in so that all keys in the array are
	 * present.  This also applies the crop choices from the DB.
	 *
	 * @return array
	 */
	public function getConfig() {

		// Create default keys for the config
		$config = array_merge([
			'width'   => null,
			'height'  => null,
			'options' => null,
		], $this->config);

		// Add crops
		if ($crop = $this->getAttribute('crop')) {
			if (!is_array($config['options'])) $config['options'] = [];
			$config['options']['trim_perc'] = [
				round($crop->x1, 4),
				round($crop->y1, 4),
				round($crop->x2, 4),
				round($crop->y2, 4),
			];
		}

		// Return config
		return $config;
	}

	/**
	 * Output the image URL with any queued Croppa transformations.  Note, it's
	 * possible that "file" is empty, in which case this returns an empty string.
	 * This clears the stored config on every call.
	 *
	 * @return string
	 */
	public function getUrlAttribute() {

		// Clear the instance config so that subsequent calls don't inherit anything
		$config = $this->getConfig();
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
			.$this->getBkgdPosAttribute();
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
	 * Convert the focal_point attribute to a CSS background-position.
	 *
	 * @return string
	 */
	public function getBkgdPosAttribute() {
		if (!$value = $this->getBackgroundPositionAttribute()) return;
		return sprintf('background-position: %s;', $value);
	}

	/**
	 * Convert the focal point to the VALUE portion of the CSS
	 * background-position.  This is also used in the serialization conversion
	 * and is named to be friendly to that format.
	 *
	 * @return string
	 */
	public function getBackgroundPositionAttribute() {
		if (!$point = $this->getAttribute('focal_point')) return;
		return sprintf('%s%% %s%%', $point->x*100, $point->y*100);
	}

	/**
	 * Convenience accessor for the title attribute
	 *
	 * @return string
	 */
	public function getAltAttribute() {
		return $this->getAttribute('title');
	}

	/**
	 * Generate the .5x image URL
	 *
	 * @return string
	 */
	public function getLowAttribute() {
		$config = $this->getConfig();
		return Croppa::url($this->getAttribute('file'),
			round($config['width']/2),
			round($config['height']/2),
			$config['options']
		);
	}

	/**
	 * Generate the 1x image URL
	 *
	 * @return string
	 */
	public function getMediumAttribute() {
		$config = $this->getConfig();
		return Croppa::url($this->getAttribute('file'),
			$config['width'],
			$config['height'],
			$config['options']
		);
	}

	/**
	 * Generate the 2x image URL
	 *
	 * @return string
	 */
	public function getHighAttribute() {
		$config = $this->getConfig();
		return Croppa::url($this->getAttribute('file'),
			$config['width']*2,
			$config['height']*2,
			$config['options']
		);
	}

}
