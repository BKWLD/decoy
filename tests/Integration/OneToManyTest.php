<?php
namespace Tests\Integration;

use App\Slide;
use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class OneToManyTest extends TestCase
{

    /**
     * Test the creation of a one to many model and assert that it is linked
     * properly
     *
     * @return void
     */
    public function testOneToManyCreate()
    {
        $this->auth();
        $article = factory(Article::class)->create();

        $request = $this->call('POST', 'admin/articles/' . $article->id . '/slides/1/create', [
            'title' => 'Test Slide',
            '_save' => 'save',
        ]);

        $slide = Slide::findOrFail(1);

        $this->assertEquals($article->id, $slide->article_id);
    }
}
