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
    private function createData($filename = null)
    {
        return [

            'title' => 'Example Title',
            'body' => 'Body',
            'category' => 'first',
            'date' => '2020-01-01',
            'featured' => 1,
            'public' => 1,
            'images' => [
                '_xxxx' => [
                    'name' => '',
                    'file' => $this->createUploadedFile($filename),
                ],
            ],
            '_save' => 'save',
        ];
    }

    /**
     * Create data used for file upload test
     *
     * @return array
     */
    public function createRecipeData()
    {

        return [
            'title' => 'Test Recipe',
            'locale' => 'en',
            'public' => 1,
            'file' => $this->createUploadedFile('file.jpg'),
            '_save' => 'save',
        ];
    }

    /**
     * Create data used for image upload test
     *
     * @return Image
     */
    public function createImageOn($article)
    {
        $this->createVirtualFile('test.jpg');
        return $article->images()->create([
            'file' => '/uploads/test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 10,
            'width' => 20,
            'height' => 20,
        ]);
    }

    /**
     * Test the image file field uploads and is stored when saved
     *
     * @return void
     */
    public function testImageUploadStore()
    {
        $data = $this->createData();

        $response = $this->call('POST', 'admin/articles/create', $data);

        $response->assertStatus(302);
        $article = Article::findBySlug('example-title');
        $this->assertNotEmpty($article->img()->url);
    }

    /**
     * Tese file upload with all caps filename
     *
     * @return void
     */
    public function testAllCapsImageUploadStore()
    {
        $data = $this->createData('UPPERCASE.JPEG');

        $response = $this->call('POST', 'admin/articles/create', $data);

        $response->assertStatus(302);
        $article = Article::findBySlug('example-title');
        $this->assertNotEmpty($article->img()->url);
    }

    /**
     * Test that the upload file field gets stored when saved
     */
    public function testFileUploadStore()
    {
        $data = $this->createRecipeData();

        $response = $this->call('POST', 'admin/recipes/create', $data);

        $recipe = Recipe::first();
        $this->assertEquals('Test Recipe', $recipe->title);
        $path = app('upchuck')->path($recipe->file);
        $this->assertTrue($this->disk->has($path));
    }

    /**
     * Tese that an image is not destroyed when a model is updated
     *
     * @return void
     */
    public function testImageKeptOnUpdate()
    {
        // Create recipe with file attachments
        $article = factory(Article::class)->create();
        $image = $this->createImageOn($article);

        // Submit a save
        $response = $this->post('admin/articles/'.$article->id.'/edit', [
            'title' => 'Ok?',
            'images' => [
                $image->id => [
                    'name' => 'image'
                ]
            ],
        ]);

        $response->assertSessionMissing('errors');
        $path = app('upchuck')->path($image->file);
        $this->assertEquals(1, $article->images()->count());
        $this->assertTrue($this->disk->has($path));
    }

    /**
     * Test that an image gets removed when the article is deleted
     *
     * @return void
     */
    public function testImageRemovedOnDelete()
    {
        // Create recipe with file attachments
        $article = factory(Article::class)->create();
        $this->createImageOn($article);

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
        $this->createVirtualFile('test.jpg');

        $recipe = factory(Recipe::class)->create([
            'file' => '/uploads/test.jpg'
        ]);

        $response = $this->get('admin/recipes/'.$recipe->id.'/destroy');

        $path = app('upchuck')->path($recipe->file);
        $this->assertEmpty($recipe->fresh());
        $this->assertFalse($this->disk->has($path));
    }

    /**
     * Test that an image gets removed when the article is deleted
     *
     * @return void
     */
    public function testImageCanBeDeletedOnSave()
    {
        // Create recipe with file attachments
        $article = factory(Article::class)->create();
        $image = $this->createImageOn($article);

        // Submit a save
        $response = $this->post('admin/articles/'.$article->id.'/edit', [
            'images' => [
                $image->id => [
                    'name' => '',
                    'file' => '',
                ]
            ],
            '_save' => 'save',
        ]);

        $response->assertSessionMissing('errors');

        // Test that the image has been removed
        $path = app('upchuck')->path($image->file);
        $this->assertEquals(0, $article->images()->count());
        $this->assertFalse($this->disk->has($path));
    }

    /**
     * Test that a file is deleted if checkbox is checked on save
     *
     * @return void
     */
    public function testFileCanBeDeletedOnSave()
    {
        $this->createVirtualFile('test.jpg');

        $recipe = factory(Recipe::class)->create([
            'file' => '/uploads/test.jpg'
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
