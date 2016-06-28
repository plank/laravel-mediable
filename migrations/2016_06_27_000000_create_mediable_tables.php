<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediableTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function(Blueprint $table){
            $table->increments('id')->primary();
            $table->string('disk');
            $table->string('directory');
            $table->string('filename');
            $table->string('extension', 32);
            $table->string('mime', 64);
            $table->string('type', 32);
            $table->integer('size')->unsigned();
            $table->timestamps();

            $table->index(['disk','directory']);
            $table->unique(['disk', 'directory', 'filename', 'extension']);
            $table->index('extension');
            $table->index('mime');
            $table->index('type');
        });

        Schema::create('mediables', function(Blueprint $table){
            $table->increments('media_id');
            $table->intefer('mediable_id')->unsigned();
            $table->string('mediable_type');
            $table->string('association');

            $table->unique(['media_id', 'mediable_id', 'association']);
            $table->index(['mediable_id', 'mediable_type']);
            $table->foreign('media_id')->references('id')->on('media')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mediables');
        Schema::drop('media');
    }
}
