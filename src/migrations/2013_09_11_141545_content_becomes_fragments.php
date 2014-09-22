<?php

use Illuminate\Database\Migrations\Migration;

class ContentBecomesFragments extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::drop('content');
		Schema::create('fragments', function($table){
			$table->engine = 'InnoDB';
			
			$table->increments('id');
			$table->string('key')->unique();
			$table->text('value')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
		Schema::create('content', function($table){
			$table->increments('id');
			$table->string('slug')->unique(); // Key was a reserved word
			$table->string('category')->nullable;
			$table->string('type')->default('textarea');
			$table->string('label');
			$table->text('value')->nullable();
		});
		Schema::drop('fragments');
	}

}