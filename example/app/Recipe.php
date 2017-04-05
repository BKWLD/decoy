<?php

namespace App;

use Bkwld\Decoy\Models\Base;
use Bkwld\Decoy\Models\Traits\HasImages;

class Recipe extends Base
{
    use HasImages;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required',
        'images.default' => 'image',
        'file' => 'file',
    ];

    /**
     * Uploadable attributes
     *
     * @var array
     */
    protected $upload_attributes = ['file'];

    /**
     * Localize it
     *
     * @var boolean
     */
    static public $localizable = true;
}
