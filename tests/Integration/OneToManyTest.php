<?php
namespace Tests\Integration;

use App\Slide;
use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class OneToManyTest extends TestCase
{

    /**
     * Test the creation of a one to many item and assert that it is linked
     * properly
     *
     * @return void
     */
    public function testOneToManyCreate()
    {
        $this->auth();
        $article = factory(Article::class)->create();

        $response = $this->call('POST', 'admin/articles/' . $article->id . '/slides/1/create', [
            'title' => 'Test Slide',
            '_save' => 'save',
        ]);

        $slide = Slide::findOrFail(1);

        $this->assertEquals($article->id, $slide->article_id);
    }

    /**
     * Test that you can edit a related item through the parent
     *
     * @return void
     */
    public function testOneToManyEdit()
    {
        $this->auth();
        $article = factory(Article::class)->create();
        $article->slides()->save(factory(Slide::class)->make());

        $response = $this->get('admin/articles/1/slides/1/edit');
        $response->assertStatus(200);
    }

    /**
     * Test that you can destroy the related item
     *
     * @return void
     */
    public function testOneToManyDestroy()
    {
        $this->auth();
        $article = factory(Article::class)->create();
        $article->slides()->save(factory(Slide::class)->make());

        $this->assertNotEmpty($article->fresh()->slides()->first());

        $response = $this->get('admin/articles/1/slides/1/destroy');

        $this->assertEmpty($article->fresh()->slides()->first());
    }

    /**
     * Test that you can destroy the related item from the parent
     *
     * @return void
     */
    public function testOneToManyDestroyFromListing()
    {
        $this->auth();
        $article = factory(Article::class)->create();
        $article->slides()->save(factory(Slide::class)->make());

        $this->assertNotEmpty($article->fresh()->slides()->first());

        $response = $this->call('DELETE', 'admin/slides/1');

        $this->assertEmpty($article->fresh()->slides()->first());
    }
}
