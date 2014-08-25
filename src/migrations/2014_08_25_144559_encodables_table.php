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
			$table->string('status')->index();
			$table->string('job_id')->nullable()->index();
			$table->text('outputs')->nullable();
			$table->text('message')->nullable();
			$table->timestamps();

			$table->index(array('encodable_id', 'encodable_type', 'encodable_attribute'));
			$table->index(array('encodable_type', 'encodable_id', 'encodable_attribute'));
			$table->index(array('encodable_attribute', 'encodable_id', 'encodable_type'));
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
