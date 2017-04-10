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
    protected function setUp() {
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
        $response = $this->json('GET', 'admin/articles/autocomplete?'.$params);

        $response->assertJson([
            [
                'id' => 1,
                'title' => 'Example',
                'columns' => [
                    'getAdminTitleHtmlAttribute' => 'Example',
                    'getAdminFeaturedAttribute' => '',
                    'created_at' => date('m/d/y'),
                ]
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
     *
     * @return void
     */
    public function testSidebarDetach()
    {
        $this->tag->articles()->attach($this->article->id);
        $this->assertEquals(1, $this->article->tags()->count());

        $this->delete('admin/admin/articles/1/remove', [
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
        ], $this->ajaxHeader());
        $this->assertEquals(0, $this->tag->articles()->count());
    }

    /**
     * Test bulk detaching of a many to many relationship from the sidebar
     *
     * @return void
     */
    public function testSidebarBulkDetach()
    {
        $article = factory(Article::class)->create();
        $this->tag->articles()->attach([$article->id, $this->article->id]);
        $this->assertEquals(2, $this->tag->articles()->count());

        $this->delete('admin/admin/articles/1/remove', [
            'parent_controller' => 'App\Http\Controllers\Admin\Tags',
            'parent_id' => $this->tag->id,
            'ids' => $this->article->id.','.$article->id,
        ], $this->ajaxHeader());

        $this->assertEquals(0, $this->tag->articles()->count());
    }

    /**
     * Test adding relations via the many to many checklist field
     *
     * @return void
     */
    public function testManyToManyChecklistAttach()
    {
        $this->post('admin/articles/'.$this->article->id.'/edit', [
            '_many_to_many_tags' => [ $this->tag->id ],
        ]);

        $this->assertEquals(1, $this->article->tags()->count());
        $this->assertEquals($this->tag->id, $this->article->tags()->first()->id);
    }

    /**
     * Test removing relations via the many to many checklist field
     *
     * @return void
     */
    public function testManyToManyChecklistDetach()
    {
        $this->article->tags()->attach($this->tag->id);
        $this->assertEquals(1, $this->article->tags()->count());

        $this->post('admin/articles/'.$this->article->id.'/edit', [
            '_many_to_many_tags' => [ ],
        ]);

        $this->assertEquals(0, $this->article->tags()->count());
    }


}
