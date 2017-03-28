<?php
namespace Tests\Integration;

use App\Article;
use App\Recipe;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class FileTest extends TestCase
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
     * Create data used for image upload test
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
     * Create data used for file upload test
     *
     * @return array
     */
    public function createRecipeData()
    {
        $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $file_name = 'file.txt';
        $file_path = $tmp_dir.'/'.$file_name;
        if (!file_exists($file_path)) {
            file_put_contents($file_path, 'test');
        }

        return [

            // Params
            [
                'title' => 'Test Recipe',
                'locale' => 'en',
                'public' => 1,
            ],

            // Files
            [
                'file' => new UploadedFile(
                    $file_path,
                    $file_name,
                    null,
                    null,
                    null,
                    true
                )
            ],
        ];
    }

    /**
     * Test the image file field uploads and is stored when saved
     *
     * @return void
     */
    public function testImageUploadStore()
    {
        list($params, $files) = $this->createData();

        $response = $this->call('POST', 'admin/articles/create', array_merge($params, [
            '_save' => 'save',
        ]), [], $files);

        $article = Article::findBySlug('example-title');
        $this->assertNotEmpty($article->img()->url);
    }

    /**
     * Test that the upload file field gets stored when saved
     */
    public function testFileUploadStore()
    {
        list($params, $files) = $this->createRecipeData();

        $response = $this->call('POST', 'admin/recipes/create', array_merge($params, [
            '_save' => 'save',
        ]), [], $files);

        $recipe = Recipe::first();
        $this->assertEquals('Test Recipe', $recipe->title);
        $path = app('upchuck')->path($recipe->file);
        $this->assertTrue($this->disk->has($path));
    }

    /**
     * Test that an image gets removed when the article is deleted
     *
     * @return void
     */
    public function testImageRemovedOnDelete()
    {
        // Make image
        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($img);
        $this->disk->put('test.jpg', ob_get_clean());
        imagedestroy($img);

        // Create recipe with file attachments
        $article = factory(Article::class)->create();
        $article->images()->create([
            'file' => '/uploads/test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 10,
            'width' => 20,
            'height' => 20,
        ]);

        // Test first that the image is actually there
        $path = app('upchuck')->path($article->img()->file);
        $this->assertNotEmpty($article);
        $this->assertTrue($this->disk->has($path));

        $response = $this->get('admin/articles/'.$article->id.'/destroy');

        // Test that article and the image are both removed
        $this->assertEmpty($article->fresh());
        $this->assertEmpty($this->disk->has($path));
    }

    /**
     * Test that a file gets removed when the parent is deleted
     *
     * @return void
     */
    public function testFileRemovedOnDelete()
    {
        $this->disk->put('test.txt', 'test');

        $recipe = factory(Recipe::class)->create([
            'file' => '/uploads/test.txt'
        ]);

        $response = $this->get('admin/recipes/'.$recipe->id.'/destroy');

        $path = app('upchuck')->path($recipe->file);
        $this->assertEmpty($recipe->fresh());
        $this->assertFalse($this->disk->has($path));
    }

    /**
     * Test that a file is deleted if checkbox is checked on save
     *
     * @return void
     */
    public function testFileRemovedOnSave()
    {
        $this->disk->put('test.txt', 'test');

        $recipe = factory(Recipe::class)->create([
            'file' => '/uploads/test.txt'
        ]);

        $this->assertNotEmpty($recipe->file);

        $response = $this->post('admin/recipes/'.$recipe->id.'/edit', [
            'file' => '',
            '_save' => 'save',
        ]);

        $path = app('upchuck')->path($recipe->file);
        $this->assertNotEmpty($recipe->fresh());
        $this->assertEmpty($recipe->fresh()->file);
        $this->assertFalse($this->disk->has($path));
    }

}
