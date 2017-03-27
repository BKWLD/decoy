<?php

namespace Bkwld\Decoy\Fields;

use Config;
use Illuminate\Support\Str;
use Former\Form\Fields\Checkbox;
use HtmlObject\Input as HtmlInput;
use Bkwld\Decoy\Observers\ManyToManyChecklist as ManyToManyChecklistObserver;

/**
 * Render a list of checkboxes to represent a related many-to-many table.  The
 * underlying Former field type is a checkbox.  The relationship names is stored
 * in the name.  The relationship instance that is being represented is stored
 * in the value.
 */
class ManyToManyChecklist extends Checkbox
{
    use Traits\CaptureLabel, Traits\Scopable, Traits\Helpers;

    /**
     * @var callable
     */
    protected $decorator;

    /**
     * Allow an implementation to customize the markup for each item. The callback
     * will get 2 params: the rendered html for the checkbox and the model
     * instance.  It should return a string, the html for th checkbox.
     *
     *
     * Example:
     *
     *    echo Former::manyToManyChecklist('categories')
     *    	->decorate(function($html, $model) {
     *    		return $html;
     *      })
     *
     * @param  callable $callback
     * @return $this
     */
    public function decorate($callback)
    {
        if (is_callable($callback)) {
            $this->decorator = $callback;
        }

        return $this;
    }

    /**
     * Prints out the field, wrapped in its group.  This is the opportunity
     * to tack additional stuff onto the control group
     *
     * @return string
     */
    public function wrapAndRender()
    {
        // Do not show the form at all if they don't have permission
        if (!app('decoy.user')->can('read', $this->name)) {
            return '';
        }

        // Add classes and continue
        $this->addGroupClass('many-to-many-checklist');

        return parent::wrapAndRender();
    }

    /**
     * Prints out the current tag, appending an extra hidden field onto it
     * for the storing of the foreign key
     *
     * @return string An input tag
     */
    public function render()
    {
        // Get an array of formatted data for Former checkboxes
        $boxes = [];
        foreach ($this->getRelations() as $row) {
            $boxes[$this->generateBoxLabel($row)] = $this->generateBox($row);
        }

        // Render the checkboxes, adding a hidden field after the set so that if
        // all boxes are un-checked, an empty value will be sent
        if (count($boxes)) {
            $this->checkboxes($boxes);

            return parent::render().HtmlInput::hidden($this->boxName());

        }

        // There are no relations yet, show a message to that effect
        return '<span class="glyphicon glyphicon-info-sign"></span>
            You have not <a href="/admin/'.Str::snake($this->name, '-').'">created</a>
            any <b>'.ucfirst($this->label_text).'</b>.';
    }

    /**
     * Get all the options in the related table.  Each will become a checkbox.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getRelations()
    {
        $class = ucfirst('App\\'.ucfirst(Str::singular($this->name)));
        $query = call_user_func([$class, 'ordered']);

        if ($this->scope) {
            call_user_func($this->scope, $query);
        }

        return $query->get();
    }

    /**
     * Generate the checkbox name using a special prefix that tells
     * Decoy to treat it has a many to many checkbox
     *
     * @return string
     */
    protected function boxName()
    {
        return ManyToManyChecklistObserver::PREFIX.$this->name.'[]';
    }

    /**
     * Take a model instance and generate a checkbox for it
     *
     * @param  Illuminate\Database\Eloquent\Model $row
     * @return array                              Configuration that is used by Former for a checkbox
     */
    protected function generateBox($row)
    {
        return [
            'name' => $this->boxName(),
            'value' => $row->getKey(),
            'checked' => ($children = $this->children())
                && $children->contains($row->getKey()),

            // Former is giving these a class of "form-control" which isn't correct
            'class' => false,

            // Add the model instance so the decorator can use it
            'model' => $row,
        ];
    }

    /**
     * Create the HTML label for a checkbox
     *
     * @param  Illuminate\Database\Eloquent\Model $row
     * @return string                             HTML
     */
    protected function generateBoxLabel($row)
    {
        // Generate the title
        $html = '<span class="title">'.$row->getAdminTitleHtmlAttribute().'</span>';

        // Add link to edit
        $url = '/'.Config::get('decoy.core.dir').'/'.Str::snake($this->name, '-')
            .'/'.$row->getKey().'/edit';
        if (app('decoy.user')->can('update', $url)) {
            $html .= '<a href="'.$url.'"><span class="glyphicon glyphicon-pencil edit"></span></a>';
        }

        // The str_replace fixes Former's auto conversion of underscores into spaces.
        $html = str_replace('_', '&#95;', $html);

        return $html;
    }

    /**
     * Get a collection of all the children that are already associated with the parent
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function children()
    {
        if (($item = $this->getModel()) && method_exists($item, $this->name)) {
            return $item->{$this->name};
        }
    }

    /**
     * Renders a checkable. This overrides the Former subclass so we can decorate
     * each individual `.checkbox`div.
     *
     * @param string|array $item          A checkable item
     * @param integer      $fallbackValue A fallback value if none is set
     *
     * @return string
     */
    protected function createCheckable($item, $fallbackValue = 1)
    {
        // Get the model reference out of the item and remove it before it's
        // rendered.  Otherwise it gets created as a data atrribute
        $model = $item['attributes']['model'];
        unset($item['attributes']['model']);

        // Render the checkbox as per normal
        $html = parent::createCheckable($item, $fallbackValue);

        // Mutate a checkable using user defined
        if ($this->decorator) {
            $html = call_user_func($this->decorator, $html, $model);
        }

        // Return html
        return $html;
    }
}
