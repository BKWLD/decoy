<?php return array(

	/**
	 * A class that implements the Bkwld\Decoy\Input\EncodingProviders\EncoderInterface
	 * interface and contains the logic to push a video encode request to service
	 * provider and handle the responses
	 */
	'class' => '\Bkwld\Decoy\Input\EncodingProviders\Zencoder',

	/**
	 * The API key used to access the specified provider
	 */
	'api_key' => 'REQUIRED',

	/**
	 * The destination endpoint.
	 */
	'destination' => 'REQUIRED',

	/**
	 * An object that is used to tell the encoding provider how to prepare
	 * it's outputs
	 */
	'outputs' => array(
		array(
			'format' => 'mp4',
		),
		array(
			'format' => 'webm',
		),
	),

);