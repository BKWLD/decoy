<?php
namespace Tests\Integration;

use Bkwld\Decoy\Models\Element;
use Decoy;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ElementsTest extends TestCase
{

    /**
     * Common init
     *
     * @return void
     */
    protected function setUp() {
        parent::setUp();
        $this->auth();

        // Disable localization for these tests
        config()->set('decoy.site.locales', [
            'en' => 'English',
        ]);
    }

    /**
     * Test that the elements page loads
     *
     * @return void
     */
    public function testElementsListing()
    {
        $response = $this->get('admin/elements');

        $response->assertStatus(200);
    }

    /**
     * Test that elements read from the default yaml file
     *
     * @return void
     */
    public function testElementsShowDefault()
    {
        $default = 'Welcome to Decoy';
        $element = Decoy::el('homepage.marquee.title');

        $this->assertEquals($default, $element);
    }

    /**
     * Test that the text field saves properly
     *
     * @return void
     */
    public function testTextFieldSave()
    {
        $response = $this->post('admin/elements', [
            'homepage|marquee|title' => 'Test',
            'images' => [
                '_xxxx' => [
                    'name' => 'homepage|marquee|image',
                ],
            ],
        ]);

        $element = Decoy::el('homepage.marquee.title');
        $response->assertStatus(302);
        $this->assertEquals('Test', $element);
    }

    /**
     * Test that the text field will populate from database without saving
     *
     * @return void
     */
    public function testTextFieldReadsValueFromDatabase()
    {
        $create_element = factory(Element::class)->create();
        $database_value = Element::first()->value();

        $element = Decoy::el('homepage.marquee.title')->value();

        $this->assertEquals($database_value, $element);
    }

    /**
     * Test that the text field only saves to the database if a value
     * other than the default is used
     *
     * @return void
     */
    public function testTextFieldDoesntSaveUnchanged()
    {
        // Make sure first there are no elements in the database
        $first_check = Element::first();
        $this->assertEmpty($first_check);

        // Check to verify the default element is being pulled
        $default_text = 'Welcome to Decoy';
        $default_element = Decoy::el('homepage.marquee.title');
        $this->assertEquals($default_text, $default_element);

        // Make a post request without changing the title
        $response = $this->post('admin/elements', [
            'images' => [
                '_xxxx' => [
                    'name' => 'homepage|marquee|image',
                ],
            ],
        ]);
        $response->assertStatus(302);
        $this->assertEmpty(Element::first());
        $this->assertEquals($default_text, $default_element);
    }

    /**
     * Test that the image uploads to element fields
     *
     * @return void
     */
    public function testImageFieldUpload()
    {
        $response = $this->call('POST', 'admin/elements', [
            'images' => [
                '_xxxx' => [
                    'name' => 'homepage|marquee|image',
                    'file' => $this->createUploadedFile(),
                ],
            ],
        ]);

        $element = Decoy::el('homepage.marquee.image');
        $response->assertStatus(302);
        $this->assertNotEmpty($element->crop(10, 10)->url);
    }

    /**
     * Test that the file uploads to element fields
     *
     * @return void
     */
    public function testFileFieldUpload()
    {
        $response = $this->call('POST', 'admin/elements', [
            'images' => [
                '_xxxx' => [
                    'name' => 'homepage|marquee|image',
                ],
            ],
        ], [], [
            'homepage|marquee|file' => $this->createUploadedFile('file.jpg')
        ]);

        $element = Decoy::el('homepage.marquee.file');
        $response->assertStatus(302);
        $this->assertNotEmpty($element->value());
    }

    /**
     * Test that the image is deleted from elements and db
     *
     * @return void
     */
    public function testImageFieldDelete()
    {

        $this->createUploadedFile('test.jpg');

        $element = factory(Element::class)->create([
            'key' => 'homepage.marquee.image',
            'type' => 'image',
            'value' => '/uploads/test.jpg',
            'locale' => 'en',
        ]);
        $image = $element->images()->create([
            'file' => '/uploads/test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 10,
            'width' => 20,
            'height' => 20,
        ]);

        $response = $this->call('POST', 'admin/elements', [
            'images' => [
                $image->id => [
                    'name' => 'homepage|marquee|image',
                    'file' => '',
                ],
            ],
        ], [], [
            'images' => [
                $image->id => [
                    'file' => '',
                ],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertEmpty($element->fresh()->value);
        $this->assertEmpty($image->fresh());
    }

    /**
     * Test that the file is deleted from elements and db
     *
     * @return void
     */
    public function testFileFieldDelete()
    {
        $this->createVirtualFile('test.jpg');

        $element = factory(Element::class)->create([
            'key' => 'homepage.marquee.file',
            'type' => 'file',
            'value' => '/uploads/file.jpg',
            'locale' => 'en',
        ]);

        $response = $this->call('POST', 'admin/elements', [
            'homepage|marquee|file' => '',
            'images' => [
                '_xxxx' => [
                    'name' => 'homepage|marquee|image',
                ],
            ],
        ]);

        $response->assertStatus(302);

        $path = app('upchuck')->path($element->value);
        $this->assertEmpty($element->fresh()->value);
        $this->assertFalse($this->disk->has($path));
    }

    /**
     * Test that element images are moved to writeable storage for cropping
     *
     * @return void
     */
    public function testDefaultImageMovedToDisk()
    {
        $this->get('admin/elements');

        // Check that image was created in elements table
        $this->assertDatabaseHas('elements', [
            'key' => 'homepage.bukwild.logo',
            'type' => 'image',
            'value' => '/uploads/elements/logo.jpg',
        ]);

        // Check that image was also created in images table
        $this->assertDatabaseHas('images', [
            'imageable_type' => 'Bkwld\Decoy\Models\Element',
            'imageable_id' => 'homepage.bukwild.logo',
            'file' => '/uploads/elements/logo.jpg',
        ]);

        // Check that image exists on disk
        $path = app('upchuck')->path('/uploads/elements/logo.jpg');
        $this->assertTrue($this->disk->has($path));
    }

}
