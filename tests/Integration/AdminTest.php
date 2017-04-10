<?php
namespace Tests\Integration;

use App\Article;
use Bkwld\Decoy\Models\Admin;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

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
        $response->assertStatus(200);
    }

    /**
     * Test the admin can't permissions
     */
    public function testAdminCantPermissions()
    {
        $article = Article::first();
        $this->assertEquals(1, $article->id);
        $response = $this->get('admin/articles/1/delete');
        $response->assertStatus(403);
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
        $response->assertStatus(403);
    }

    /**
     * Test the reset password flow
     *
     * @return void
     */
    public function testResetPasswordIndex()
    {
        $response = $this->get('admin/forgot');

        $response->assertStatus(200);
    }

    /**
     * Test the reset password submit button works
     *
     * @return void
     */
    public function testResetPasswordSubmit()
    {
        $response = $this->call('POST', 'admin/forgot', [
            'email' => 'test@domain.com',
        ]);

        $response->assertStatus(302);
    }

    /**
     * Test reset password form
     *
     * @return void
     */
    public function testResetPasswordFormIndex()
    {
        $token = Str::random(60);
        DB::table('password_resets')->insert([
            'email' => 'test@domain.com',
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->get('admin/password/reset/'.$token);
        $response->assertStatus(200);
    }

    /**
     * Test that the reset password form works
     *
     * @return void
     */
    public function testResetPasswordFormSave()
    {
        // Get current admin
        $admin = Admin::findOrFail(1);

        // Write reset token to database and get the emailed token back.
        // Ordinarily, this gets called eventually by the forgot controller.
        $tokens = Password::broker()->getRepository();
        $token = $tokens->create($admin);

        // Post the reset form
        $response = $this->post('admin/password/reset/'.$token, [
            'email' => 'test@domain.com',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
            'token' => $token,
        ]);

        // Should redirect to to self
        $response->assertStatus(302);

        // Check that the admin has a new password now
        $new_password = Admin::findOrFail(1)->password;
        $this->assertNotEquals($admin->password, $new_password);
        $this->assertEmpty(DB::table('password_resets')
            ->where('email', 'test@domain.com')->get());
    }

}
