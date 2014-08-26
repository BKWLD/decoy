<?php namespace Bkwld\Decoy\Input\EncodingProviders;

// Dependencies
use App;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Models\Encoding;
use Config;
use Request;

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
	 * Tell the service to encode an asset it's source
	 *
	 * @param string $source A full URL for the source asset
	 * @param Bkwld\Decoy\Models\Encoding $model 
	 * @return void 
	 */
	abstract public function encode($source, Encoding $model);
	
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
		if (!($ip = gethostbyname($host)) || preg_match('#^(127)|(10)|(192\.168)#', $ip)) {
			throw new Exception('The server name ('.$host.') does not appear to be publicly accessible.  If running from CLI, pass the server name in via ENV variables like: `SERVER_NAME=10147f98.ngrok.com php artisan your:command`.');
		}

		// Produce the route, passing in the host explicitly.  This allows CLI invocations to
		// be supported.
		if (!App::runningInConsole()) return route('decoy\encode@notify');
		else {
			$generator = app('url');
			$generator->forceRootUrl('http://'.$host);
			$url = $generator->route('decoy\encode@notify');
			$generator->forceRootUrl(null);
			return $url;
		}
	}

}