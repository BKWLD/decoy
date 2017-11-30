<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OptionalChangesAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('changes', function (Blueprint $table) {
            $table->integer('admin_id')->unsigned()->nullable()->change();
        });
        Schema::table('changes', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
        });
        Schema::table('changes', function (Blueprint $table) {
            $table->foreign('admin_id')
                ->references('id')->on('admins')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('changes', function (Blueprint $table) {
            $table->integer('admin_id')->unsigned()->change();
        });
        Schema::table('changes', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
        });
        Schema::table('changes', function (Blueprint $table) {
            $table->foreign('admin_id')
                ->references('id')->on('admins')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

    }
}
