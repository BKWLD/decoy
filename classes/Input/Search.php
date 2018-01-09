<?php

namespace Bkwld\Decoy\Input;

use DB;
use Carbon\Carbon;
use Config;
use Request;
use Bkwld\Library\Utils\Text;
use Bkwld\Decoy\Exceptions\Exception;

/**
 * This class contains logic related to searching from controller
 * index views.  It could have gone in the controller class, but
 * trying to reduce it's bulk
 */
class Search
{
    /**
     * Utility method to generate a query string that applies the condition
     * provided in the args
     *
     * @param  array $terms An associative array where the keys are "fields"
     *         and the values are "inputs"
     * @return string
     */
    public static function query($terms)
    {
        return 'query='.urlencode(json_encode(array_map(function ($input, $field) {
            return [$field, '=', $input];
        }, $terms, array_keys($terms))));
    }

    /**
     * Apply the effect of a search (which is communicated view request('query'))
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @param  array $config Search config from the controller class definition
     * @return Illuminate\Database\Query\Builder
     */
    public function apply($query, $config)
    {
        // Do nothing if no query in the input
        if (!Request::has('query')) {
            return $query;
        }

        // Expand the config
        $config = $this->longhand($config);

        // Deserialize the query
        $conditions = json_decode(request('query'));
        if (!is_array($conditions)) {
            throw new Exception('Bad query');
        }

        // ... and loop though it
        foreach ($conditions as $condition) {

            // Get the field name by taking the index and looking up which key
            // it corresponds to
            $field = $condition[0];
            $field_config = $config[$field];

            // Extract vars for query
            $comparison = $condition[1];
            $input = $condition[2];

            // Use an app-defined query ...
            if (isset($config[$field]['query'])) {
                call_user_func($config[$field]['query'], $query, $comparison, $input);

            // ... or one of the simple, standard ones
            } else {
                $this->condition($query, $field, $comparison, $input, $config[$field]['type']);
            }
        }

        // Return the agumented query
        return $query;
    }

    /**
     * Add a condition to a query
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @param  string                            $field      The field name from search config
     * @param  string                            $comparison The operator string from the search UI
     * @param  string                            $input      The input for the field
     * @param  string                            $type       The type of the field
     * @return Illuminate\Database\Query\Builder
     */
    private function condition($query, $field, $comparison, $input, $type)
    {
        // Convert date formats
        if ($type == 'date') {
            $field = $this->convertDateField($field);
            $input = Carbon::createFromFormat(__('decoy::form.date.format'), $input)
                ->format('Y-m-d');
        }

        // Apply the where
        switch ($comparison) {

            // NULL safe equals and not equals
			case '=':
			case '!=':
				return $this->applyEquality($comparison, $query, $field, $input);

            // Not Like
            case '!%*%':
                $comparison = substr($comparison, 1);
                $input = str_replace('*', $input, $comparison);
                return $query->where($field, 'NOT LIKE', $input);

            // Like
            case '*%':
            case '%*':
            case '%*%':
                $input = str_replace('*', $input, $comparison);
                return $query->where($field, 'LIKE', $input);

            // Defaults
            default:
                return $query->where($field, $comparison, $input);
        }
    }

    /**
     * Convert a datetime to a date (no time) value
     * https://stackoverflow.com/a/113055/59160
     *
     * @param  string $field
     * @return string
     */
    protected function convertDateField($field)
    {
    	switch(DB::getDriverName())
		{
            case 'sqlsrv': return DB::raw("CONVERT(date, [{$field}])");
            default: return DB::raw("DATE(`{$field}`)");
        }
    }

    /**
     * Make the NULL-safe equals query
     *
     * @param  string $comparison
     * @param  Builder $query
     * @param  string $field
     * @param  string $input
     * @return Builder
     */
    protected function applyEquality($comparison, $query, $field, $input)
	{
		// Make SQL safe values
		$safe_field = $this->makeSafeField($field);
		$safe_input = $input  == '' ?
			'NULL' : DB::connection()->getPdo()->quote($input);

		// Different engines have different APIs
		switch(DB::getDriverName())
		{
			case 'mysql': return $this->applyMysqlEquality(
					$comparison, $query, $safe_field, $safe_input);
			case 'sqlite': return $this->applySqliteEquality(
					$comparison, $query, $safe_field, $safe_input);
            case 'sqlsrv': return $this->applySqlServerEquality(
					$comparison, $query, $safe_field, $safe_input);
        }
	}

    /**
     * Make SQL safe field name.  Different engines use different escapes:
     * https://stackoverflow.com/a/2901502/59160
     *
     * @param  string $field
     * @return string
     */
    protected function makeSafeField($field)
    {
        switch(DB::getDriverName())
		{
            case 'sqlsrv': return is_string($field) ? "[{$field}]" : $field;
            default: return is_string($field) ? "`{$field}`" : $field;
        }
    }

    /**
     * Make NULL-safe MySQL query
     * http://stackoverflow.com/a/19778341/59160
     *
     * @param  string $comparison
     * @param  Builder $query
     * @param  string $field
     * @param  string $input
     * @return Builder
     */
    protected function applyMysqlEquality($comparison, $query, $field, $input) {
		switch($comparison)
		{
			case '=':
				$sql = sprintf('%s <=> %s', $field, $input);
				return $query->whereRaw($sql);
			case '!=':
				$sql = sprintf('NOT(%s <=> %s)', $field, $input);
				return $query->whereRaw($sql);
		}
	}

	/**
     * Make NULL-safe SQLITE query
     * http://www.sqlite.org/lang_expr.html#isisnot
     *
     * @param  string $comparison
     * @param  Builder $query
     * @param  string $field
     * @param  string $input
     * @return Builder
     */
    protected function applySqliteEquality($comparison, $query, $field, $input) {
		switch($comparison)
		{
			case '=':
				$sql = sprintf('%s IS %s', $field, $input);
				return $query->whereRaw($sql);
			case '!=':
				$sql = sprintf('%s IS NOT %s', $field, $input);
				return $query->whereRaw($sql);
		}
	}

    /**
     * Make NULL-safe SQL Server query
     * https://stackoverflow.com/a/802666/59160
     *
     * @param  string $comparison
     * @param  Builder $query
     * @param  string $field
     * @param  string $input
     * @return Builder
     */
    protected function applySqlServerEquality($comparison, $query, $field, $input) {
		switch($comparison)
		{
			case '=':
				$sql = sprintf("COALESCE(%s, '') = COALESCE(%s, '')", $field, $input);
				return $query->whereRaw($sql);
			case '!=':
				$sql = sprintf("COALESCE(%s, '') != COALESCE(%s, '')", $field, $input);
				return $query->whereRaw($sql);
		}
	}

    /**
     * Make the shorthand options of the search config explicit
     *
     * @param  array $config Search config from the controller class definition
     * @return array
     */
    public function longhand($config)
    {
        $search = [];
        foreach ($config as $key => $val) {

            // Make locale menu
            if ($val == 'locale') {
                $search['locale'] = [
                    'type' => 'select',
                    'label' => 'Locale',
                    'options' => Config::get('decoy.site.locales'),
                ];

            // Not associative assume it's a text field
            } elseif (is_numeric($key)) {
                $search[$val] = ['type' => 'text', 'label' => Text::titleFromKey($val)];

            // If value isn't an array, make a default label
            } elseif (!is_array($val)) {
                $search[$key] = ['type' => $val, 'label' => Text::titleFromKey($key)];

            // Add the meta array
            } else {

                // Make a default label
                if (empty($val['label'])) {
                    $val['label'] = Text::titleFromKey($key);
                }

                // Support class static method or variable as options for a select
                if (!empty($val['type'])
                    && $val['type'] == 'select'
                    && !empty($val['options'])
                    && is_string($val['options'])) {
                    $val['options'] = $this->longhandOptions($val['options']);
                }

                // Apply the meta data
                $search[$key] = $val;
            }
        }

        return $search;
    }

    /**
     * Parse select options, returning a transformed array with static arrays
     * or callbacks executed
     *
     * @param  array $options
     * @return array
     */
    private function longhandOptions($options)
    {
        // Call static method.  You don't pass the paranethesis
        // to static calls
        if (preg_match('#::.+\(\)#', $options)) {
            return call_user_func(substr($options, 0, strlen($options) - 2));
        }

        // Return static variable
        if (preg_match('#::\$#', $options)) {
            list($class, $var) = explode('::$', $options);

            return $class::$$var;
        }

        // Unknown format
        throw new Exception('Could not parse option: '.$options);
    }

    /**
     * Make soft deletes condition if the controller supports trashed records.
     * Returns an array so it can be easily merged into exisitng configs.
     *
     * @param  Controller\Base $controller
     * @return array
     */
    public function makeSoftDeletesCondition($controller)
    {
        if (!$controller->withTrashed()) return [];
        return [
            'deleted_at' => [
                'type' => 'select',
                'label' => 'status',
                'options' => [
                    'exists' => 'exists',
                    'deleted' => 'deleted'
                ],
                'query' => function($query, $condition, $input) {

                    // If not deleted...
                    if (($input == 'exists' && $condition == '=') ||
                        ($input == 'deleted' && $condition == '!=')) {
                        $query->whereNull('deleted_at');

                    // If deleted...
                    } else if (($input == 'deleted' && $condition == '=') ||
                        ($input == 'exists' && $condition == '!=')) {
                        $query->whereNotNull('deleted_at');
                    }
                },
            ]
        ];
    }
}
