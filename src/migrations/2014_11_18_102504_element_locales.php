<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ElementLocales extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('elements', function($t) {
			$t->string('locale');
			$t->dropPrimary('elements_key_primary');
			$t->primary(['key', 'locale']);
			$t->index(['locale', 'key']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('elements', function($t) {
			$t->dropColumn('locale');
		});
	}

}
