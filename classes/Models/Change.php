<?php

namespace Bkwld\Decoy\Models;

use DB;
use Decoy;
use Config;
use DecoyURL;
use Illuminate\Support\Str;
use Bkwld\Decoy\Input\Search;
use Bkwld\Library\Utils\Text;
use Illuminate\Database\Eloquent\Model;

/**
 * Reperesents a single model change event.  Typically a single CRUD action on
 * a model.
 */
class Change extends Base
{
    /**
     * The query param key used when previewing
     *
     * @var string
     */
    const QUERY_KEY = 'view-change';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'changed' => 'array',
    ];

    /**
     * Get the admin associated with the change
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function admin()
    {
        return $this->belongsTo('Bkwld\Decoy\Models\Admin');
    }

    /**
     * The polymorphic relation back to the parent model
     *
     * @var mixed
     */
    public function loggable()
    {
        return $this->morphTo('loggable', 'model', 'key');
    }

    /**
     * Get the related model, including trashed instances
     *
     * @return Model
     */
    public function changedModel()
    {
        return $this->loggable()->withTrashed();
    }

    /**
     * Default ordering by descending time, designed to be overridden
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('changes.id', 'desc')->with('admin');
    }

    /**
     * Don't log changes
     *
     * @param  string $action
     * @return boolean
     */
    public function shouldLogChange($action)
    {
        return false;
    }

    /**
     * A convenience method for saving a change instance
     *
     * @param  Model  $model  The model being touched
     * @param  string $action Generally a CRUD verb: "created", "updated", "deleted"
     * @param  Admin  $admin  The admin acting on the record
     * @return static|void
     */
    public static function log(Model $model, $action, Admin $admin = null)
    {
        // Create a new change instance
        if (static::shouldWriteChange($model, $action)) {
            $changed = static::getChanged($model, $action);
            $change = static::createLog($model, $action, $admin, $changed);
        }

        // Log published / unpblished changes
        static::logPublishingChange($model, $action, $admin);

        // If the action was a deletion, mark all of the records for this model as
        // deleted
        if ($action == 'deleted') {
            DB::table('changes')
                ->where('model', get_class($model))
                ->where('key', $model->getKey())
                ->update(['deleted' => 1]);
        }

        // Return the changed instance
        if (isset($change)) {
            return $change;
        }
    }

    /**
     * Don't log changes when the only thing that changed was the published
     * state or updated timestamp.  We check if there are any attributes
     * besides these that changed.
     *
     * @param  Model  $model  The model being touched
     * @param  string $action
     * @return boolean
     */
    static private function shouldWriteChange(Model $model, $action)
    {
        if (in_array($action, ['created', 'deleted'])) return true;
        $changed_attributes = array_keys($model->getDirty());
        $ignored = ['updated_at', 'public'];
        $loggable = array_diff($changed_attributes, $ignored);
        return count($loggable) > 0;
    }

    /**
     * Get the changes attributes
     *
     * @param  Model  $model  The model being touched
     * @param  string $action
     * @return array|null
     */
    static private function getChanged(Model $model, $action)
    {
        $changed = $model->getDirty();
        if ($action == 'deleted' || empty($changed)) {
            $changed = null;
        }
        return $changed;
    }

    /**
     * Create a change entry
     *
     * @param  Model  $model  Th
     * @param  string $action
     * @param  Admin  $admin
     */
    static protected function createLog(
        Model $model,
        $action,
        Admin $admin = null,
        $changed = null)
    {
        return static::create([
            'model' => get_class($model),
            'key' => $model->getKey(),
            'action' => $action,
            'title' => static::getModelTitle($model),
            'changed' => $changed,
            'admin_id' => static::getAdminId($admin),
        ]);
    }

    /**
     * Get the title of the model
     *
     * @param  Model  $model
     * @return string
     */
    static protected function getModelTitle(Model $model)
    {
        return method_exists($model, 'getAdminTitleAttribute') ?
            $model->getAdminTitleAttribute() : null;
    }

    /**
     * Get the admin id
     *
     * @param  Admin $admin
     * @return integer
     */
    static protected function getAdminId(Admin $admin = null)
    {
        if (!$admin) {
            $admin = app('decoy.user');
        }
        return $admin ? $admin->getKey() : null;
    }

    /**
     * Log changes to publishing state.  The initial publish should be logged
     * but not an initil unpublished state.
     *
     * @param  Model  $model
     * @param  string $action
     * @param  Admin $admin
     * @return void
     */
    static public function logPublishingChange(
        Model $model,
        $action,
        Admin $admin = null)
    {
        if ($model->isDirty('public')) {
            if ($model->public) {
                static::createLog($model, 'published', $admin);
            } else if (!$model->public && $action != 'created') {
                static::createLog($model, 'unpublished', $admin);
            }
        }
    }

    /**
     * Return a list of all the actions currently being used as a hash for use
     * in a select menu
     *
     * @return array
     */
    public static function getActions()
    {
        return static::groupBy('action')->pluck('action', 'action')
            ->mapWithKeys(function ($item) {
                return [$item => __("decoy::changes.actions.$item")];
            });
    }

    /**
     * Return a list of all the admins as a hash for use in a select menu
     *
     * @return array
     */
    public static function getAdmins()
    {
        return Admin::all(['id', 'email'])->pluck('email', 'id');
    }

    /**
     * Format the the activity like a sentence
     *
     * @return string HTML
     */
    public function getAdminTitleHtmlAttribute()
    {
        return __('decoy::changes.admin_title', [
            'admin' => $this->getAdminLinkAttribute(),
            'action' => $this->getActionLabelAttribute(),
            'model' => $this->getModelNameHtmlAttribute(),
            'model_title' => $this->getLinkedTitleAttribute(),
            'date' => $this->getDateAttribute()
        ]);
    }

    /**
     * Get the admin name and link
     *
     * @return string HTML
     */
    public function getAdminLinkAttribute()
    {
        if ($this->admin_id) {
            return sprintf('<a href="%s">%s</a>',
                $this->filterUrl(['admin_id' => $this->admin_id]),
                $this->admin->getAdminTitleHtmlAttribute());
        } else {
            return 'Someone';
        }
    }

    /**
     * Format the activity as a colored label
     *
     * @return string HTML
     */
    public function getActionLabelAttribute()
    {
        $map = [
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'published' => 'info',
            'unpublished' => 'default',
        ];

        return sprintf('<a href="%s" class="label label-%s">%s</a>',
            $this->filterUrl(['action' => $this->action]),
            isset($map[$this->action]) ? $map[$this->action] : 'info',
            __("decoy::changes.actions.$this->action"));
    }

    /**
     * Format the model name by translating it through the controllers's defined
     * title
     *
     * @return string HTML
     */
    public function getModelNameHtmlAttribute()
    {
        $class = Decoy::controllerForModel($this->model);

        // There is not a controller for the model
        if (!$class || !class_exists($class)) {
            return sprintf('<b><a href="%s">%s</a></b>',
            $this->filterUrl(['model' => $this->model]),
            preg_replace('#(?<!\ )[A-Z]#', ' $0', $this->model));
        }

        // There is a corresponding controller class
        $controller = new $class;
        return sprintf('<b class="js-tooltip" title="%s"><a href="%s">%s</a></b>',
            htmlentities($controller->description()),
            $this->filterUrl(['model' => $this->model]),
            Str::singular($controller->title()));
    }

    /**
     * Get the title of the model. Perhaps in the future there will be more smarts
     * here, like generating a link to the edit view
     *
     * @return string HTML
     */
    public function getLinkedTitleAttribute()
    {
        if (!$this->title) return;
        return sprintf('<a href="%s">"%s"</a>',
            $this->filterUrl(['model' => $this->model, 'key' => $this->key]),
            $this->title);
    }

    /**
     * Get the date of the change
     *
     * @return string HTML
     */
    public function getDateAttribute()
    {
        \Carbon\Carbon::setLocale(Decoy::locale());
        return sprintf('<a href="%s" class="js-tooltip" title="%s">%s</a>',
            $this->filterUrl(['created_at' => $this->created_at->format('m/d/Y')]),
            $this->getHumanDateAttribute(),
            $this->created_at->diffForHumans());
    }

    /**
     * Get the human readable date
     *
     * @return string
     */
    public function getHumanDateAttribute()
    {
        return $this->created_at->format(__('decoy::changes.human_date'));
    }

    /**
     * Customize the action links
     *
     * @param  array $data The data passed to a listing view
     * @return array
     */
    public function makeAdminActions($data)
    {
        return array_filter([
            $this->filter_action,
            $this->changes_action,
            $this->preview_action,
        ]);
    }

    /**
     * Make the preview filter icon
     *
     * @return string
     */
    public function getFilterActionAttribute()
    {
        return sprintf('<a href="%s"
            class="glyphicon glyphicon-filter js-tooltip"
            title="' . __('decoy::changes.standard_list.filter') . '"
            data-placement="left"></a>',
            $this->filterUrl(['model' => $this->model, 'key' => $this->key]),
            strip_tags($this->getModelNameHtmlAttribute()));
    }

    /**
     * Make a link to filter the result set
     *
     * @return string
     */
    public function filterUrl($query)
    {
        return DecoyURL::action('changes').'?'.Search::query($query);
    }

    /**
     * Make the changes icon
     *
     * @return string
     */
    public function getChangesActionAttribute()
    {
        // If there are changes, add the modal button
        if ($this->changed) {
            return sprintf('<a href="%s"
                class="glyphicon glyphicon-export js-tooltip changes-modal-link"
                title="%s" data-placement="left"></a>',
                DecoyURL::action('changes@edit', $this->id),
                __('decoy::changes.standard_list.view'));
        }

        // Else, show a disabled button
        else {
            return sprintf('<span
            class="glyphicon glyphicon-export js-tooltip"
            title="%s" data-placement="left"></span>', __('decoy::changes.standard_list.no_changed'));
        }
    }

    /**
     * Make link to preview a version as long as the model has a URI and the
     * action wasn't a delete action.
     *
     * @return string
     */
    public function getPreviewActionAttribute()
    {
        if ($this->changedModel
            && $this->changedModel->uri
            && $this->action != 'deleted') {
            return sprintf('<a href="%s" target="_blank"
                class="glyphicon glyphicon-bookmark js-tooltip"
                title="%s" data-placement="left"></a>',
                $this->preview_url,
                __('decoy::changes.standard_list.preview'));
        } else {
            return '<span class="glyphicon glyphicon-bookmark disabled"></span>';
        }
    }

    /**
     * Make the preview URL for a the model
     *
     * @return string
     */
    public function getPreviewUrlAttribute()
    {
        return vsprintf('%s?%s=%s', [
            $this->changedModel->uri,
            static::QUERY_KEY,
            $this->id,
        ]);
    }

    /**
     * Get just the attributes that should be displayed in the admin modal.
     *
     * @return array
     */
    public function attributesForModal()
    {
        // Remove some specific attributes.  Leaving empties in there so the updating
        // of values to NULL is displayed.
        $attributes = array_except($this->changed, [
            'id', 'updated_at', 'created_at', 'password', 'remember_token',
        ]);

        // Make more readable titles
        $out = [];
        foreach ($attributes as $key => $val) {
            $out[Text::titleFromKey($key)] = $val;
        }

        return $out;
    }
}
