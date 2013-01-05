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
 * @todo support variations of locales
 */
class Faker_Locale {
	
	/**
	 * Registered languages.
	 */
	private static $languages = array(
		"en" => "English"
	);
	
	/**
	 * Current locale.
	 */
	private static $locale;
	
	/**
	 * Set a default global locale for all data generators.
	 *
	 * @param string $code language code
	 */
	public static function set($code) {
		self::$locale = $code;
	}
	
	/**
	 * Return the default global locale.
	 *
	 * @return string language code
	 */
	public static function get() {
		return (self::$locale) ? self::$locale : 'en';
	}
	
}