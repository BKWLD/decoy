<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		// Adapted from:
		// https://scotch.io/tutorials/simple-and-easy-laravel-login-authentication
		Schema::create('admins', function(Blueprint $table) {
			$table->engine = 'InnoDB';
			$table->increments('id');

			$table->string('first_name');
			$table->string('last_name');
			$table->string('email')->index();
			$table->string('password');
			$table->string('image')->nullable();
			$table->string('role');
			$table->text('permissions')->nullable();
			$table->rememberToken();
			$table->boolean('active');
			
			$table->timestamps();
		});

		// Make a default admin
		$this->makeDefaultAdmin();
	}

	/**
	 * Make a default admin.  The password will be hashed automatically by an
	 * onSaving callback on the model.
	 */
	public function makeDefaultAdmin() {
		Bkwld\Decoy\Models\Admin::create([
			'first_name' => 'Default',
			'last_name' => 'Admin',
			'email' => Config::get('decoy.core.default_login'),
			'password' => Config::get('decoy.core.default_password'),
			'role' => 'super',
			'active' => 1,
		]);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('admins');
	}

}
