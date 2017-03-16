<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedirectRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_rules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->string('from')->index();
            $table->string('to');
            $table->string('code');
            $table->string('label')->nullable();

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
        Schema::drop('redirect_rules');
    }
}
