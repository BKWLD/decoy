<?php
namespace Tests\Integration;

use App\Article;
use App\Slide;
use League\Csv\Reader;
use Tests\TestCase;

class CsvTest extends TestCase
{
    /**
     * The single article instance in the export
     *
     * @var Article
     */
    protected $article;

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
    public function testExportWithScopedRelations()
    {
        // Generate test data
        $article = factory(Article::class)->create();
        $article->slides()->save(factory(Slide::class)->make());

        // Call export method
        $response = $this->get('admin/articles/csv')->assertStatus(200);

        // Build a csv instance for inspecting the output
        $csv = Reader::createFromString($response->getContent());
        $csv->setHeaderOffset(0);
        $headers = $csv->getHeader();
        $row = $csv->fetchOne(0);

        // Check that the header contains expected fields
        $this->assertEquals('id', $headers[0]);
        $this->assertEquals('Category', $headers[1]);
        $this->assertContains('Slides', $headers);

        // Check that scalar values were set
        $this->assertEquals($article->id, $row['id']);

        // Check that random relation becomes id
        $this->assertEquals($article->slides->first()->id, $row['Slides']);
    }

    /**
     * Test that dev must opt in
     *
     * @return void
     */
    public function test404ByDefault()
    {
        $response = $this->get('admin/tags/csv')->assertStatus(404);
    }
}
