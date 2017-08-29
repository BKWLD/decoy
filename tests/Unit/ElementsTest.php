<?php
namespace Tests\Unit;

use Bkwld\Decoy\Helpers;
use Tests\TestCase;

class ElementsTest extends TestCase
{
    /**
     * Test `Decoy::hasEl()`
     *
     * @return void
     */
    public function testHasEl()
    {
        $this->assertTrue((new Helpers)->hasEl('homepage.marquee.title'));
        $this->assertFalse((new Helpers)->hasEl('homepage.fake.title'));
    }

    /**
     * Test `Decoy::els()`
     *
     * @return void
     */
    public function testEls()
    {
        // Convert croped images to string that can be tested by phunit easier
        $els = (new Helpers)->els('homepage', [
            'bukwild.logo' => [1600, 800],
        ]);
        $els['bukwild']['logo'] = $els['bukwild']['logo']->url;

        $this->assertEquals([
            'marquee' => [
                'title' => 'Welcome to Decoy',
                'image' => '',
                'file' => '',
            ], 'bukwild' => [
                'logo' => 'http://localhost/uploads/elements/logo-1600x800.jpg?token=8f30b24fbc397cade10cf62b9f03b9a7',
            ],
        ], $els);
    }



}
