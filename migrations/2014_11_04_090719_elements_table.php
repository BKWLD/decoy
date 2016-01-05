<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ElementsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('elements', function($table){
			$table->engine = 'InnoDB';

			$table->string('key');
			$table->string('type');
			$table->mediumtext('value')->nullable();
			$table->string('locale');

			$table->primary(['key', 'locale']);
			$table->index(['locale', 'key']);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('elements');
	}

}
