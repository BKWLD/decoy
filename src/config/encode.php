<?php return array(

	/**
	 * A class that implements the Bkwld\Decoy\Input\EncodingProviders\EncoderInterface
	 * interface and contains the logic to push a video encode request to service
	 * provider and handle the responses
	 */
	'provider' => '\Bkwld\Decoy\Input\EncodingProviders\Zencoder',

	/**
	 * The API key used to access the specified provider
	 */
	'api_key' => 'REQUIRED',

	/**
	 * The destination endpoint.
	 */
	'destination' => 'REQUIRED',

	/**
	 * A associative array that can be used to remove or override the default
	 * output configuration that is defined in the specified encoding provider
	 */
	'outputs' => array(),

);