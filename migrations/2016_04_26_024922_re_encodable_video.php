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
            $table->string('preset')->after('encodable_attribute');
            $table->text('response')->after('message')->nullable();
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
            $table->dropColumn('response');
        });
    }
}
