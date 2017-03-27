<?php

namespace Bkwld\Decoy\Input\EncodingProviders;

use Config;
use Request;
use Illuminate\Support\Str;
use Bkwld\Decoy\Models\Encoding;
use Bkwld\Decoy\Exceptions\Exception;

/**
 * Base class for encoding providers that provides some shared logic
 * and defines abstract methods that must be implemented
 */
abstract class EncodingProvider
{
    /**
     * Default outputs configuration.  These should be overridden
     * by the provider.
     *
     * @var array
     */
    protected $defaults = [];

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
    public function __construct(Encoding $model = null)
    {
        $this->model = $model;
    }

    /**
     * Produce the destination directory
     *
     * @return string
     */
    protected function destination()
    {
        return Config::get('decoy.encode.destination').'/'.Str::random(32).'/';
    }

    /**
     * Tell the service to encode an asset it's source
     *
     * @param  string $source A full URL for the source asset
     * @param  string $preset The key to the preset function
     * @return void
     */
    abstract public function encode($source, $preset);

    /**
     * Handle notification requests from the SDK
     *
     * @param  array $input Request::input()
     * @return mixed Reponse to the API
     */
    abstract public function handleNotification($input);

    /**
     * Update the default configwith the user config
     *
     * @param  string $preset
     * @return array
     *
     * @throws Exception
     */
    protected function mergeConfigWithDefaults($preset)
    {
        // Get the preset settings
        if (!$settings = Config::get('decoy.encode.presets.'.$preset.'.settings')) {
            throw new Exception('Encoding preset not found: '.$preset);
        }

        // If the settings are an assoc array, then there is only one output and it
        // needs to be wrapped in an array
        if (!is_numeric(array_keys($settings)[0])) {
            $settings = ['mp4' => $settings];
        }

        // Merge defaults with each output in the settings
        return array_map(function ($output) {
            return array_merge($this->defaults, $output);
        }, $settings);
    }

    /**
     * Return the encoding percentage as an int
     *
     * @return int 0-100
     */
    abstract public function progress();
}
