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
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
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
        $this->post('admin/articles/1/attach', [
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
        ], $this->ajaxHeader());
        $this->assertEquals(1, $this->tag->articles()->count());
        $this->assertEquals($this->article->id, $this->tag->articles()->first()->id);
    }

    /**
     * Test detaching (deleting) of a many to many relationship from the sidebar
     */
    public function testSidebarDetach()
    {
        $this->delete('admin/admin/articles/1/remove', [
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
        ], $this->ajaxHeader());
        $this->assertEquals(0, $this->tag->articles()->count());
    }

    /**
     * Test bulk detaching of a many to many relationship from the sidebar
     */
    public function testSidebarBulkDetach()
    {
        $article = factory(Article::class)->create();
        $this->delete('admin/admin/articles/1/remove', [
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
            'ids' => $this->article->id.','.$article->id,
        ], $this->ajaxHeader());
        $this->assertEquals(0, $this->tag->articles()->count());
    }

}
