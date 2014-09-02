<?php namespace Bkwld\Decoy\Input\EncodingProviders;

// Dependencies
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Models\Encoding;
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

		// For HLS encoding, inspired by:
		// https://app.zencoder.com/docs/guides/encoding-settings/http-live-streaming
		// This is intentionally before the normal HTML5 formats so the eventual
		// video tag has the playlist source first.
		'hls-240' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'baseline',
			'width': 400,
			'decoder_bitrate_cap': 300,
			'decoder_buffer_size': 800,
			'video_bitrate': 200,
			'max_frame_rate': 15,
			
		],
		'hls-440' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'baseline',
			'width': 400,
			'decoder_bitrate_cap': 600,
			'decoder_buffer_size': 1600,
			'video_bitrate': 400,
		],
		'hls-640' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'main',
			'width': 480,
			'decoder_bitrate_cap': 900,
			'decoder_buffer_size': 2400,
			'video_bitrate': 600,
		],
		'hls-1040' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'main',
			'width': 640,
			'decoder_bitrate_cap': 1500,
      'decoder_buffer_size': 4000,
      'video_bitrate': 1000,
		],
		'hls-1540' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'high',
			'width': 960,
			'decoder_bitrate_cap': 2250,
      'decoder_buffer_size': 6000,
      'video_bitrate': 1500,
		],
		'hls-2040' => [
			'type' => 'segmented',
			'format' => 'ts',
			'h264_profile' => 'high',
			'width': 1024,
			'decoder_bitrate_cap': 3000,
			'decoder_buffer_size': 8000,
			'video_bitrate': 2000,
		],
		'playlist' => [
			'streams' => [
				[ 'bandwidth' => 2040, 'path' => 'hls-2040.m3u8'],
				[ 'bandwidth' => 1540, 'path' => 'hls-1540.m3u8'],
				[ 'bandwidth' => 1040, 'path' => 'hls-1040.m3u8'],
				[ 'bandwidth' => 640, 'path' => 'hls-640.m3u8'],
				[ 'bandwidth' => 440, 'path' => 'hls-440.m3u8'],
				[ 'bandwidth' => 240, 'path' => 'hls-240.m3u8'],
			],
			'type' => 'playlist',
			'format' => 'm3u8',
		],

		// Normal HTML5 formats
		'mp4' => [
			'format' => 'mp4',
			'h264_profile' => 'main',
		],
		'webm' => [
			'format' => 'webm',
		],
	);

	/**
	 * Tell the service to encode an asset it's source
	 *
	 * @param string $source A full URL for the source asset
	 * @return void 
	 */
	public function encode($source) {

		// Tell the Zencoder SDK to create a job
		try {
			$outputs = $this->outputsConfig();
			$job = $this->sdk()->jobs->create(array(
				'input' => $source, 
				'output' => $this->outputsConfig($outputs),
			));

			// Store the response from the SDK
			$this->model->storeJob($job->id, $this->outputsToHash($job->outputs));

		// Report an error with the encode
		} catch(Services_Zencoder_Exception $e) {
			$this->model->status('error', implode(' ', $this->zencoderArray($e->getErrors())));
		} catch(Exception $e) {
			$this->model->status('error', $e->getMessage());
		}

	}

	/**
	 * Create the outputs config by merging the `outputs` config of the encode config
	 * file in with $this->defaults and then massaging into Zencoder's expected forat
	 * 
	 * @return array
	 */
	protected function outputsConfig() {
		return $this->addCommonProps(
			$this->filterHLS(
				$this->mergeConfigWithDefaults()
		));
	}

	/**
	 * If the playlist is set to `false` then remove all the HLS encodings.  HLS
	 * is the "http live streaming" outputs that let us serve a video that can adjust
	 * quality levels in response to the bandwidth of the clietn.  Works only on iPhone,
	 * Android, and Safari right now.
	 *
	 * @param array $config A config assoc array
	 * @return array
	 */
	protected function filterHLS($config) {
		
		// Do not allow any outputs that have a type of "segmented" or "playlist"
		if (empty($config['playlist'])) return array_filter($config, function($output) {
			return !(isset($output['type']) && in_array($output['type'], ['segmented', 'playlist']));
		});

		// Else, passthrough the config
		else return $config;
	}

	/**
	 * Update the config with properties that are common to all outputs
	 *
	 * @param array $config 
	 * @return array 
	 */
	protected function addCommonProps($outputs) {

		// Common settings
		$common = [

			// Default width (960x540 at 16:9)
			'width' => 960,

			// Destination location as a directory
			'base_url' => $this->destination(),

			// Make the outputs web readable on S3
			'public' => 1,

			// Slower encodes for better quality.  Their docs recommended this
			// which is why I'm using it instead of "1".
			'speed' => 2,

			// Normalize audio
			'audio_bitrate': 56,
			'audio_sample_rate': 22050,

			// Register for notifications for when the conding is done
			'notifications' => array($this->notificationURL()),

		];

		// Apply common settings ontop of the passed config
		foreach($outputs as $label => &$config) {
			$common['label'] = $label;

			// Make the filename from the label
			$common['filename'] = $label.'.'.$config['format'];

			// Do the merge
			$config = array_merge($common, $config);
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
		return array_map(function($output) {
			return $output->url;
		}, $this->zencoderArray($outputs));
	}

	/**
	 * Handle notification requests from the SDK
	 *
	 * @param array $input Input::get()
	 * @return mixed Reponse to the API 
	 */
	public function handleNotification($input) {

		// Parse the input
		$job = $this->sdk()->notifications->parseIncoming()->job;

		// Find the encoding model instance.  If it's not found, then just
		// ignore it.  This can easily happen if someone replaces a video
		// while one is being uploaded.
		if (!$model = Encoding::where('job_id', '=', $job->id)->first()) return;

		// Loop through the jobs and look for error messages.  A job may recieve a
		// seperate notification for each output that has failed though the job
		// is still processessing.
		$errors = trim(implode(' ', array_map(function($output) {
			return isset($output->error_message) ? '(Output '.$output->label.') '.$output->error_message : null;
		}, $this->zencoderArray($job->outputs))));

		// If there were any messages, treat the job as errored.  This also tries
		// to fix an issue I saw where a final "error" notifcation wasn't fired even
		// though multiple jobs failed.
		$state = empty($errors) ? $job->state : 'failed';

		// Update the model
		switch($state) {
			
			// Simple passthru of status
			case 'processing';
			case 'cancelled';
				$model->status($job->state); 
				break;
			
			// Massage name
			case 'finished':
				$model->status('complete'); 
				break;
			
			// Find error messages on the output
			case 'failed':
				$model->status('error', $errors);
				break;
			
			// Default
			default:
				$model->status('error', 'Unkown Zencoder state: '.$job->state);
		}
	}

	/**
	 * Return the encoding percentage as an int
	 *
	 * @return int 0-100
	 */
	public function progress() {
		try {
			$progress = $this->sdk()->jobs->progress($this->model->job_id);
			if ($progress->state == 'finished') return 100;
			else return $progress->progress;
			return $progress;
		} catch (\Exception $e) {
			return 0;
		}
	}

	/**
	 * Build an instance of the SDK
	 *
	 * @return Services_Zencoder
	 */
	public function sdk() {
		return new Services_Zencoder(Config::get('decoy::encode.api_key'));
	}

	/**
	 * Convert a Services_Zencoder_Object object to an array
	 *
	 * @param Services_Zencoder_Object|array $obj
	 * @return array 
	 */
	public function zencoderArray($obj) {
		if (is_array($obj)) return $obj;
		if (is_a($obj, 'Services_Zencoder_Object')) return get_object_vars($obj);
		throw new Exception('Unexpected object: '.get_class($obj));
	}

}