<?php
namespace Tests\Integration;

use App\Article;
use App\Tag;
use Tests\TestCase;

class ManyToManyTest extends TestCase
{

    /**
     * @var Article
     */
    protected $article;

    /**
     * @var Tag
     */
    protected $tag;

    /**
     * Seed with a tag and article
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->auth();

        $this->tag = factory(Tag::class)->create();
        $this->article = factory(Article::class)->create([
            'title' => 'Example',
        ]);
    }

    /**
     * Test the autocomplete response
     *
     * @return void
     */
    public function testAutocomplete()
    {
        $params = http_build_query([
            'query' => 'amp',
            'parent_id' => $this->tag->id,
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
        ]);
        $this->json('GET', 'admin/articles/autocomplete?'.$params);

        $this->seeJson([
            'id' => 1,
            'title' => 'Example',
            'columns' => [
                'getAdminTitleHtmlAttribute' => 'Example',
            ]
        ]);
    }

    /**
     * Test attach of a many to many relationship from the sidebar
     *
     * @return void
     */
    public function testSidebarAttach()
    {


    }

    /**
     * Test detaching (deleting) of a many to many relationship from the sidebar
     */
    public function testSidebarDetach()
    {

    }

}
