<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;

class CsvTest extends TestCase
{

    /**
     * Common init
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->auth();
    }

    /**
     * Test that a CSV is generated
     *
     * @return void
     */
    public function testCsvExports()
    {

    }
}
