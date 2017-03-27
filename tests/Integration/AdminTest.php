<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Bkwld\Decoy\Models\Admin;

class AdminTest extends TestCase
{

    /**
     * @var Article
     */
    protected $article;

    /**
     * Seed with a tag and article
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->actingAs(Admin::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'test@domain.com',
            'password' => 'pass',
            'role' => 'viewer',
        ]), 'decoy');

        $this->article = factory(Article::class)->create();
    }

    /**
     * Test the admin can permissions
     *
     * @return void
     */
    public function testAdminCanPermissions()
    {
        $response = $this->get('admin/articles/1/edit');
        $this->assertResponseOk();
    }

    /**
     * Test the admin can't permissions
     */
    public function testAdminCantPermissions()
    {
        $article = Article::first();
        $this->assertEquals(1, $article->id);
        $response = $this->get('admin/articles/1/delete');
        $this->assertResponseStatus(403);
    }

    /**
     * Test the specific admin disable button for admins
     *
     * @return void
     */
    public function testAdminDisableAdmins()
    {
        $new_admin = factory(Admin::class)->create();
        $this->assertEquals(2, $new_admin->id);

        $response = $this->get('admin/admins/2/disable');
        $this->assertResponseStatus(403);
    }

}
