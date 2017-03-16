<?php

namespace Bkwld\Decoy\Fields\Traits;

/**
 * Save a scope
 */
trait Scopable
{
    /**
     * Preserve the scope
     *
     * @var callable
     */
    private $scope;

    /**
     * Allow the developer to customize the query for related items.  We'll execute the
     * scope function, passing it a reference to this query to customize
     *
     * @param  callable $callback
     * @return Field    A field
     */
    public function scope($callback)
    {
        if (is_callable($callback)) {
            $this->scope = $callback;
        }

        return $this;
    }
}
