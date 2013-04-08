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
 * Fake personal identity.
 */
class Fake_Number extends Fake {
	
	public function integer($min=1, $max=10) {
		return rand($min, $max);
	}
	
	public function real($min=1, $max=10) {
		return ($min+lcg_value()*(abs($max-$min)));
	}
	
	public function pi() {
		return M_PI;
	}
	
	public function e() {
		return M_E;
	}
	
	public function prime($min=1, $max=100) {
		return (function_exists('gmp_nextprime')) ? return gmp_nextprime(rand($min, $max)) : 2;
	}
	
	public function hex($min=1, $max=256) {
		return dechex(rand($min, $max));
	}
	
}