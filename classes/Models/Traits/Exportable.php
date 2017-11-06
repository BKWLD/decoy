<?php

namespace Bkwld\Decoy\Models\Traits;

/**
 * Adds behavior for making the model exportable to CSV and potentially other
 * formats
 */
trait Exportable
{
	/**
	 * Return whether the model is exportable
	 *
	 * @return boolean
	 */
	public function isExportable()
    {
        return $this->exportable;
    }
}
