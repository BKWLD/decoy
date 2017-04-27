<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class CrudTest extends TestCase
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
     * Create data used for crud tests
     *
     * @return array
     */
    private function createData()
    {
        return [
            'title' => 'Example Title',
            'body' => 'Body',
            'category' => 'first',
            'date' => '2020-01-01',
            'featured' => 1,
            'public' => 1,
            'topic' => [ 'cars', 'trucks' ],
        ];
    }

    /**
     * Test the model create route is working
     *
     * @return void
     */
    public function testCreate()
    {
        $response = $this->get('admin/articles/create');
        $response->assertStatus(200);
    }

    /**
     * Test if the validator catches missing data
     *
     * @return void
     */
    public function testStoreFailsValidation()
    {
        $response = $this->post('admin/articles/create', ['title' => ''], [
            'HTTP_REFERER' => url('admin/articles/create'),
        ]);
        $response->assertRedirect('admin/articles/create');
        $response->assertSessionHasErrors('title');
    }

    /**
     * Test the model store
     *
     * @return void
     */
    public function testStore()
    {
        $response = $this->call('POST', 'admin/articles/create', array_merge($this->createData(), [
            '_save' => 'save',
        ]));

        $response->assertRedirect('admin/articles/1/edit');

        $article = Article::findBySlug('example-title');
        $this->assertNotEmpty($article);
        $this->assertEquals(1, $article->position);
    }

    /**
     * Test that the edit view doesn't error
     *
     * @return void
     */
    public function testEdit()
    {
        $article = factory(Article::class)->create();

        $response = $this->get('admin/articles/'.$article->id.'/edit');
        $response->assertStatus(200);
    }

    /**
     * Test that the edit view updates properly
     *
     * @return void
     */
    public function testUpdate()
    {
        $article = factory(Article::class)->create();

        $response = $this->call('POST', 'admin/articles/' . $article->id . '/edit', [
            'title' => 'new article title',
        ]);

        $this->assertEquals('new article title', $article->fresh()->title);
    }

    /**
     * Test the model destroy
     *
     * @return void
     */
    public function testDestroy()
    {
        $article = factory(Article::class)->create();

        $this->get('admin/articles/' . $article->id . '/destroy');

        $this->assertEmpty($article->fresh());
    }

    /**
     * Test the model duplicate
     *
     * @return void
     */
    public function testDuplicate()
    {
        $article = factory(Article::class)->create();

        $new_article = Article::findBySlug($article->slug);
        $this->assertNotEmpty($new_article);

        $this->get('admin/articles/' . $new_article->id . '/duplicate');

        $duplicate_article = Article::findBySlug($new_article->slug . '-1');

        $this->assertNotEmpty($duplicate_article);
        $this->assertEquals($duplicate_article->slug, $new_article->slug . '-1');
    }

}
