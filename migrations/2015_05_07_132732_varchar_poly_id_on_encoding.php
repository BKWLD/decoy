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

		// Need to remove indexes for SqlServer to migrate
		Schema::table('encodings', function($table) {
			$table->dropIndex('encodings_encodable_id_encodable_type_encodable_attribute_index');
			$table->dropIndex('encodings_encodable_type_encodable_id_encodable_attribute_index');
			$table->dropIndex('encodings_encodable_attribute_encodable_id_encodable_type_index');
		});

		// SQL SERVER
		if (Config::get('database.default') == 'sqlsrv') {
			DB::statement("ALTER table encodings ALTER COLUMN encodable_id VARCHAR(255)");

		// MySQL	
		} else {
			DB::statement("ALTER TABLE `encodings` CHANGE `encodable_id` `encodable_id` VARCHAR(255)  NOT NULL");
		}

		Schema::table('encodings', function($table) {
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
		// SQL SERVER
		if (Config::get('database.default') == 'sqlsrv') {
			

		// MySQL	
		} else {
			DB::statement("ALTER TABLE `encodings` CHANGE `encodable_id` `encodable_id` INT(10)  NOT NULL");
		}
	}

}
