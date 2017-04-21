<?php
namespace Tests\Integration;

use Cache;
use Carbon\Carbon;
use Decoy;
use Tests\TestCase;

class CommandsTest extends TestCase
{

    /**
     * Common init
     *
     * @return void
     */
    protected function setUp() {
        parent::setUp();
        $this->auth();
    }

    /**
     * Test that the commands page loads
     *
     * @return void
     */
    public function testCommandsIndex()
    {
        $response = $this->get('admin/commands');
        $response->assertStatus(200);
    }

    /**
     * Test that the cache:clear command is working properly
     *
     * @return void
     */
    public function testCacheClearCommand()
    {
        // Setup cache
        Cache::put('key', 'value', Carbon::now()->addMinutes(10));

        // Make sure the value is in the cache
        $this->assertEquals('value', Cache::get('key'));

        // Run the clear command
        $response = $this->post('admin/commands/cache:clear');

        // Command should 200 first
        $response->assertStatus(200);

        // Cache should be empty
        $this->assertEmpty(Cache::get('key'));

    }

}
