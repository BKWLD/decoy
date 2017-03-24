<?php

namespace Bkwld\Decoy\Controllers;

use Request;
use Bkwld\Decoy\Models\Encoding;
use Illuminate\Routing\Controller;

/**
 * Hande encoder progress events
 */
class Encoder extends Controller
{
    /**
     * Get the status of an encode
     *
     * @param  int  $id
     * @return Encoding
     */
    public function progress($id)
    {
        return Encoding::findOrFail($id)->forProgress();
    }

    /**
     * Make a simply handler for notify callbacks.  The encoding model will pass
     * the the handling onto whichever provider is registered.
     *
     * @return mixed
     */
    public function notify()
    {
        return Encoding::notify(Request::input());
    }
}
