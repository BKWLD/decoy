<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemainingFieldTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->time('time')->after('date')->nullable();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->datetime('datetime')->after('time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('datetime');
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('time');
        });
    }
}
