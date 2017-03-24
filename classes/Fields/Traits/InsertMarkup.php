<?php

namespace Bkwld\Decoy\Fields\Traits;

/**
 * Methods to assist in inserting markup needed for more
 * complex UIs at specific places in a form group
 */
trait InsertMarkup
{
    /**
     * Insert $html before the help-block
     *
     * @param  string $html
     * @return void
     */
    public function beforeBlockHelp($html)
    {
        // TODO
    }

    /**
     * Insert html at the very end of the group unless the form is horizontal
     * in which case it goes inside the last sub-div (so that the classes that pad
     * the controls can take affect)
     *
     * @param  string $group The rendered group as html
     * @param  string $html
     * @return string
     */
    public function appendToGroup($group, $html)
    {
        // Horizontal form
        if (app('former.form')->isOfType('horizontal')) {
            return preg_replace('#(</div>\s*</div>)$#', $html.'$1', $group);

        }

        // Vertical form
        return preg_replace('#(</div>)$#', $html.'$1', $group);
    }
}
