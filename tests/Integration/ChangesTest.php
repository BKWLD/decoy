<?php
namespace Tests\Integration;

use App\Tag;
use Bkwld\Decoy\Models\Change;
use Cache;
use Carbon\Carbon;
use Decoy;
use Tests\TestCase;

class ChangesTest extends TestCase
{

    /**
     * The tag that triggreed changes
     *
     * @var Tag
     */
    protected $tag;

    /**
     * Common init
     *
     * @return void
     */
    protected function setUp() {
        parent::setUp();
        $this->auth();

        // Create a series of Change records through messing with the soft
        // deleting Tag model
        $this->tag = factory(Tag::class)->create([ 'name' => 'Name' ]);
        $this->tag->name = 'New name';
        $this->tag->save();
        $this->tag->delete();
    }

    /**
     * Test that the commands page loads
     *
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('admin/changes');
        $response->assertStatus(200);

        // Test that all the changes from the setUp were created
        $this->assertEquals(3, Change::count());
    }

    /**
     * Test that the edit response returns JSON
     *
     * @return void
     */
    public function testCreatedEdit()
    {
        $response = $this->get('admin/changes/1/edit');
        $response
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Name',
                'action' => 'created',
            ]);
    }

    /**
     * Test that the edit response returns JSON
     *
     * @return void
     */
    public function testUpdatedEdit()
    {
        $response = $this->get('admin/changes/2/edit');
        $response
            ->assertStatus(200)
            ->assertJson([
                'title' => 'New name',
                'action' => 'updated',
            ]);
    }


}
