<?php
namespace Tests\Integration;

use Tests\TestCase;
use Bkwld\Decoy\Models\RedirectRule;

class RedirectTest extends TestCase
{

    /**
     * Test that the redirects page loads
     *
     * @return void
     */
    public function testRedirectListing()
    {
        $this->auth();

        $response = $this->get('admin/redirect-rules');

        $response->assertStatus(200);
    }

    /**
     * Test that redirects create correctly
     *
     * @return void
     */
    public function testRedirectCreate()
    {
        $this->auth();

        $from = 'test';
        $to = '/test-redirected';

        $response = $this->post('admin/redirect-rules/create', [
            'from' => $from,
            'to' => $to,
            'code' => 301,
        ]);

        $redirect = RedirectRule::first();
        $this->assertEquals(1, $redirect->id);
        $this->assertEquals($from, $redirect->from);
        $this->assertEquals($to, $redirect->to);
        $this->assertEquals(301, $redirect->code);
    }

    public function testRedirectUrl()
    {
        $this->auth();

        $redirect_rule = factory(RedirectRule::class)->create();
        $data = RedirectRule::first();

        $this->assertNotEmpty($data);

        $response = $this->get('/test');

        $response->assertStatus(301);
        $response->assertRedirect('/redirected');
    }

}
