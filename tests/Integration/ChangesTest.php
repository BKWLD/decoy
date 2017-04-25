<?php
namespace Tests\Integration;

use Cache;
use Carbon\Carbon;
use Decoy;
use Tests\TestCase;

class ChangesTest extends TestCase
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
    public function testChangesIndex()
    {
        $response = $this->get('admin/changes');
        $response->assertStatus(200);
    }


}
