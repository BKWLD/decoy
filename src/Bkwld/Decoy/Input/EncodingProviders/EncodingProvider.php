<?php namespace Bkwld\Decoy\Input\EncodingProviders;

// Dependencies
use App;
use Bkwld\Decoy\Exceptions\Exception;
use Bkwld\Decoy\Models\Encoding;
use Config;
use Request;
use Illuminate\Support\Str;

/**
 * Base class for encoding providers that provides some shared logic
 * and defines abstract methods that must be implemented
 */
abstract class EncodingProvider {

	/**
	 * Default outputs configuration.  These should be overridden
	 * by the provider.
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * The Encoding model instance that this encode is related to
	 *
	 * @var Bkwld\Decoy\Models\Encoding
	 */
	protected $model;

	/**
	 * Inject dependencies
	 * 
	 * @param Bkwld\Decoy\Models\Encoding $model
	 */
	public function __construct(Encoding $model = null) {
		$this->model = $model;
	}

	/**
	 * Produce the destination directory
	 *
	 * @return string 
	 */
	protected function destination() {
		return Config::get('decoy::encode.destination').'/'.Str::random(32).'/';
	}

	/**
	 * Tell the service to encode an asset it's source
	 *
	 * @param string $source A full URL for the source asset
	 * @return void 
	 */
	abstract public function encode($source);
	
	/**
	 * Handle notification requests from the SDK
	 *
	 * @param array $input Input::get()
	 * @return mixed Reponse to the API 
	 */
	abstract public function handleNotification($input);

	/**
	 * Update the default configwith the user config
	 *
	 * @return array
	 */
	protected function mergeConfigWithDefaults() {

		// Store config options that should be applied to all outputs
		$common = [];

		// Loop though user config and modify the defaults
		$outputs = $this->defaults;
		foreach(Config::get('decoy::encode.outputs') as $key => $config) {

			// If there is a user key for one of the defaults but no value, then
			// the delete the output
			if (empty($config)) unset($outputs[$key]);

			// If there the config option is not not an array, then it is a setting
			// for ALL outputs
			else if (!is_array($config)) $common[$key] = $config;

			// If user key doesn't exist in the defaults, it's new, and just
			// straight pass it through
			else if (!array_key_exists($key, $outputs)) $common[$key] = $config;

			// Else, merge the user config onto the default for that key
			else $outputs[$key] = array_merge($outputs[$key], $config);
		}

		// Apply common settings to all outputs
		foreach($outputs as &$output) $output = array_merge($output, $common);

		// Return the massaged outputs
		return $outputs;

	}

	/**
	 * Get the notifications URL
	 *
	 * @return string 
	 */
	protected function notificationURL() {

		// Get the host name from env variable if running through CLI
		$host = App::runningInConsole() && isset($_SERVER['SERVER_NAME']) 
			? $_SERVER['SERVER_NAME'] 
			: Request::getHost();

		// Verify that the host is public
		if (!($ip = gethostbyname($host)) || preg_match('#^(127|10|192\.168)#', $ip)) {
			throw new Exception('The server name ('.$host.') does not appear to be publicly accessible.  It is recommended to use <a href="https://ngrok.com/">ngrok</a> to access your localhost.  If running from CLI, pass the server name in via ENV variable like: `SERVER_NAME=10147f98.ngrok.com php artisan your:command`.');
		}

		// Produce the route, passing in the host explicitly.  This allows CLI invocations to
		// be supported.
		if (!App::runningInConsole()) return route('decoy::encode@notify');
		else {
			$generator = app('url');
			$generator->forceRootUrl('http://'.$host);
			$url = $generator->route('decoy::encode@notify');
			$generator->forceRootUrl(null);
			return $url;
		}
	}

	/**
	 * Return the encoding percentage as an int
	 *
	 * @return int 0-100
	 */
	abstract public function progress();

}