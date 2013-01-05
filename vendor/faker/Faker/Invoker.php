<?php

/**
 * Dynamic invoker that wraps method calls to fake objects and handles
 * cleanup of nested state.
 */
class Faker_Invoker {
	
	/**
	 * @param string $property name segment of class to locate
	 */
	public function __construct($property) {
		$this->fake = Faker_Locator::instance()->locate($property);
	}
	
	/**
	 * Invoke the given method on a fake object or traverse further
	 * down the invocation chain.
	 */
	public function __get($property) {
		if (is_callable($this->fake, $property)) {
			Faker_Locator::instance()->clear();
			return $this->fake->$property();
		} else {
			return new Faker_Invoker($property);
		}
	}
	
	/**
	 * Invoke the given method on the current fake object and
	 * clear the invocation chain.
	 */
	public function __call($method, $args) {
		if (is_callable(array($this->fake, $method))) {
			Faker_Locator::instance()->clear();
			return call_user_func_array(array($this->fake, $method), $args);
		} else {
			throw new Exception();
		}
	}
	
}