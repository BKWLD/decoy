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
 * A fake military industrial corporation.
 */
class Fake_Company_Aerospace extends Fake_Company {
	
	protected $military_verbs = 'evolve enable target deliver mesh unleash engineer institute exploit visualize orchestrate drive schedule instantiate define execute harness';
	
	protected $military_adjectives = 'virtual dynamic infra-red cyber flexinol electroactive armoured command-and-control 360-degree live ubiquitous tactical autonomous composite versatile mission-critical multipurpose remote-controlled bio-inspired self-organizing embedded context-aware full-spectrum';
	
	protected $military_nouns = 'soldiers actuation nanotubes technology polymers robotics systems weapons drones armour battlespace battlefields sensing-units UAVs UCAVs payloads USVs weaponry mechanisms';
	
	/**
	 * A slogan promoting rapacious profiteering from war.
	 */
	public function bullshit() {
		return implode(' ', array(
				ucfirst($this->lexicalize(explode(' ', $this->military_verbs))),
				$this->lexicalize(explode(' ', $this->military_adjectives), 1, 2),
				$this->lexicalize(explode(' ', $this->military_nouns))
			));
	}
	
}