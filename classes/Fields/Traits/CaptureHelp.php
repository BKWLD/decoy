<?php

namespace Bkwld\Decoy\Fields\Traits;

/**
 * Store block help attributes so I can access them later in the
 * rendering of a field
 */
trait CaptureHelp
{
    /**
     * Preserve help data
     *
     * @var string
     */
    private $help;

    /**
     * Preserve blockhelp data
     *
     * @var string
     */
    private $blockhelp;

    /**
     * Store the help locally
     *
     * @param  string $help       The help text
     * @param  array  $attributes Facultative attributes
     * @return $this
     */
    public function help($help, $attributes = [])
    {
        $this->help = $help;

        return parent::help($help, $attributes);
    }

    /**
     * Store the block help locally
     *
     * @param  string $help       The help text
     * @param  array  $attributes Facultative attributes
     * @return $this
     */
    public function blockhelp($help, $attributes = [])
    {
        $this->blockhelp = $help;

        return parent::help($help, $attributes);
    }

    /**
     * Was help defined
     *
     * @return boolean
     */
    public function hasHelp()
    {
        return isset($this->help) || isset($this->blockhelp);
    }
}
