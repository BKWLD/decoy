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
			$job = $this->sdk->jobs->create(array(
				'input' => $source, 
				'output' => $this->outputsConfig(),
			));
			$this->dispatcher->storeJob($job->id, $this->outputsToHash($job->outputs));

		// Report an error with the encode
		} catch(Services_Zencoder_Exception $e) {
			if (($errors = $e->getErrors()) && is_a($errors, 'Services_Zencoder_Error')) {
				$errors = get_object_vars($errors); // Convert errors object to an array 
			}
			$this->dispatcher->storeError(implode(', ', $errors));
		}

	}

	/**
	 * Create the outputs config by merging the `outputs` config of the encode config
	 * file in with $this->defaults and then massaging into Zencoder's expected forat
	 * 
	 * @return array
	 */
	protected function outputsConfig() {
		return $this->addCommonProps($this->mergeConfigWithDefaults());
	}

	/**
	 * Update the defaults with the user config
	 *
	 * @return array
	 */
	protected function mergeConfigWithDefaults() {

		// Loop though user config and modify the defaults
		$outputs = $this->defaults;
		foreach(Config::get('decoy::encode.outputs') as $key => $config) {

			// If user key doesn't exist in the defaults, it's new, and just
			// straight pass it through
			if (!array_key_exists($key, $outputs)) $outputs[$key] = $config;

			// If there is a user key for one of the defaults but no value, then
			// the delete the output
			else if (empty($config)) unset($outputs[$key]);

			// Else, merge the user config onto the default for that key
			else $outputs[$key] = array_merge($outputs[$key], $config);
		}

		// Return the massaged outputs
		return $outputs;

	}

	/**
	 * Update the config with properties that are common to all outputs
	 *
	 * @param array $config 
	 * @return array 
	 */
	protected function addCommonProps($outputs) {
		$destination = Config::get('decoy::encode.destination');
		foreach($outputs as $label => &$config) {

			// Set a label
			$config['label'] = $label;

			// Destination location as a directory
			$config['base_url'] = $destination;

			// Make the outputs web readable on S3
			$config['public'] = 1;

			// Slower encodes for better quality.  Their docs recommended this
			// which is why I'm using it instead of "1".
			$config['speed'] = 2;
		}

		// Strip the keys from the array at this point, Zencoder doesn't like them
		return array_values($outputs);
	}

	/**
	 * Massage the outputs from Zencoder into a key-val associative array
	 * 
	 * @param array $outputs
	 * @return array
	 */
	protected function outputsToHash($outputs) {
		// FINISH THIS
		return $outputs;
	}

}