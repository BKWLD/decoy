<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ArticleTest extends TestCase
{

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

    public function testIndex()
    {
        $this->auth();
        $response = $this->get('admin/articles');
        $response->assertResponseStatus(200);
    }

    public function testCreate()
    {
        $this->auth();
        $response = $this->get('admin/articles/create');
        $response->assertResponseStatus(200);
    }

    public function testStoreFailsValidation()
    {
        $this->auth();
        $response = $this->post('admin/articles/create', ['title' => ''], [
            'HTTP_REFERER' => url('admin/articles/create'),
        ]);
        $response->assertRedirectedTo('admin/articles/create');
        $response->assertSessionHasErrors('title');
    }

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

    public function testDestroy()
    {
        $this->auth();
        $article = factory(Article::class)->create();

        $new_article = Article::findBySlug($article->slug);
        $this->assertNotEmpty($new_article);

        $this->get('admin/articles/1/destroy');

        $old_article = Article::findBySlug($article->slug);
        $this->assertEmpty($old_article);
    }

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
