<?php
namespace Tests\Integration;

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
    public function setUp() {
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

        // Create an image in the tmp directory where Upchuck is expecting it
        $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $img_name = 'decoy-article-image.png';
        $img_path = $tmp_dir.'/'.$img_name;
        if (!file_exists($img_path)) {
            $img = imagecreatetruecolor(20, 20);
            imagepng($img, $img_path);
            imagedestroy($img);
        }

        // Make the file record
        $file = new UploadedFile(
            $img_path,
            $img_name,
            'image/png',
            null,
            null,
            true
        );

        return [

            // Params
            [
                'title' => 'Tasty food',
                'directions' => '<p>Do it</p>',
                'public' => 1,
                'locale' => 'en',
                'images' => [
                    '_xxxx' => [
                        'name' => '',
                    ],
                ],
            ],

            // Files
            [
                // 'file' => $file,
                'images' => [
                    '_xxxx' => [
                        'file' => $file,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test that the localization views don't break anything
     *
     * @return void
     */
    public function testCreate()
    {
        $response = $this->get('admin/recipes/create');
        $response->assertResponseStatus(200);
    }

    /**
     * Test that localization columns get set
     *
     * @return void
     */
    public function testStore()
    {
        list($params, $files) = $this->createData();

        $response = $this->call('POST', 'admin/recipes/create', array_merge($params, [
            '_save' => 'save',
        ]), [], $files);

        $this->assertRedirectedTo('admin/recipes/1/edit');

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
        $response->assertResponseStatus(200);
    }

    // /**
    //  * Test that the edit view updates properly
    //  *
    //  * @return void
    //  */
    // public function testUpdate()
    // {
    //     $this->auth();
    //     $recipe = factory(Recipe::class)->create();
    //
    //     $response = $this->call('POST', 'admin/recipes/' . $recipe->id . '/edit', [
    //         'title' => 'new article title',
    //     ]);
    //
    //     $this->assertEquals('new article title', $recipe->fresh()->title);
    // }

}
