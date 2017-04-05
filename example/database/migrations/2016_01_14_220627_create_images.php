<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->string('imageable_type');
            $table->string('imageable_id');

            $table->string('file');
            $table->string('file_type');
            $table->string('file_size');

            $table->string('name')->nullable(); // Key used to refer to it in code
            $table->string('title')->nullable(); // Alt title
            $table->text('crop_box')->nullable();
            $table->string('focal_point')->nullable();

            $table->integer('width')->unsigned();
            $table->integer('height')->unsigned();
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id']);
            $table->index(['imageable_id', 'imageable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('images');
    }
}
