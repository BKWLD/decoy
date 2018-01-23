<?php

namespace Bkwld\Decoy\Models;

use DB;
use Config;
use Request;
use Bkwld\Library\Utils;

class RedirectRule extends Base
{
    /**
     * Don't allow cloning because the "from" is unique
     *
     * @var boolean
     */
    public $cloneable = false;

    /**
     * Admins should not be localized
     *
     * @var boolean
     */
    public static $localizable = false;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'from' => 'required|unique:redirect_rules,from',
        'to' => 'required',
    ];

    /**
     * Redirection codes
     *
     * @return array
     */
    public static function getCodes()
    {
        return [
            '301' => __('decoy::redirect_rules.model.301'),
            '302' => __('decoy::redirect_rules.model.302'),
        ];
    }

    /**
     * Generate the admin title
     *
     * @return string
     */
    public function getAdminTitleAttribute()
    {
        // Use the label, if defined
        if ($this->label) {
            return $this->label;
        }

        // Else make from the `from` and `to`
        // http://character-code.com/arrows-html-codes.php
        return $this->from .' &#8594; '.$this->to;
    }

    /**
     * Pre-validation rules
     *
     * @param  Illuminate\Validation\Validator $validation
     * @return null
     */
    public function onValidating($validation)
    {
        // Clean up "from" route, stripping host and leading slash
        $this->from = preg_replace('#^([^/]*//[^/]+)?/?#', '', $this->from);

        // Make an absolute path if the current domain is entered
        $this->to = Utils\URL::urlToAbsolutePath($this->to);

        // Add row exception for unique
        if ($this->exists) {
            $rules = $validation->getRules();
            $rules['from'][1] .= ','.$this->getKey();
            $validation->setRules($rules);
        }
    }

    /**
     * Orders instances of this model in the admin as well as default ordering
     * to be used by public site implementation.
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('from');
    }

    /**
     * See if the current request matches the "FROM" using progressively more
     * expensive ways to match the from column.
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function scopeMatchUsingRequest($query)
    {
        return $query->where(function ($query) {
            $from = $this->pathAndQuery();
            $escaped_from = DB::connection()->getPdo()->quote($from);
            $from_col = DB::getDriverName() == 'sqlsrv' ? '[from]' : '`from`';
            $query->where('from', $from)->orWhereRaw("{$escaped_from} LIKE {$from_col}");
            if (Config::get('decoy::core.allow_regex_in_redirects')) {
                $query->orWhereRaw("{$escaped_from} REGEXP {$from_col}");
            }
        });
    }

    /**
     * Get the path and query from the request
     *
     * @return string
     */
    public function pathAndQuery()
    {
        $query = Request::getQueryString();
        $path = ltrim(Request::path(), '/'); // ltrim fixes homepage
        return $query ? $path.'?'.$query : $path;
    }
}
