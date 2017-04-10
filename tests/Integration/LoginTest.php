<?php
namespace Tests\Integration;

use Auth;
use Bkwld\Decoy\Models\Admin;
use Tests\TestCase;

class LoginTest extends TestCase
{

    /**
     * Test viewing the login screen
     *
     * @return void
     */
    public function testLoginScreen()
    {
        $response = $this->get('admin');
        $response->assertStatus(200);
    }

    /**
     * Test posting invalid creds
     *
     * @return void
     */
    public function testInvalidLogin()
    {
        $response = $this->post('admin', [
            'email' => 'test@domain.com',
            'password' => 'pass',
        ], [
            'HTTP_REFERER' => url('admin'), // So it redirects back to self
        ]);

        // Check that user is redirected back to login but there are errors
        $response->assertRedirect('admin');
        $response->assertSessionHasErrors();
    }

    /**
     * Test a valid login
     *
     * @return void
     */
    public function testValidLogin()
    {
        Admin::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'test@domain.com',
            'password' => 'pass',
        ]);

        // Confirm not initially logged in
        $this->assertFalse(Auth::check());

        // Log in the user
        $response = $this->post('admin', [
            'email' => 'test@domain.com',
            'password' => 'pass',
        ]);

        // The response redirects back to the login page. A middleware will
        // then redirect to the first page of the admin
        $response->assertRedirect('admin');

        // Check that we're logged in now
        $this->assertTrue(Auth::check());
        $this->assertEquals('test@domain.com', Auth::user()->email);
    }

    /**
     * Test redirect after login
     *
     * @return void
     */
    public function testRedirectAfterLogin()
    {
        $this->auth();

        // Should redirect to the first route in the config
        $response = $this->get('admin');
        $response->assertRedirect('admin/articles');

    }

    /**
     * Test redirect after login when there is a config value set for where
     * the user should be redirected to.
     *
     * @return void
     */
    public function testRedirectAfterLoginToExplicitlyDefinedUrl()
    {
        $this->auth();

        config()->set('decoy.site.post_login_redirect', '/admin/something');

        // Should redirect to the first route in the config
        $response = $this->get('admin');
        $response->assertRedirect('admin/something');

    }
}
