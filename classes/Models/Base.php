<?php

namespace Bkwld\Decoy\Models;

use DB;
use App;
use URL;
use Decoy;
use Event;
use Config;
use Session;
use DecoyURL;
use Bkwld\Cloner\Cloneable;
use Bkwld\Upchuck\SupportsUploads;
use Bkwld\Library\Utils\Collection;
use Bkwld\Decoy\Exceptions\Exception;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Bkwld\Decoy\Collections\Base as BaseCollection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class Base extends Eloquent
{

    /**
     * Adding common traits.  The memory usage of adding additional methods is
     * negligible.
     */
    use Cloneable,
        Sluggable,
        SluggableScopeHelpers,
        SupportsUploads,
        Traits\CanSerializeTransform,
        Traits\Exportable,
        Traits\Loggable
    ;

    /**
     * Use the Decoy Base Collection
     *
     * @param  array  $models
     * @return Images
     */
    public function newCollection(array $models = [])
    {
        return new BaseCollection($models);
    }

    //---------------------------------------------------------------------------
    // Overrideable properties
    //---------------------------------------------------------------------------

    /**
     * This should be overridden by Models to store the array of their
     * Laravel validation rules
     *
     * @var array
     */
    public static $rules = [];

    /**
     * Should this model be localizable in the admin.  If not undefined, will
     * override the site config "auto_localize_root_models"
     *
     * @var boolean
     */
    public static $localizable;

    /**
     * If false, this model cannot be cloned
     *
     * @var boolean
     */
    public $cloneable = true;

    /**
     * Specify columns that shouldn't be duplicated by Bkwld\Cloner.  Include
     * slug by default so that Sluggable will automatically generate a new one.
     *
     * @var array
     */
    protected $clone_exempt_attributes = ['slug'];

    /**
     * Relations to follow when models are duplicated
     *
     * @var array
     */
    protected $cloneable_relations;

    /**
     * If populated, these will be used instead of the files that are found
     * automatically by getCloneableFileAttributes()
     *
     * @var array
     */
    protected $cloneable_file_attributes;

    /**
     * Should the model be exportable as CSV?
     *
     * @var boolean
     */
    public $exportable = false;

    /**
     * If populated, these will ignore the override mutators in admin that are
     * in hasGetMutator() and hasSetMutator()
     *
     * @var array
     */
    protected $admin_mutators = [];

    /**
     * Disable mutators unless the active request isn't for the admin, the key
     * doesn't reference a true database-backed attribute, or the key was
     * expressly whitelisted in the admin_mutators property.
     *
     * @param  string  $key
     * @return mixed
     */
    public function hasGetMutator($key)
    {
        if (!Decoy::handling()
            || !array_key_exists($key, $this->attributes)
            || in_array($key, $this->admin_mutators)) {
            return parent::hasGetMutator($key);
        }
    }

    /**
    * Disable mutators unless the active request isn't for the admin, the key
    * doesn't reference a true database-backed attribute, or the key was
    * expressly whitelisted in the admin_mutators property.
    *
    * @param  string  $key
    * @return mixed
     */
    public function hasSetMutator($key)
    {
        if (!Decoy::handling()
            || !array_key_exists($key, $this->attributes)
            || in_array($key, $this->admin_mutators)) {
            return parent::hasSetMutator($key);
        }
    }

    /**
     * No-Op callbacks invoked by Observers\ModelCallbacks.  These allow quick handling
     * of model event states.
     *
     * @return  void|false
     */
    public function onSaving() { }
    public function onSaved() { }
    public function onCreating() { }
    public function onCreated() { }
    public function onUpdating() { }
    public function onUpdated() { }
    public function onDeleting() { }
    public function onDeleted() { }

    /**
     * Validation callback no-ops
     *
     * @param  $validation Illuminate\Validation\Validator
     * @return void|false
     */
    public function onValidating($validation) { }
    public function onValidated($validation) { }

    /**
     * Many to many attach/detach callback no-ops
     *
     * @param $parent Eloquent\Model
     * @return void|false
     */
    public function onAttaching($parent) { }
    public function onAttached($parent) { }
    public function onRemoving($parent) { }
    public function onRemoved($parent) { }

    //---------------------------------------------------------------------------
    // Instantiation
    //---------------------------------------------------------------------------

    /**
     * Constructor registers events and configures mass assignment
     */
    public function __construct(array $attributes = [])
    {
        // Remove any settings that affect JSON conversion (visible / hidden) and
        // mass assignment protection (fillable / guarded) while in the admin
        if (Decoy::handling()) {
            $this->visible = $this->hidden = $this->fillable = $this->guarded = [];
        }

        // Blacklist special columns that aren't intended for the DB
        $this->guarded = array_merge($this->guarded, [
            'parent_controller', // Backbone.js sends this with sort updates
            'parent_id', // Backbone.js may also send this with sort
            'select-row', // This is the name of the checkboxes used for bulk delete
        ]);

        // Continue Laravel construction
        parent::__construct($attributes);
    }

    //---------------------------------------------------------------------------
    // Slug creation via cviebrock/eloquent-sluggable
    //---------------------------------------------------------------------------

    /**
     * Tell sluggable where to get the source for the slug and apply other
     * customizations.
     *
     * @return array
     */
    public function sluggable()
    {
        if (!$this->needsSlugging()) return [];
        return [
            'slug' => [
                'source' => 'admin_title',
                'maxLength' => 100,
                'includeTrashed' => true,
            ]
        ];
    }

    /**
     * Check for a validation rule for a slug column
     *
     * @return boolean
     */
    protected function needsSlugging()
    {
        return array_key_exists('slug', static::$rules);
    }

    //---------------------------------------------------------------------------
    // Accessors
    //---------------------------------------------------------------------------

    /**
     * Return the title for the row for the purpose of displaying in admin list
     * views and breadcrumbs.  It looks for columns that are named like common
     * things that would be titles.
     *
     * @return string
     */
    public function getAdminTitleHtmlAttribute()
    {
        return $this->getAdminThumbTagAttribute().$this->getAdminTitleAttribute();
    }

    /**
     * Deduce the source for the title of the model and return that title
     *
     * @return string
     */
    public function getAdminTitleAttribute()
    {
        return implode(' ', array_map(function ($attribute) {
            return $this->$attribute;
        }, $this->titleAttributes())) ?: __('decoy::base.untitled');
    }

    /**
     * Add a thumbnail img tag to the title
     *
     * @return string IMG tag
     */
    public function getAdminThumbTagAttribute()
    {
        if (!$url = $this->getAdminThumbAttribute()) {
            return;
        }

        return sprintf('<img src="%s" alt="">', $url);
    }

    /**
     * The URL for the thumbnail
     *
     * @return string URL
     */
    public function getAdminThumbAttribute($width = 40, $height = 40)
    {

        // Check if there are images for the model
        if (!method_exists($this, 'images')) {
            return;
        }

        $images = $this->images;
        if ($images->isEmpty()) {
            return;
        }

        // Get null-named (default) images first
        return $images->sortBy('name')->first()->crop($width, $height)->url;
    }

    /**
     * Get the URL sitemaps generated by Bkwld\SitemapFromRoutes
     *
     * @return string
     */
    public function getSitemapUrlAttribute()
    {
        return $this->uri;
    }

    /**
     * A no-op that should return the URI (an absolute path or a fulL URL) to the record
     *
     * @return string
     */
    public function getUriAttribute() {}

    /**
     * Get all file fields by looking at Upchuck config and validation rules
     *
     * @return array The keys of all the attributes that store file references
     */
    public function getFileAttributesAttribute()
    {

        // Get all the file validation rule keys
        $attributes = array_keys(array_filter(static::$rules, function ($rules) {
            return preg_match('#file|image|mimes|video|dimensions#i', $rules);
        }));

        // Get all the model attributes from upchuck
        if (method_exists($this, 'getUploadMap')) {
            $attributes = array_unique(array_merge($attributes,
                array_values($this->getUploadMap())));
        }

        // Return array of attributes
        return $attributes;
    }

    /**
     * Use getFileAttributesAttribute() to get the files that should be cloned
     * by Bkwld\Cloner
     *
     * @return array The keys of all the attributes that store file references
     */
    public function getCloneableFileAttributes()
    {
        if (isset($this->cloneable_file_attributes)) {
            return $this->cloneable_file_attributes;
        }

        return $this->getFileAttributesAttribute();
    }

    /**
     * Automatically add classes to rows in listing tables in the admin
     *
     * @return string
     */
    public function getAdminRowClassAttribute() {
        $classes = [];

        // Add a visbility classs
        if ($this->public) {
            $classes[] = 'is-public';
        }

        // Add a soft-deleted class
        if (method_exists($this, 'trashed') && $this->trashed()) {
            $classes[] = 'is-trashed';
        }

        // Return all classes
        return implode(' ', $classes);
    }

    /**
     * Expose model attributes for comparison by the localization sidebar
     *
     * @return array
     */
    public function getAttributesForLocalizationComparisonAttribute()
    {
        $attributes = $this->getAttributes();
        if (method_exists($this, 'croppedImages')) {
            $attributes['images'] = $this->croppedImages(300);
        }
        return $attributes;
    }

    //---------------------------------------------------------------------------
    // Listing view, action-column accessors
    //---------------------------------------------------------------------------

    /**
     * Make the markup for the actions column of the admin listing view.  The
     * indivudal actions are stored in an array that is iterted through in the
     * view
     *
     * @param  array $data The data passed to a listing view
     * @return array
     */
    public function makeAdminActions($data)
    {
        $actions = [];

        if ($html = $this->makeVisibilityAction($data)) {
            $actions['visibility'] = $html;
        }

        if ($html = $this->makeEditAction($data)) {
            $actions['edit'] = $html;
        }

        if ($html = $this->makeViewAction($data)) {
            $actions['view'] = $html;
        }

        if ($html = $this->makeDeleteAction($data)) {
            $actions['delete'] = $html;
        }

        return $actions;
    }

    /**
     * Make the visibility state action
     *
     * @param  array  $data The data passed to a listing view
     * @return string
     */
    protected function makeVisibilityAction($data)
    {
        extract($data);

        // Check if this model supports editing the visibility
        if ($many_to_many
            || !app('decoy.user')->can('publish', $controller)
            || !array_key_exists('public', $this->attributes)) {
            return;
        }

        // Create the markup
        $public = $this->getAttribute('public');
        return sprintf('<a class="visibility js-tooltip" data-placement="left" title="%s">
                <span class="glyphicon glyphicon-eye-%s"></span>
            </a>',
            $public ? __('decoy::base.standard_list.private') : __('decoy::base.standard_list.publish'),
            $public ? 'open' : 'close'
        );
    }

    /**
     * Make the edit or view action.
     *
     * @param  array  $data The data passed to a listing view
     * @return string
     */
    protected function makeEditAction($data)
    {
        extract($data);

        // Make markup
        $editable = app('decoy.user')->can('update', $controller);
        return sprintf('<a href="%s" class="action-edit js-tooltip"
            data-placement="left" title="%s">
                <span class="glyphicon glyphicon-%s"></span>
            </a>',
            $this->getAdminEditUri($controller, $many_to_many), // URL
            $editable ? // Label
                __('decoy::base.action.edit') :
                __('decoy::base.action.read') ,
            $editable ? 'pencil' : 'zoom-in' // Icon
        );
    }

    /**
     * Get the admin edit URL assuming you know the controller and whether it's
     * being listed as a many to many
     *
     * @param  string  $controller   ex: Admin\ArticlesController
     * @param  boolean $many_to_many
     * @return string
     */
    public function getAdminEditUri($controller, $many_to_many = false)
    {
        if ($many_to_many) {
            return URL::to(DecoyURL::action($controller.'@edit', $this->getKey()));
        }

        return URL::to(DecoyURL::relative('edit', $this->getKey(), $controller));
    }

    /**
     * Make the view action
     *
     * @param  array  $data The data passed to a listing view
     * @return string
     */
    protected function makeViewAction($data)
    {
        if (!$uri = $this->getUriAttribute()) {
            return;
        }

        return sprintf('<a href="%s" target="_blank" class="action-view js-tooltip"
            data-placement="left" title="' . __('decoy::base.action.view') . '">
                <span class="glyphicon glyphicon-bookmark"></span>
            </a>', $uri);
    }

    /**
     * Make the delete action
     *
     * @param  array  $data The data passed to a listing view
     * @return string
     */
    protected function makeDeleteAction($data)
    {
        extract($data);

        // Check if this model can be deleted.  This mirrors code found in the table
        //  partial for generating the edit link on the title
        if (!(app('decoy.user')->can('destroy', $controller)
            || ($many_to_many && app('decoy.user')->can('update', $parent_controller)))) {
            return;
        }

        // If soft deleted, show a disabled icon
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return '<span class="glyphicon glyphicon-trash"></span>';
        }

        // Make the label
        $label = $many_to_many ?
            __('decoy::base.action.remove') :
            $with_trashed ?
                __('decoy::base.action.soft_delete') :
                __('decoy::base.action.delete') ;

        // Return markup
        return sprintf('<a class="%s js-tooltip" data-placement="left" title="%s">
                <span class="glyphicon glyphicon-%s"></span>
            </a>',
            $many_to_many ? 'remove-now' : 'delete-now',
            $label,
            $many_to_many ? 'remove' : 'trash'
        );
    }

    //---------------------------------------------------------------------------
    // Scopes
    //---------------------------------------------------------------------------

    /**
     * Search the title (where "title" is the admin definiton of the title) for
     * the terms.  This is designed for the Decoy autocomplete
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @param  string                            $term
     * @throws Bkwld\Decoy\Exceptions\Exception
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeTitleContains($query, $term, $exact = false)
    {
        // Get an instance so the title attributes can be found.
        if (!$model = static::first()) {
            return;
        }

        // Get the title attributes
        $attributes = $model->titleAttributes();
        if (empty($attributes)) {
            throw new Exception('No searchable attributes');
        }

        // Concatenate all the attributes with spaces and look for the term.
        switch(DB::getDriverName()) {
            case 'mysql':
                $source = DB::raw('CONCAT('.implode('," ",', $attributes).')');
                break;
            case 'sqlite':
            case 'pgsql':
                $source = DB::raw(implode(' || ', $attributes));
                break;

            // For SQL Server, only support concatenating of two attributes so
            // it works in 2008 and above.
            // https://stackoverflow.com/a/47423292/59160
            case 'sqlsrv':
                if (count($attributes) == 2) {
                    $source = DB::raw('{fn CONCAT('.implode(',', $attributes).')}');
                } else {
                    $source = $attributes[0];
                }
        }

        return $exact ?
            $query->where($source, '=', $term) :
            $query->where($source, 'LIKE', "%$term%");
    }

    /**
     * Default ordering by descending time, designed to be overridden
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeOrdered($query)
    {
        if ($this->usesTimestamps()) {
            $query->orderBy($this->getTable().'.created_at', 'desc');
        }
        return $query;
    }

    /**
     * Get publically visible items. The scope couldn't be `public` because PHP
     * took issue with it as a function name.
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeIsPublic($query)
    {
        return $query->where($this->getTable().'.public', '1');
    }

    /**
     * Get all public items by the default order
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeOrderedAndPublic($query)
    {
        return $query->ordered()->isPublic();
    }

    /**
     * Get all public items by the default order.  This is a good thing to
     * subclass to define special listing scopes used ONLY on the frontend.  As
     * compared with scopeOrdered().
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeListing($query)
    {
        return $query->orderedAndPublic();
    }

    /**
     * Order a table that has a position value
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopePositioned($query)
    {
        $query->orderBy($this->getTable().'.position', 'asc');
        if ($this->usesTimestamps()) {
            $query->orderBy($this->getTable().'.created_at', 'desc');
        }
        return $query;
    }

    /**
     * Get only public records by default for Bkwld\SitemapFromRoute
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeForSitemap($query)
    {
        return $query->isPublic();
    }

    /**
     * Randomize the results in the DB.  This shouldn't be used for large datasets
     * cause it's not very performant
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @param  mixed                             $seed  Providing a seed keeps the order the same on subsequent queries
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeRandomize($query, $seed = false)
    {
        if ($seed === true) {
            $seed = Session::getId();
        }

        if ($seed) {
            return $query->orderBy(DB::raw('RAND("'.$seed.'")'));
        }

        return $query->orderBy(DB::raw('RAND()'));
    }

    /**
     * Filter by the current locale
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @param  string                            $locale
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeLocalize($query, $locale = null)
    {
        return $query->where('locale', $locale ?: Decoy::locale());
    }

    /**
     * Get localized siblings of this model
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeOtherLocalizations($query)
    {
        return $query->where('locale_group', $this->locale_group)
            ->where($this->getKeyName(), '!=', $this->getKey());
    }

    /**
     * Find by the slug and fail if missing.  Invokes methods from the
     * Sluggable trait.
     *
     * @param  string $string
     * @param  array $columns
     * @return Illuminate\Database\Eloquent\Model
     *
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findBySlugOrFail($slug, array $columns = ['*'])
    {
        // Model not found, throw exception
        if (!$item = static::findBySlug($slug)) {
            throw (new ModelNotFoundException)->setModel(get_called_class());
        }

        // Return the model if visible
        $item->enforceVisibility();

        return $item;
    }

    //---------------------------------------------------------------------------
    // Utility methods
    //---------------------------------------------------------------------------

    /**
     * Throw exception if not public and no admin session
     *
     * @throws Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function enforceVisibility()
    {
        if (array_key_exists('public', $this->getAttributes())
            && !$this->getAttribute('public')
            && !app('decoy.user')) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * Fire an Decoy model event.
     *
     * @param $string  event The name of this event
     * @param $array   args  An array of params that will be passed to the handler
     * @return object
     */
    public function fireDecoyEvent($event, $args = null)
    {
        $event = "decoy::model.{$event}: ".get_class($this);

        return Event::fire($event, $args);
    }

    /**
     * Deduce the source for the title of the model
     *
     * @return array
     */
    public function titleAttributes()
    {
        // Convert to an array so I can test for the presence of values. As an
        // object, it would throw exceptions
        $row = $this->getAttributes();

         // Name before title to cover the case of people with job titles
        if (isset($row['name'])) {
            return ['name'];
        }

        // Search full names if people-type fields
        if (isset($row['first_name']) && isset($row['last_name'])) {
            return ['first_name', 'last_name'];
        }

        // Standard location for the title
        if (isset($row['title'])) {
            return ['title'];
        }

        // Default to no searchable attributes
        return [];
    }

    /**
     * The pivot_id may be accessible at $this->pivot->id if the result was fetched
     * through a relationship OR it may be named pivot_id out of convention (something
     * currently done in Decoy_Base_Controller->get_index_child()).  This function
     * checks for either
     *
     * @return integer
     */
    public function pivotId()
    {
        if (!empty($this->pivot->id)) {
            return $this->pivot->id;
        }

        if (!empty($this->pivot_id)) {
            return $this->pivot_id;
        }

        return null;
    }

    /**
     * Add a field to the blacklist
     *
     * @param string $field
     */
    public function blacklist($field)
    {
        $this->guarded[] = $field;
    }
}
