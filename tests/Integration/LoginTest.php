<?php
namespace Tests\Integration;

use Auth;
use Bkwld\Decoy\Models\Admin;
use Tests\TestCase;

class LoginTest extends TestCase
{

    public function testLoginScreen()
    {
        $response = $this->get('admin');
        $response->assertResponseStatus(200);
    }

    public function testInvalidLogin()
    {
        $response = $this->post('admin', [
            'email' => 'test@domain.com',
            'password' => 'pass',
        ], [
            'HTTP_REFERER' => url('admin'), // So it redirects back to self
        ]);

        // Check that user is redirected back to login but there are errors
        $response->assertRedirectedToRoute('decoy::account@login');
        $response->assertSessionHasErrors();
    }

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
        $response->assertRedirectedToRoute('decoy::account@login');

        // Check that we're logged in now
        $this->assertTrue(Auth::check());
        $this->assertEquals('test@domain.com', Auth::user()->email);
    }

    public function testRedirectAfterLogin()
    {
        $this->auth();

        // Should redirect to the first route in the config
        $response = $this->get('admin');
        $response->assertRedirectedTo('admin/elements');

    }
}
