<?php namespace Bkwld\Decoy\Input\EncodingProviders;

// Dependencies
use Config;
use Bkwld\Decoy\Input\EncodeDispatcher;
use Services_Zencoder;
use Services_Zencoder_Exception;

/**
 * Encode videos with Zencoder
 */
class Zencoder {

	/**
	 * Default outputs configuration
	 *
	 * @var array
	 */
	protected $defaults = array(
		'mp4' => array(
			'format' => 'mp4',
		), 
		'webm' => array(
			'format' => 'webm',
		),
	);

	/**
	 * The dispatcher instance that invoked this provider
	 *
	 * @var Bkwld\Decoy\Input\EncodeDispatcher
	 */
	protected $dispatcher;

	/**
	 * An instance of the official SDK
	 *
	 * @var Services_Zencoder
	 */
	protected $sdk;

	/**
	 * Inject dependencies
	 *
	 * @param Bkwld\Decoy\Input\EncodeDispatcher $encoder 
	 */
	public function __construct(EncodeDispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
		$this->sdk = new Services_Zencoder(Config::get('decoy::encode.api_key'));
	}

	/**
	 * Tell the service to encode an asset it's source
	 *
	 * @param string $source A URI for the source asset that can be resolved
	 *                       by the dispatcher
	 * @return void 
	 */
	public function encode($source) {
		
		// Try to create a job
		try {
			print_r($this->outputsConfig());
			// $job = $this->sdk->jobs->create(array('input' => $source), $this->outputsConfig());
			// $this->dispatcher->storeJob($job->id, $this->outputsToHash($job->outputs));

		// Report an error with the encode
		} catch(Services_Zencoder_Exception $e) {
			$this->encoder->storeError(implode(', ', $e->getErrors()));
		}

	}

	/**
	 * Create the outputs config by merging the `outputs` config of the encode config
	 * file in with $this->defaults and then massaging into Zencoder's expected forat
	 * 
	 * @return array
	 */
	protected function outputsConfig() {

		// Remove any outputs that have been empty-ed in the config file
		
		/**
		 * Foreach default
		 * 	if config for the key is empty, delete it
		 * 	else if key exists, merge it
		 */
		
		// Apply settings that are common to all outputs

		// Merge config file ontop of defaults

		// Return

	}

	/**
	 * Massage the outputs from Zencoder into a key-val associative array
	 * 
	 * @param array $outputs
	 * @return array
	 */
	protected function outputsToHash($outputs) {
		// FINISH THIS
	}

}