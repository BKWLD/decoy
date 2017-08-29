<?php
namespace Tests\Unit;

use Bkwld\Decoy\Helpers;
use Tests\TestCase;

class ElementsTest extends TestCase
{

    /**
     * Test `Decoy::hasEl()`
     *
     * @return [type] [description]
     */
    public function testHasEl()
    {
        $this->assertTrue((new Helpers)->hasEl('homepage.marquee.title'));
        $this->assertFalse((new Helpers)->hasEl('homepage.fake.title'));
    }

}
