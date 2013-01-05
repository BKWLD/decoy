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
class Fake_Person extends Fake {
	
	const ProperNames = "Jackson Jack Bean Benetto Boris George Joerg Jakeson Mitch Bev Rich Shirl Shelly Hayden Howard Helen Helga Henriet Rufina Bell Elba Rosio Tomika Thalia Lieselotte Georgene Cynthia Marth Alane Gregorio Penelope Johnny Teresa Birgit Micaela Cruz Marta Jimmy Tiesha Charis Christiane Georgeann Kaylee Shannan Marcie Tynisha Julee Dorthey Tangela Karren Gwendolyn Darlene Vannesa Chasidy Germaine Easter Delinda Vincenza Lorene Albertha Collen Krissy Carlee Cleotilde Omer Eleonore Hollie Rigoberto Carolyne Douglas Sonya Yong Asley Reyna Erminia Twanda Wai Frederic Pa Donte Stefan Elana Arlene Maile Marlene Mike Candelaria Mellie Hettie Earlean Rodrick Shantell Rina Sharda Silvia Lilia Stan Moira Iesha Foster Dolores Altagracia Jacques Ofelia Jordan Lindy Christen Marcelo Renaldo Jacquetta Randal Jana Wan Arminda Joyce Malcom Doreatha Matt";
	
	public function name() {
		return $this->lexicalize(explode(' ', self::ProperNames), 2, 2, 'ucfirst');
	}
	
}