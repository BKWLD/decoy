<?php

namespace Bkwld\Decoy\Models;

use Log;
use Cache;
use Bkwld\Library;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Workers are tasks that define logic designed to be run as a never
 * ending worker routine.  Commands can and should extend this class.
 * The command's fire() method will be executed on every tick of the
 * worker.
 */
class Worker extends \Illuminate\Console\Command
{
    // Worker settings
    protected $WORKER_SLEEP_SECS = 60;   // How many seconds to wait before each worker exec
    protected $HEARTBEAT_FAIL_MINS = 30; // The age in after which the worker is deemed failed
    protected $HEARTBEAT_WORKER_KEY;     // The key that the worker heartbeat is stored as
    protected $HEARTBEAT_CRON_KEY;       // The key that the cron heartbeat is stored as

    /**
     * Constructor susses out default properties.  The commands that subclass this would also use the
     * constructor to pass dependencies and to do any one time bootstrapping.
     */
    public function __construct()
    {
        parent::__construct();

        // Base the cache keys off the class name
        if (empty($this->HEARTBEAT_WORKER_KEY)) {
            $this->HEARTBEAT_WORKER_KEY = 'worker-heartbeat-'.$this->name;
        }

        if (empty($this->HEARTBEAT_CRON_KEY)) {
            $this->HEARTBEAT_CRON_KEY = 'cron-heartbeat-'.$this->name;
        }
    }

    /**
     * Add special worker options
     */
    protected function getOptions()
    {
        return [
            ['worker', null, InputOption::VALUE_NONE, 'Run command as a worker.'],
            ['cron', null, InputOption::VALUE_NONE, 'Run command as cron.'],
            ['heartbeat', null, InputOption::VALUE_NONE, 'Check that the worker is running.'],
        ];
    }

    /**
     * Tap into the Laravel method that invokes the fire command to check for and
     * act on options
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->option('worker')) {
            return $this->worker();
        }

        if ($this->option('cron')) {
            return $this->cron();
        }

        if ($this->option('heartbeat')) {
            return $this->heartbeat();
        }

        return parent::execute($input, $output);
    }

    //---------------------------------------------------------------------------
    // Options for tasks
    // - Child classes must define a work() method and maybe an init()
    // - For the worker, on Pagoda, the worker instance would have:
    //   exec: "php artisan <COMMAND> --worker --env=$LARAVEL_ENV"
    // - Or, if the host is more traditional, start your work with cron by adding this to
    //   your crontab:
    //   * * * * * php artisan <COMMAND> --cron --env=<LARAVEL_ENV>
    // - For the heatbeat, on Pagoda, the Boxfile would have for the worker instance:
    //   cron:
    //      - "* * * * *": "php artisan <COMMAND> --heartbeat --env=$LARAVEL_ENV"
    //---------------------------------------------------------------------------

    /**
     * The worker loop.  This method never ends.  This is the task method that would be called
     * to start a worker when the --worker option is passed
     */
    protected function worker()
    {
        // Bootstrap
        $this->addLogging();

        // Run this stuff as long as the worker is running
        while (true) {
            $this->fire();
            Cache::forever($this->HEARTBEAT_WORKER_KEY, time());
            sleep($this->WORKER_SLEEP_SECS);
        }
    }

    /**
     * Similar to worker(), this runs the worker logic and updates the heartbeat but is designed
     * to be invoked by cron.  Thus, it only runs the work once.
     */
    protected function cron()
    {
        $this->addLogging();
        $this->fire();
        Cache::forever($this->HEARTBEAT_WORKER_KEY, time());
    }

    /**
     * This heartbeat function is called by cron to verify that the worker is still running
     */
    protected function heartbeat()
    {
        // Update the heartbeat
        $last = Cache::get($this->HEARTBEAT_CRON_KEY);
        if (empty($last->interval)) {
            $interval = 'calculating';
        } else {
            $interval = time() - $last->time;
        }

        Cache::forever($this->HEARTBEAT_CRON_KEY, (object) [
            'time' => time(),
            'interval' => $interval,
        ]);

        // The worker has died
        if (!$this->isRunning()) {

            // Log an error that the worker stopped
            $this->addLogging();
            $this->error('The '.$this->name.' worker has stopped');

            // Do work (since the worker has stopped)
            $this->fire();

        // The worker appears to be fine
        } else {
            $this->info('The '.$this->name.' worker is running');
        }
    }

    //---------------------------------------------------------------------------
    // Logging
    //---------------------------------------------------------------------------

    /**
     * Log messages to special worker log file
     */
    private $logger;

    private function addLogging()
    {
        // Simply the formatting of the log
        $output = "%datetime% [%level_name%] %message%\n";
        $formatter = new LineFormatter($output);

        // Create a new log file for this command
        $this->logger = new Logger($this->name);
        $stream = new StreamHandler(self::logPath($this->name), Logger::DEBUG);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);

        // Listen for log events and write to custom worker log.  This code
        // mimics what `Log::listen()` does but allows us to use a callback
        // rather than a closure.
        Log::getEventDispatcher()->listen('illuminate.log', array($this, 'log'));
    }

    /**
     * Write Command output types to the log
     * @param $level
     * @param $message
     * @param array $context
     */
    public function log($level, $message, $context = [])
    {
        // This will be empty when output messages are triggered by the command
        // when it's NOT called by a worker option
        if (empty($this->logger)) {
            return;
        }

        // Call the logger's level specific methods
        $method = 'add'.ucfirst($level);
        $this->logger->$method($message, $context);
    }

    /**
     * Make the path to the log file
     */
    public static function logPath($name)
    {
        return storage_path().'/logs/'.str_replace(':', '-', $name).'.log';
    }

    /**
     * Override the Command output functions so that output also gets put
     * in the log file
     */
    // public function line($str) {     $this->log('info', $str);   parent::line($str); }
    // public function info($str) {     $this->log('info', $str);   parent::info($str); }
    // public function comment($str) {  $this->log('debug', $str);  parent::comment($str); }
    // public function question($str) { $this->log('notice', $str); parent::question($str); }
    // public function error($str) {    $this->log('error', $str);  parent::error($str); }


    //---------------------------------------------------------------------------
    // Queries
    //---------------------------------------------------------------------------

    /**
     * Get all the tasks that have workers
     */
    public static function all()
    {
        $output = array();
        $namespaced = Command::allCustom();
        foreach ($namespaced as $namespace => $commands) {
            foreach ($commands as $title => $command) {
                if (is_a($command, 'Bkwld\Decoy\Models\Worker')) {
                    $output[] = $command;
                }
            }
        }

        return $output;
    }

    /**
     * Check if we're currently failing or not
     */
    public function isRunning()
    {
        return time() - Cache::get($this->HEARTBEAT_WORKER_KEY) < $this->HEARTBEAT_FAIL_MINS * 60;
    }

    /**
     * Last time the heartbeat was checked
     */
    public function lastHeartbeatCheck()
    {
        $check = Cache::get($this->HEARTBEAT_CRON_KEY);
        if (empty($check)) {
            return 'never';
        }

        return date(Library\Utils\Constants::COMMON_DATETIME.' T', $check->time);
    }

    /**
     * The last time the worker ran
     */
    public function lastHeartbeat()
    {
        $check = Cache::get($this->HEARTBEAT_WORKER_KEY);
        if (empty($check)) {
            return 'never';
        }

        return date(Library\Utils\Constants::COMMON_DATETIME.' T', $check);
    }

    /**
     * The current interval that heartbeats are running at
     */
    public function currentInterval($format = null)
    {

        // Relative time formatting
        $abbreviated = [
            'pluraling' => false,
            'spacing' => false,
            'labels' => ['now', 's', 'm', 'h', 'd', 'm', 'y'],
        ];

        // Figure stuff out
        if ($this->isRunning()) {
            $interval = $this->WORKER_SLEEP_SECS;
        } else {
            $check = Cache::get($this->HEARTBEAT_CRON_KEY);
            if (empty($check)) {
                $interval = 'uncertain';
            } else {
                $interval = $check->interval;
            }
        }

        // Format it
        if (!is_numeric($interval)) {
            return $interval;
        }
        switch ($format) {
            case 'raw':
                return $interval;

            case 'abbreviated':
                return Library\Utils\Text::timeElapsed(time() - $interval, $abbreviated);

            default:
                return Library\Utils\Text::timeElapsed(time() - $interval);
        }
    }
}
