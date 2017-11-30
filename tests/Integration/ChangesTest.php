<?php
namespace Tests\Integration;

use App\Article;
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
     * Test that the commands page loads and all 3 changes were made
     *
     * @return void
     */
    public function testIndex()
    {
        $this->get('admin/changes')->assertStatus(200);
        $changes = Change::where('model', 'App\Tag')
            ->where('key', $this->tag->id)
            ->get();
        $this->assertEquals(3, $changes->count());
    }

    /**
     * Test that the edit response returns JSON
     *
     * @return void
     */
    public function testCreatedEdit()
    {
        // Admin create change was created first as id = 1
        $response = $this->get('admin/changes/2/edit');
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
        $response = $this->get('admin/changes/3/edit');
        $response
            ->assertStatus(200)
            ->assertJson([
                'title' => 'New name',
                'action' => 'updated',
            ]);
    }

    /**
     * Test viewing old chages by getting the preview url of the first change
     * and checking that response json for it has the orginal name
     *
     * @return void
     */
    public function testPreview()
    {
        $response = $this->get(Change::find(2)->preview_url);
        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->tag->id,
                'name' => 'Name',
            ]);
    }

    /**
     * Test that an admin is optional by logging out, creating an Article, and
     * then verifying that the changes were created and don't error.
     *
     * @return void
     */
    public function testNoAdmin()
    {
        // Verify no logout change was written for admin
        $this->logout();
        $this->assertEquals(1,
            Change::where('model', 'Bkwld\Decoy\Models\Admin')->count());

        // Create the article
        $article = factory(Article::class)->create();

        // Did Change get written?
        $changes = Change::where('model', 'App\Article')
            ->where('key', $article->id)
            ->get();
        $this->assertEquals(1, $changes->count());
        $change = $changes->first();
        $this->assertNull($change->admin_id);

        // Are views fine?
        $this->auth();
        $this->get('admin/changes')->assertStatus(200);
        $this->get('admin/changes/'.$change->id.'/edit')->assertStatus(200);
    }


}
