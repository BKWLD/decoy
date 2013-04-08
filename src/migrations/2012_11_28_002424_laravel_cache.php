<?php

class Decoy_Laravel_Cache {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('laravel_cache', function($table){
			$table->string('key')->primary();
			$table->text('value');
			$table->integer('expiration');
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('laravel_cache');
	}

}