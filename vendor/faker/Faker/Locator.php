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
 * Handles a dynamic invocation chain, enabling hierarchical traversal 
 * of the fake object API.
 */
class Faker_Locator {
	
	private static $instance = null;
	
	/**
	 * Singleton instance to share the lookup chain across contexts.
	 *
	 * @return Faker_Locator
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new Faker_Locator();
		}
		return self::$instance;
	}
	
	private $contextStack = array();
	
	private function __construct() {}
	
	/**
	 * Constructs a new fake object from the current lookup context.
	 */
	public function locate($name) {
		$this->contextStack[] = ucfirst($name);
		$classname = 'Fake_' . implode('_', $this->contextStack);
		return new $classname;
	}
	
	/**
	 * Clears the current context stack.
	 */
	public function clear() {
		$this->contextStack = array();
	}
}