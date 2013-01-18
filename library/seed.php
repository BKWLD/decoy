<?php namespace Decoy;

// Imports
use Laravel\Bundle;
use Laravel\Config;
use Laravel\Database as DB;
use Faker; // Otherwise, Faker gets seen as Decoy\Faker

/**
 * This abstract class contains utitlies to use with writing
 * tasks to seed a project with data
 */
abstract class Seed {
	
	// Directory for saving images
	static protected $UPLOADS;
	
	// All functions require an instance of Faker
	protected $faker;
	
	// Initialize
	function __construct() {

		// Get a reference to faker
		require_once(Bundle::path('decoy').'vendor/faker/Faker.php');
		$this->faker = new Faker();
		
		// Set the upload path
		self::$UPLOADS = Config::get('decoy::decoy.upload_dir').'/seeded/';
		if (!is_dir(self::$UPLOADS)) mkdir(self::$UPLOADS, 0700, true);
		
		// Set the ckfinder path
		self::$CKFINDER = Config::get('decoy::decoy.ckfinder_upload_dir');
		if (!is_dir(self::$CKFINDER)) mkdir(self::$CKFINDER, 0700, true);
	}
	
	// ---------------------------------------------------
	// Utilities
	// ---------------------------------------------------


	// Return a random image
	protected function random_image() {
		return $this->file_path($this->faker->image->random(self::$UPLOADS));
	}
	
	// Output something to console
	protected function log($info) {
		echo $info."\n";
	}
	
	// Optionally return a value
	protected function optional($val) {
		if (rand(0,1)) return DB::raw('NULL');
		else return $val;
	}
	
	// Clean up uploads path
	protected function file_path($dst) {
		if (strpos($dst, 'http') !== false) return $dst;
		return '/'.str_replace(path('public'), '', $dst);
	}
	
}