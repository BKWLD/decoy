<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VarcharPolyIdOnEncoding extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE `encodings` CHANGE `encodable_id` `encodable_id` VARCHAR(255)  NOT NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE `encodings` CHANGE `encodable_id` `encodable_id` INT(10)  NOT NULL");
	}

}
