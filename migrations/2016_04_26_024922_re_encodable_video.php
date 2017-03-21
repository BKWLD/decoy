<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReEncodableVideo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('encodings', function (Blueprint $table) {
            $table->text('response')->after('message')->nullable();
        });
        Schema::table('encodings', function (Blueprint $table) {
            $table->string('preset')->after('encodable_attribute')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('encodings', function (Blueprint $table) {
            $table->dropColumn('preset');
        });
        Schema::table('encodings', function (Blueprint $table) {
            $table->dropColumn('response');
        });
    }
}
