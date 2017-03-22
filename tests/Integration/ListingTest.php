<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ListingTest extends TestCase
{

    /**
     * Test that the model listing view works
     * @return void
     */
    public function testIndex()
    {
        $this->auth();
        $response = $this->get('admin/articles');
        $response->assertResponseStatus(200);
    }

    /**
     * Test the listing ordering
     * @return void
     */
    public function testOrder()
    {
        $this->auth();
        $articles = factory(Article::class, 2)->create();

        list($first_article, $second_article) = $articles;

        $this->json('PUT', 'admin/articles/' . $first_article->id, [
            'id' => $first_article->id,
            'position' => 2,
            'public' => true
        ]);

        $this->assertEquals(2, $first_article->fresh()->position);
    }

}
