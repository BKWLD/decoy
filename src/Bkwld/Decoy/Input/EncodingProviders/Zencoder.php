<?php namespace Bkwld\Decoy\Input\EncodingProviders;

// Dependencies
use Bkwld\Decoy\Exception;
use Config;
use Services_Zencoder;
use Services_Zencoder_Exception;

/**
 * Encode videos with Zencoder
 */
class Zencoder extends EncodingProvider {

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
	 * Tell the service to encode an asset it's source
	 *
	 * @param string $source A URI for the source asset
	 * @return void 
	 */
	public function encode($source) {

		// Tell the Zencoder SDK to create a job
		try {
			$sdk = new Services_Zencoder(Config::get('decoy::encode.api_key'));
			$job = $sdk->jobs->create(array(
				'input' => $source, 
				'output' => $this->outputsConfig(),
			));

			// Store the response from the SDK
			$this->model->storeJob($job->id, $this->outputsToHash($job->outputs));

		// Report an error with the encode
		} catch(Services_Zencoder_Exception $e) {
			if (($errors = $e->getErrors()) && is_a($errors, 'Services_Zencoder_Error')) {
				$errors = get_object_vars($errors); // Convert errors object to an array 
			}
			$this->model->storeError(implode(', ', $errors));
		} catch(Exception $e) {
			$this->model->storeError($e->getMessage());
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

			// Register for notifications for when the conding is done
			$config['notifications'] = array($this->notificationURL());
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