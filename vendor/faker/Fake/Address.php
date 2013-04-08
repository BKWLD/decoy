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
 * A fake address.
 */
class Fake_Address extends Fake {
	
	private $cities = 'Sydney,Austin,Los Angeles,Miami,New York,London,Manchester,Bristol,Brisbane,Auckland,Frankfurt,Paris,Madrid,Warsaw,Kiev,Melbourne,Adelaide,Perth,Canberra,Bankok,Jakarta,Singapore,Hong Kong,Shanghai,Tokyo,Seoul,Mumbai,Madras,Newcastle,San Francisco,Seattle,Denver,Dallas,Santiago,Buenos Aires,São Paulo';
	
	/**
	 * @todo handle regionalized addresses
	 */
	public function city() {
		return $this->lexicalize(explode(',', $this->cities));
	}
	
	/**
	 * A US style phone number
	 */
	public function phone() {
		return rand(100,999).'-'.rand(100,999).'-'.rand(1000,9999);
	}
	
}