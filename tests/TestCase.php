<?php
namespace Tests;

use Bkwld\Decoy\Models\Admin;
use Cache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Vfs\VfsAdapter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use VirtualFileSystem\FileSystem as Vfs;

abstract class TestCase extends LaravelTestCase
{
    use DatabaseMigrations,
        MockeryPHPUnitIntegration; // Increments assertion count

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Flysystem disk that Decoy will write to
     *
     * @var Flysystem
     */
    protected $disk;

    /**
     * Common setUp tasks
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockDisk();
    }

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

    /**
     * Authenticate an admin
     *
     * @return void
     */
    protected function auth()
    {
        $this->actingAs(Admin::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'test@domain.com',
            'password' => 'pass',
        ]), 'decoy');
    }

    /**
     * Helper for creating the header that Request::ajax() looks for
     *
     * @return array
     */
    protected function ajaxHeader()
    {
        return [ 'X-Requested-With' => 'XMLHttpRequest' ];
    }

    /**
     * Create a fake disk for filesystem tests and override the uphuck disk
     * with it so no actual files get written to the filesystem.
     *
     * @return
     */
    protected function mockDisk()
    {
        $this->app->singleton('upchuck.disk', function($app) {
            return $this->disk = new Filesystem(new VfsAdapter(new Vfs));
        });
    }

    /**
	 * Clear the cache after every test
	 */
	public function tearDown() {
		Cache::flush();
        parent::tearDown();
	}
}
