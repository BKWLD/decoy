<?php
namespace Tests\Integration;

use Decoy;
use Tests\TestCase;
use Bkwld\Decoy\Models\Element;

class ElementsLocalizationTest extends TestCase
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
     * Test that the elements page loads
     *
     * @return void
     */
    public function testDefaultLocaleListing()
    {
        $response = $this->get('admin/elements');
        $response->assertStatus(200);
    }

    /**
     * Test that the elements page loads when a locale is specified
     *
     * @return void
     */
    public function testExplicitLocaleListing()
    {
        $response = $this->get('admin/elements/en');
        $response->assertStatus(200);
    }

    /**
     * Test that saved value is quarantined to locale it was saved as
     *
     * @return void
     */
    public function testSavedValueQuarantined()
    {
        $response = $this->post('admin/elements/es', [
            'homepage|marquee|title' => 'Spanish'
        ]);

        // Clear the cache
        app('decoy.elements')->reset();

        // Test that in english, we don't see the sapnish title
        $element = (string) Decoy::el('homepage.marquee.title');
        $this->assertEquals('Welcome to Decoy', $element);
    }

    /**
     * Test that saved value can be read when locale is ste
     *
     * @return void
     */
    public function testSavedValue()
    {
        $response = $this->post('admin/elements/es', [
            'homepage|marquee|title' => 'Spanish'
        ]);

        // Clear the cache
        app('decoy.elements')->reset();

        // Set to spanish adn confirm the locale scene
        Decoy::locale('es');
        $element = (string) Decoy::el('homepage.marquee.title');
        $this->assertEquals('Spanish', $element);
    }

}
