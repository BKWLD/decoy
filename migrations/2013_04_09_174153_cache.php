<?php

use Illuminate\Database\Migrations\Migration;

class Cache extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cache', function($t) {
			$t->string('key')->unique();
			$t->mediumtext('value');
			$t->integer('expiration');
	});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cache');
	}

}
