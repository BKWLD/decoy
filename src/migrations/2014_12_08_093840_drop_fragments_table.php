<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropFragmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::drop('fragments');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::create('fragments', function($table){
			$table->engine = 'InnoDB';
			
			$table->increments('id');
			$table->string('key')->unique();
			$table->text('value')->nullable();
		});
	}

}
