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
 * A fake company or corporation and such things that go with it.
 */
class Fake_Company extends Fake {
	
	protected $company_suffixes = 'Inc LLC Group';
	
	protected $bullshit_verbs = 'implement utilize integrate streamline optimize evolve transform embrace enable orchestrate leverage reinvent aggregate architect enhance incentivize morph empower envisioneer monetize harness facilitate seize disintermediate synergize strategize deploy brand grow target syndicate synthesize deliver mesh incubate engage maximize benchmark expedite reintermediate whiteboard visualize repurpose innovate scale unleash drive extend engineer revolutionize generate exploit transition e-enable iterate cultivate matrix productize redefine recontextualize';
	
	protected $bullshit_adjectives = 'clicks-and-mortar value-added vertical proactive robust revolutionary scalable leading-edge innovative intuitive strategic long-tail e-business mission-critical sticky one-to-one 24/7 end-to-end global B2B B2C granular frictionless virtual viral dynamic best-of-breed killer magnetic bleeding-edge web-enabled interactive dot-com sexy back-end real-time efficient front-end distributed seamless extensible turn-key world-class open-source cross-platform cross-media synergistic bricks-and-clicks out-of-the-box enterprise integrated impactful wireless transparent next-generation cutting-edge user-centric visionary customized ubiquitous plug-and-play collaborative compelling holistic rich';
	
	protected $bullshit_nouns = 'synergies web-readiness paradigms markets partnerships infrastructures platforms initiatives channels eyeballs communities ROI solutions e-tailers e-services action-items portals niches technologies content vortals supply-chains convergence relationships architectures interfaces e-markets e-commerce systems bandwidth infomediaries models mindshare deliverables users schemas networks applications metrics e-business functionalities experiences web services methodologies';
	
	/**
	 * A company name.
	 */
	public function name() {
		$faker = new Faker();
		$name = $faker->Text->words(1, 3, 'ucfirst');
		return  $name . ' ' . $this->lexicalize($this->company_suffixes, 0);
	}
	
	/**
	 * A slogan promoting unadulterated corporate bullshit.
	 *
	 * Based on the wordlist from http://dack.com/web/bullshit.html
	 */
	public function bullshit() {
		return implode(' ', array(
			ucfirst($this->lexicalize($this->bullshit_verbs)),
			$this->lexicalize($this->bullshit_adjectives),
			$this->lexicalize($this->bullshit_nouns)
		));
	}
	
}