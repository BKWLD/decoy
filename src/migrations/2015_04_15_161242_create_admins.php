<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		// Adapted from:
		// https://scotch.io/tutorials/simple-and-easy-laravel-login-authentication
		Schema::create('admins', function(Blueprint $table) {
			$table->increments('id');

			$table->string('first_name');
			$table->string('last_name');
			$table->string('email');
			$table->string('password');
			$table->string('remember_token')->nullable();
			$table->string('role');
			$table->boolean('active');

			$table->timestamps();
		});

		// From: 
		// /vendor/laravel/framework/src/Illuminate/Auth/Console/stubs/reminders.stub
		Schema::create('password_reminders', function(Blueprint $table) {
			$table->string('email')->index();
			$table->string('token')->index();
			$table->timestamp('created_at');
		});

		// Check if Sentry is installed
		if (DB::table('migrations')
			->where('migration', 'LIKE', '%cartalyst_sentry%')
			->count()) $this->migrateSentry();
		else $this->makeDefaultAdmin();
		
	}

	/**
	 * Migrate Sentry
	 */
	public function migrateSentry() {

		// Migrate Sentry users using stright DB queries
		foreach(DB::table('users')->get() as $user) {

			// Get the group for the user.  Assuming there is only one group
			// per user
			$group = DB::table('groups')
				->join('users_groups', 'group_id', '=', 'groups.id')
				->where('user_id', '=', $user->id)
				->pluck('name');


			// Create the new user
			DB::table('admins')->insert([
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
				'email' => $user->email,
				'password' => $user->password,
				'role' => $group,
				'active' => $user->activated,
				'created_at' => $user->created_at,
				'updated_at' => DB::raw('NOW()'),
			]);
		}

		// Drop the Sentry tables
		Schema::drop('groups');
		Schema::drop('throttle');
		Schema::drop('users');
		Schema::drop('users_groups');

	}

	/**
	 * Make a default admin
	 */
	public function makeDefaultAdmin() {
		// TODO
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
