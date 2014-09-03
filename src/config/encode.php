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
	 * A associative array that can be used to remove or override the default
	 * output configuration that is defined in the specified encoding provider
	 */
	'outputs' => array(),

	/**
	 * A class that implements the Bkwld\Decoy\Input\EncodingProviders\EncoderInterface
	 * interface and contains the logic to push a video encode request to service
	 * provider and handle the responses
	 */
	'provider' => '\Bkwld\Decoy\Input\EncodingProviders\Zencoder',

);