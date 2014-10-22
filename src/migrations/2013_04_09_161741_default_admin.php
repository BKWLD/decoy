<?php

use Illuminate\Database\Migrations\Migration;

class DefaultAdmin extends Migration {

	/**
	 * Make sure this migration should run
	 * @return boolean
	 */
	private function validate() {
		
		// Make sure we're using Sentry as the admin
		if (Config::get('decoy::core.auth_class') != '\Bkwld\Decoy\Auth\Sentry') return false;
		
		// Do nothing if no creds were defined in the config file.  It' assumed that the 
		// site doesn't want to use Sentry in this case
		if (!Config::get('decoy::core.default_login') || !Config::get('decoy::core.default_password')) {
			echo 'There were no creds defined in the configuration file.'.PHP_EOL;
			return false;
		}
		
		// All good
		return true;
		
	}

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		
		// Should this run?
		if (!$this->validate()) return;
		
		// Make sure the user doesn't already exist
		try {
			if (Sentry::getUserProvider()->findByLogin(Config::get('decoy::core.default_login'))) {
				echo 'The default admin user already exists.'.PHP_EOL;
				return;
			}
		} catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {}
		
		// Create the login user
		$user = Sentry::getUserProvider()->create(array(
			'email'    => Config::get('decoy::core.default_login'),
			'password' => Config::get('decoy::core.default_password'),
			'first_name' => 'Default',
			'last_name'  => 'Admin',
			'activated' => true,
		));
		
		// Assign to a group called admins
		$group = Sentry::getGroupProvider()->create(array(
			'name'        => 'admins',
			'permissions' => array(
				'admin' => 1,
			),
		));
		$user->addGroup($group);
		
		// Assign to a group called developers
		$group = Sentry::getGroupProvider()->create(array(
			'name'        => 'developers',
			'permissions' => array(
				'developer' => 1,
			),
		));
		$user->addGroup($group);

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		
		// Should this run?
		if (!$this->validate()) return;
		
		// Remove user
		$user = Sentry::getUserProvider()->findByLogin(Config::get('decoy::core.default_login'));
		if (!$user->delete()) {
			echo 'There was an error deleting the user'.PHP_EOL;
			return;
		}
		
		// Remove the admins group
		$group = Sentry::getGroupProvider()->findByName('admins');
		if (!$group->delete()) {
			echo 'There was an error deleting the group'.PHP_EOL;
			return;
		}
		
	}

}