<?php

class Decoy_Default_Admin {
	
	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		
		// Do nothing if no creds were defined in the config file.  It' assumed that the 
		// site doesn't want to use Sentry in this case
		if (!Config::get('decoy::decoy.default_login') || !Config::get('decoy::decoy.default_password')) {
			echo 'There were no creds defined in the configuration file.'.PHP_EOL;
			return;
		}

		// These were both required to be able to mess with Sentry
		Session::load();
		Bundle::start('sentry');
		
		// Make sure the user doesn't already exist
		if (Sentry::user_exists(Config::get('decoy::decoy.default_login'))) {
			echo 'The default user already exists.'.PHP_EOL;
			return;
		}
		
		// Create the login user
		Sentry::user()->create(array(
			'email'    => Config::get('decoy::decoy.default_login'),
			'password' => Config::get('decoy::decoy.default_password'),
			'metadata' => array(
				'first_name' => 'Default',
				'last_name'  => 'Admin',
			)));
		
		// Assign to a group called admins
		Sentry::group()->create(array(
        'name'  => 'admins'
    ));
    Sentry::user(Config::get('decoy::decoy.default_login'))->add_to_group('admins');
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		
		// Do nothing if no creds were defined in the config file.  It' assumed that the 
		// site doesn't want to use Sentry in this case
		if (!Config::get('decoy::decoy.default_login') || !Config::get('decoy::decoy.default_password')) return;
		
		// Remove the admins
		Session::load();
		Bundle::start('sentry');
		Sentry::user(Config::get('decoy::decoy.default_login'))->delete();
		Sentry::group('admins')->delete();
	}
}