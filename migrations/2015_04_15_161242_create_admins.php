<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @link https://scotch.io/tutorials/simple-and-easy-laravel-login-authentication
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('password');
            $table->string('image')->nullable();
            $table->string('role');
            $table->text('permissions')->nullable();
            $table->rememberToken();
            $table->boolean('active');

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
        Schema::drop('admins');
    }
}
