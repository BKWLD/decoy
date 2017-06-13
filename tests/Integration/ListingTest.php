<?php
namespace Tests\Integration;

use App\Article;
use App\Tag;
use Tests\TestCase;
use Bkwld\Decoy\Input\Search;
use Illuminate\Http\UploadedFile;

class ListingTest extends TestCase
{

    /**
     * Shared setup
     *
     * @return void
     */
    protected function setup() {
        parent::setUp();
        $this->auth();
    }

    /**
     * Test that the model listing view works
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('admin/articles');
        $response->assertStatus(200);
    }

    /**
     * Test the listing ordering
     * @return void
     */
    public function testOrder()
    {
        $articles = factory(Article::class, 2)->create();

        list($first_article, $second_article) = $articles;

        $response = $this->json('PUT', 'admin/articles/' . $first_article->id, [
            'id' => $first_article->id,
            'position' => 2,
            'public' => true
        ], $this->ajaxHeader());

        $this->assertEquals(2, $first_article->fresh()->position);
    }

    /**
     * Test deleting from the listing view
     * @return void
     */
    public function testListingDestroy()
    {
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
        $article = factory(Article::class)->create([
            'public' => 0
        ]);

        $response = $this->json('PUT', 'admin/articles/' . $article->id, [
            'id' => 1,
            'position' => 1,
            'public' => true
        ], $this->ajaxHeader());

        $this->assertEquals(1, $article->fresh()->public);
    }

    /**
     * Test if making the listing item private is working
     * @return void
     */
    public function testListingPrivate()
    {
        $article = factory(Article::class)->create();

        $response = $this->json('PUT', 'admin/articles/' . $article->id, [
            'id' => 1,
            'position' => 1,
            'public' => false
        ], $this->ajaxHeader());

        $this->assertEquals(0, $article->fresh()->public);
    }

    /**
     * Test that a title field is exactly a match in search
     *
     * @return void
     */
    public function testListingSearch()
    {
        $articles = factory(Article::class, 3)->create();
        $first = Article::first();

        $search = Search::query(["title" => $first->title]);

        $response = $this->get('admin/articles?' . $search);

        // Sanity check that the view has content property
        $response->assertViewHas('content');

        // Check that the one result matches the article that was searched for
        $articles = $response->original->content->getItems();
        $this->assertEquals(1, $articles->count());
        $this->assertEquals($first->title, $articles[0]->title);
    }

    /**
     * Test that pagination is rendering fine
     *
     * @return void
     */
    public function testPagination()
    {
        // Pagination is currently set at 5
        $articles = factory(Article::class, 6)->create();
        $response = $this->get('admin/articles');

        // Check for errors
        $response->assertStatus(200);

        // Check that there are 2 pages of results
        $paginator = $response->original->content->getItems();
        $this->assertEquals(2, $paginator->lastPage());
    }

    /**
     * Test that soft deleted rows can be shown
     *
     * @return void
     */
    public function testSoftDeletes()
    {
        // Make a tag and soft delete it
        $tag = factory(Tag::class)->create();
        $tag->delete();

        // Check for errors
        $response = $this->get('admin/tags')->assertStatus(200);

        // Check that the tag is present in the results
        $paginator = $response->original->content->getItems();
        $this->assertEquals(1, $paginator->count());

        // Checkt that the edit view is allowed
        $this->get('admin/tags/'.$tag->id.'/edit')->assertStatus(200);
    }

}
