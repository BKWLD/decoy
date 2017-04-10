<?php
namespace Tests\Integration;

use App\Article;
use App\Recipe;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class LocalizationTest extends TestCase
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
     *
     * @return void
     */
    public function testCreate()
    {
        $response = $this->get('admin/recipes/create');
        $response->assertStatus(200);
    }

    /**
     * Test hidden on a non-localized model
     *
     * @return void
     */
    public function testHiddenOnCreate()
    {
        $response = $this->get('admin/articles/create');
        $response->assertStatus(200);
        $this->assertTrue($response->original->content->localize->hidden());
    }

    /**
     * Test auto localize root models
     *
     * @return void
     */
    public function testAutoLocalizeRootModelsConfig()
    {
        config()->set('decoy.site.auto_localize_root_models', true);
        $response = $this->get('admin/articles/create');
        $response->assertStatus(200);
        $this->assertFalse($response->original->content->localize->hidden());
    }

    /**
     * Test that localization columns get set
     *
     * @return void
     */
    public function testStore()
    {
        $response = $this->call('POST', 'admin/recipes/create', [
            'title' => 'Tasty food',
            'directions' => '<p>Do it</p>',
            'public' => 1,
            'locale' => 'en',
            '_save' => 'save',
        ]);

        $response->assertRedirect('admin/recipes/1/edit');

        $this->assertEquals('en', Recipe::first()->locale);
        $this->assertNotEmpty(Recipe::first()->locale_group);

    }

    /**
     * Test that the edit view doesnt error
     *
     * @return void
     */
    public function testEdit()
    {
        $recipe = factory(Recipe::class)->create();

        $response = $this->get('admin/recipes/'.$recipe->id.'/edit');
        $response->assertStatus(200);
        $this->assertFalse($response->original->content->localize->hidden());
    }

    /**
     * Test hidden on a non-localized model
     *
     * @return void
     */
    public function testHiddenOnEdit()
    {
        $article = factory(Article::class)->create();

        $response = $this->get('admin/articles/'.$article->id.'/edit');
        $response->assertStatus(200);
        $this->assertTrue($response->original->content->localize->hidden());
    }

    /**
     * Test that clone is created properly during localization
     *
     * @return void
     */
    public function testDuplicate()
    {
        // Make image
        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($img);
        $this->disk->put('test.jpg', ob_get_clean());
        imagedestroy($img);

        // Make an example file
        $this->disk->put('test.txt', 'test');

        // Create recipe with file attachments
        $recipe = factory(Recipe::class)->create([
            'title' => 'Title',
            'directions' => 'Directions',
            'file' => '/uploads/test.txt',
            'public' => 0,
        ]);
        $recipe->images()->create([
            'file' => '/uploads/test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 10,
            'width' => 20,
            'height' => 20,
        ]);

        // The localization call
        $response = $this->call('POST', 'admin/recipes/'.$recipe->id.'/duplicate', [
            'locale' => 'es',
        ]);

        // Test that simple fields were copied
        $this->assertDatabaseHas('recipes', [
            'title' => 'Title copy',
            'directions' => 'Directions',
            'locale' => 'es',
        ]);

        // Test that the file was duplicated to a new location
        $dupe = Recipe::where('title', 'Title copy')->first();
        $this->assertNotEquals($recipe->file, $dupe->file);

        // Test that the duplicated file acutally exists and has the same value
        $path = app('upchuck')->path($dupe->file);
        $this->assertTrue($this->disk->has($path));
        $this->assertEquals('test', $this->disk->read($path));

        // Test that the image was duplicated to a new location
        $this->assertNotEquals($recipe->img()->url, $dupe->img()->url);

        // Test that the image exists
        $path = app('upchuck')->path($dupe->img()->file);
        $this->assertTrue($this->disk->has($path));
    }

    /**
     * Test that the edit view of clone doesn't error
     *
     * @return void
     */
    public function testEditOfDuplicate()
    {
        $recipe = factory(Recipe::class)->create();
        $dupe = $recipe->duplicate();
        $dupe->locale = 'es';
        $dupe->save();

        $response = $this->get('admin/recipes/'.$dupe->id.'/edit');
        $response->assertStatus(200);
    }

}
