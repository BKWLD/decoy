<?php
namespace Tests\Integration;

use App\Article;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class FileTest extends TestCase
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
     * Test the image file field uploads and is stored when saved
     *
     * @return void
     */
    public function testImageStore()
    {
        $this->auth();
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
    public function testUploadStore()
    {
        
    }

}
