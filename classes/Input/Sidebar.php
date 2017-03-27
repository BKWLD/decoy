<?php

namespace Bkwld\Decoy\Input;

/**
 * Utilities that the Decoy base controller can use to generate
 * the related content sidebar
 */
class Sidebar
{
    /**
     * The array of items to show in the sidebar
     *
     * @var array
     */
    private $items = [];

    /**
     * Items that should be added to the end of the sidebar
     *
     * @var array
     */
    private $ending_items = [];

    /**
     * The model instance currently being worked on by Decoy
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    private $parent;

    /**
     * Inject dependencies
     *
     * @param Illuminate\Database\Eloquent\Model $parent The model instance
     *                                                   currently being worked on by Decoy
     */
    public function __construct($parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Add an item to the sidebar
     *
     * @param mixed Generally an Bkwld\Decoy\Fields\Listing object or stringable
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add an item to the END of the sidebar, regardless of when it was added
     * in the logic flow
     *
     * @param mixed Generally an Bkwld\Decoy\Fields\Listing object or stringable
     * @return $this
     */
    public function addToEnd($item)
    {
        $this->ending_items[] = $item;

        return $this;
    }

    /**
     * Return whether the sidebar is empty or not
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->items) && empty($this->ending_items);
    }

    /**
     * Render an array of listing objects to an HTML string
     *
     * @return string HTML
     */
    public function render()
    {
        // Massage the response from base controller subclassings of sidebar
        $items = array_map(function ($item) {

            // If a listing instance, apply defaults common to all sidebar instances
            if (is_a($item, 'Bkwld\Decoy\Fields\Listing')) {
                return $item->layout('sidebar')->parent($this->parent)->__toString();
            }

            // Anything else will be converted to a string in the next step
            return $item;
        }, array_merge($this->items, $this->ending_items));

        // Combine all listing items into a single string and return
        return array_reduce($items, function ($carry, $item) {
            return $carry.$item;
        }, '');
    }
}
