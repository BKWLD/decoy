<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EncodablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('encodings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('encodable_type');
			$table->integer('encodable_id')->unsigned();
			$table->string('encodable_attribute');
			$table->string('status');
			$table->text('outputs')->nullable();
			$table->text('message')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('encodings');
	}

}
