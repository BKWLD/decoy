<?php

namespace Bkwld\Decoy\Collections;

use Illuminate\Database\Eloquent\Collection;
use Bkwld\Decoy\Models\Traits\SerializeWithImages;
use Bkwld\Decoy\Models\Traits\CanSerializeTransform;

/**
 * The collection that is returned from queries on models that extend from
 * Decoy's base model.  Adds methods to tweak the serialized output
 */
class Base extends Collection
{
    use CanSerializeTransform,
        SerializeWithImages;
}
