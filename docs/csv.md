# Exporting CSVs

To mark a model as available for export to CSV, set the `exportable` property of your model to `true`:

```php?start_inline=1
class Article extends \Bkwld\Decoy\Models\Base
{
    /**
     * Should the model be exportable as CSV?
     *
     * @var boolean
     */
    public $exportable = true;
}
```

Without any further configuration, all models will be converted to CSV rows by the Laravel serialization features (visible/hidden configuration and accessor methods).  Arrays in the output will be converted to a comma delimited value.  Objects will be JSON serialized.

### Customize the query

You can configure the Eloquent query used to build the CSV by adding an `exporting` scope to your model:

```php?start_inline=1
class Article extends \Bkwld\Decoy\Models\Base
{
    /**
     * Should the model be exportable as CSV?
     *
     * @var boolean
     */
    public $exportable = true;

    /**
     * Include soft deleted records in the export
     *
     * @return void
     */
    public function scopeExporting($query)
    {
        $query->withTrashed();
    }
}
```

### Customize the CSV row

You can configure how a model is converted to a CSV by defining a `forExport` method on your model.  

```php?start_inline=1
class Article extends \Bkwld\Decoy\Models\Base
{
    /**
     * Should the model be exportable as CSV?
     *
     * @var boolean
     */
    public $exportable = true;

    /**
     * Customize the CSV row
     *
     * @return array
     */
    public function forExport()
    {
        return [
            'ID' => $this->id,
            'Title' => $this->title,
            'Date' => $this->date->format('m/d/y'),
        ];
    }
}
```
