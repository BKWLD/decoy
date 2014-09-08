<?php namespace Bkwld\Decoy\Fields\Traits;

/**
 * Methods to assist in inserting markup needed for more
 * complex UIs at specific places in a form group
 */
trait InsertMarkup {

	/**
	 * Insert $html before the help-block
	 * 
	 * @param  string $html 
	 * @return void       
	 */
	public function beforeBlockHelp($html) {
		// TODO
	}

	/**
	 * Insert html at the very end of the group
	 * 
	 * @param  string $group The rendered group as html
	 * @param  string $html 
	 * @return string       
	 */
	public function appendToGroup($group, $html) {
		return preg_replace('#(</div>)$#', $html.'$1', $group);
	}

}