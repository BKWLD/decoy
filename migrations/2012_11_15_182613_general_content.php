<?php

class Decoy_General_Content {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('content', function($table){
			$table->increments('id');
			$table->string('slug')->unique(); // Key was a reserved word
			$table->string('category')->nullable;
			$table->string('type')->default('textarea');
			$table->string('label');
			$table->text('value')->nullable();
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('content');
	}

}