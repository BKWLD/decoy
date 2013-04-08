<?php
/**
 * Faker {{version}}
 * A Fake Text Generator
 * <http://github.com/maetl/Faker>
 *
 * Copyright (c) 2011, Mark Rickerby <http://maetl.net>
 * All rights reserved.
 * 
 * This library is free software; refer to the terms in the LICENSE file found
 * with this source code for details about modification and redistribution.
 */

/**
 * Base class for fake things.
 */
abstract class Fake {
	
	/**
	 * Gets a list from the corpus of words.
	 *
	 * @return string
	 */
	protected function getCorpus() {
		$trace=debug_backtrace();
		array_shift($trace);
		$caller=array_shift($trace);
		$index = get_class($this) . '_' . $caller['function'];
		$index = str_replace('Fake', 'Faker_Corpus', $index);
		$index = '/'. str_replace('_', '/', $index) . '.' . 'txt';
		return self::getCorpusList($index);
	}
	
	/**
	 * Cache of stored word lists.
	 */
	private static $lists = array();
	
	/**
	 * Get a stored word list.
	 *
	 * @param string $index
	 * @return array|false
	 */
	private static function getCorpusList($index) {
		return (isset(self::$lists[$index])) ? self::$lists[$index] : self::addCorpusList($index);
	}
	
	/**
	 * Store a word list.
	 *
	 * @param string $index
	 * @param array|string $list
	 */
	private static function addCorpusList($index, $delimiter=' ') {
		$data = file_get_contents(dirname(__FILE__) . $index);
		$data = str_replace("\n", ' ', $data);
		$data = preg_replace('/\s\s+/', ' ', $data);
		$list = explode($delimiter, $data);
		self::$lists[$index] = $list;
		return self::$lists[$index];
	}
	
	/**
	 * Generates a random sequence of words from given input list.
	 *
	 * @param array $list input sequence of words
	 * @param int $min minimum number of words in the sequence (defaults to 1)
	 * @param int $max maximum number of words in the sequence (defaults to 1)
	 * @param string $filter apply an optional filter function to each word
	 * @return string
	 */
	public function lexicalize($list, $min=1, $max=1, $filter=false) {
		if (!is_array($list) && is_string($list)) {
			$list = explode(' ', $list);
		}
		$length = count($list);
		$total = ($min != $max) ? rand($min, $max) : $max;
		$counter = 0;
		$output = '';
		$used = array();
		while ($counter < $total) {
			$key = rand(1, $length);
			if (!$key || !isset($list[$key]) || in_array($key, $used)) continue;
			if ($counter != 0) $output .= ' ';
			$output .= ($filter) ? $filter($list[$key]) : $list[$key];
			$used[] = $key;
			$counter++;
		}
		return $output;
	}	
}