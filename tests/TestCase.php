<?php
namespace Tests;

use Auth;
use Bkwld\Decoy\Models\Admin;
use Cache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Http\UploadedFile;
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
        set_time_limit(1200);
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
     * Logout an admin
     *
     * @return void
     */
    protected function logout()
    {
        Auth::guard('decoy')->logout();
        app()->forgetInstance('decoy.user');
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
     * Create a UploadedFile instance to work with
     *
     * @param  string $filename
     * @return UploadedFile
     */
    protected function createUploadedFile($filename = null)
    {
        if (!$filename) $filename = 'test.jpg';

        // Create an image in the tmp directory where Upchuck is expecting it
        $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $file_path = $tmp_dir.'/'.$filename;
        if (!file_exists($file_path)) {
            $file = imagecreatetruecolor(20, 20);
            imagepng($file, $file_path);
            imagedestroy($file);
        }

        return new UploadedFile(
            $file_path,
            basename($file_path),
            'image/png',
            null,
            null,
            true
        );
    }

    /**
     * Create a virtual file to work with
     *
     * @param  string $filename
     * @return string
     */
    protected function createVirtualFile($filename = null)
    {
        if (!$filename) $filename = 'test.jpg';

        // Make image
        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($img);
        $this->disk->put($filename, ob_get_clean());
        imagedestroy($img);
    }

    /**
	 * Clear the cache after every test
	 */
	public function tearDown() {
		Cache::flush();
        parent::tearDown();
	}
}
