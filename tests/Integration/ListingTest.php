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

        $response = $this->json('PUT', 'admin/articles/' . $first_article->id, [
            'id' => $first_article->id,
            'position' => 2,
            'public' => true
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertEquals(2, $first_article->fresh()->position);
    }

    /**
     * Test deleting from the listing view
     * @return void
     */
    public function testListingDestroy()
    {
        $this->auth();
        $article = factory(Article::class)->create();

        $response = $this->call('DELETE', 'admin/articles/' . $article->id);

        $this->assertEmpty($article->fresh());
    }

    /**
     * Test if making the listing item public is working
     * @return void
     */
    public function testListingPublic()
    {
        $this->auth();
        $article = factory(Article::class)->create([
            'public' => 0
        ]);

        $response = $this->json('PUT', 'admin/articles/' . $article->id, [
            'id' => 1,
            'position' => 1,
            'public' => true
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertEquals(1, $article->fresh()->public);
    }

    /**
     * Test if making the listing item private is working
     * @return void
     */
    public function testListingPrivate()
    {
        $this->auth();
        $article = factory(Article::class)->create();

        $response = $this->json('PUT', 'admin/articles/' . $article->id, [
            'id' => 1,
            'position' => 1,
            'public' => false
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertEquals(0, $article->fresh()->public);
    }

}
