<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class CrudTest extends TestCase
{

    /**
     * Create data used for crud tests
     *
     * @return array
     */
    private function createData()
    {

        // Create an image in the tmp directory where Upchuck is expecting it
        $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $img_name = 'decoy-article-image.png';
        $img_path = $tmp_dir.'/'.$img_name;
        if (!file_exists($img_path)) {
            $img = imagecreatetruecolor(20, 20);
            imagepng($img, $img_path);
            imagedestroy($img);
        }

        return [

            // Params
            [
                'title' => 'Example Title',
                'body' => 'Body',
                'category' => 'first',
                'date' => '2020-01-01',
                'featured' => 1,
                'public' => 1,
                'images' => [
                    '_xxxx' => [
                        'name' => '',
                    ],
                ],
            ],

            // Files
            [
                'images' => [
                    '_xxxx' => [
                        'file' => new UploadedFile(
                            $img_path,
                            $img_name,
                            'image/png',
                            null,
                            null,
                            true
                        )
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the model create route is working
     *
     * @return void
     */
    public function testCreate()
    {
        $this->auth();
        $response = $this->get('admin/articles/create');
        $response->assertResponseStatus(200);
    }

    /**
     * Test if the validator catches missing data
     *
     * @return void
     */
    public function testStoreFailsValidation()
    {
        $this->auth();
        $response = $this->post('admin/articles/create', ['title' => ''], [
            'HTTP_REFERER' => url('admin/articles/create'),
        ]);
        $response->assertRedirectedTo('admin/articles/create');
        $response->assertSessionHasErrors('title');
    }

    /**
     * Test the model store
     *
     * @return void
     */
    public function testStore()
    {
        $this->auth();
        list($params, $files) = $this->createData();

        $response = $this->call('POST', 'admin/articles/create', array_merge($params, [
            '_save' => 'save',
        ]), [], $files);

        $this->assertRedirectedTo('admin/articles/1/edit');

        $article = Article::findBySlug('example-title');
        $this->assertNotEmpty($article);
        $this->assertEquals(1, $article->position);
        $this->assertNotEmpty($article->img()->url);
    }

    /**
     * Test that the edit view updates properly
     *
     * @return void
     */
    public function testUpdate()
    {
        $this->auth();
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
        $this->auth();
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
        $this->auth();
        $article = factory(Article::class)->create();

        $new_article = Article::findBySlug($article->slug);
        $this->assertNotEmpty($new_article);

        $this->get('admin/articles/' . $new_article->id . '/duplicate');

        $duplicate_article = Article::findBySlug($new_article->slug . '-1');

        $this->assertNotEmpty($duplicate_article);
        $this->assertEquals($duplicate_article->slug, $new_article->slug . '-1');
    }

}
