<?php return array(

	/**
	 * The API key used to access the specified provider
	 */
	'api_key' => 'REQUIRED',

	/**
	 * The destination directory.  In other words, where the encoded video files
	 * should be pushed to.
	 * Ex: s3://bucket-name/directory
	 * Ex: sftp://xx.xx.xx.xx:xx/home/gopagoda/data/public/uploads/encodes
	 */
	'destination' => 'REQUIRED',

	/**
	 * If not empty, subsitute the destiation path that is returned from the
	 * encoder with this.  If this is an absolute path (like the example), then
	 * Decoy will try and remove the encodes if the encode is deleted.
	 * Ex: /uploads/encodes
	 */
	'destination_root' => '',

	/**
	 * Settings that can be applied uniquely to each video instance.
	 */
	'presets' => [

		'1080p' => [
			'title' => '1080p (quality: 2)',
			'settings' => [
				'quality' => 2,
				'height' => '1080',
			],
		],

		'1080pq3' => [
			'title' => '1080p (quality: 3)',
			'settings' => [
				'quality' => 3,
				'height' => '1080',
			],
		],

		'720p' => [
			'title' => '1080p (quality: 2)',
			'settings' => [
				'quality' => 2,
				'height' => '720',
			],
		],

	],

	/**
	 * A class that implements the Bkwld\Decoy\Input\EncodingProviders\EncoderInterface
	 * interface and contains the logic to push a video encode request to service
	 * provider and handle the responses
	 */
	'provider' => '\Bkwld\Decoy\Input\EncodingProviders\Zencoder',

);
