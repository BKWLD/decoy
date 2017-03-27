<?php

namespace Bkwld\Decoy\Controllers;

use Bkwld\Decoy\Exceptions\Exception;

/**
 * Check the status of workers from the admin
 */
class Workers extends Base
{
    /**
     * @var string
     */
    public $description = "Monitor whether workers are running or not. The logic of a failed worker is still executed regularly, just at a slower interval.";

    /**
     * Display all the workers
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        return $this->populateView('decoy::workers.index', [
            'workers' => Model::all(),
        ]);
    }

    /**
     * Ajax service that tails the log file for the selected worker
     *
     * @param $worker
     */
    public function tail($worker)
    {
        // Form the path to the file
        $file = Model::logPath(urldecode($worker));
        if (!file_exists($file)) {
            throw new Exception('Log not found: '.$file);
        }
        $size = 1024 * 100; // in bytes to get

        // Read from the end of the file
        clearstatcache();
        $fp = fopen($file, 'r');
        fseek($fp, -$size, SEEK_END);
        $contents = explode("\n", fread($fp, $size));
        fclose($fp);

        // Reverse the contents and return
        $contents = array_reverse($contents);
        if (empty($contents[0])) {
            array_shift($contents);
        }
        die(implode("\n", $contents));
    }
}
