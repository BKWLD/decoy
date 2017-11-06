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
