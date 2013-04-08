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
 * Random color generator.
 */
class Fake_Color extends Fake {
	
	/**
	 * Return a random HTML-style hex color.
	 */
	public function hex() {
		$color = "#";
    	for ($i = 0; $i<6; $i++) {
    		$color .=  dechex(rand(0,15));
    	}
    	return $color;
	}
	
	/**
	 * Return a random RGB color with 8-bits per channel.
	 */
	public function rgb($glue=',') {
		$triplet = implode($glue, array(rand(0,255), rand(0,255), rand(0,255)));
		return "($triplet)";
	}
	
}