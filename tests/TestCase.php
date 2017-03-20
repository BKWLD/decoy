<?php
namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends LaravelTestCase
{
    use MockeryPHPUnitIntegration; // Increments assertion count

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../example/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
