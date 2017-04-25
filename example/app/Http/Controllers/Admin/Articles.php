<?php namespace App\Http\Controllers\Admin;

use Bkwld\Decoy\Controllers\Base;

class Articles extends Base
{

    protected $title = 'News & Events';
    protected $description = 'News and events yo!';
    protected $columns = [
        'Title' => 'getAdminTitleHtmlAttribute',
        'Status' => 'getAdminFeaturedAttribute',
        'Date' => 'created_at',
    ];
    protected $search = [
        'title',
        'featured' => [
            'type' => 'select',
            'label' => 'featured status',
            'options' => [
                1 => 'featured',
                0 => 'not featured',
            ]
        ],
        'category' => [
            'type' => 'select',
            'options' => 'App\Article::$categories',
        ],
        'date' => 'date',
    ];
    public static $per_page = 5;
}
