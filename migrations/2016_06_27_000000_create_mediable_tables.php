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
            $table->string('basename');
            $table->integer('size')->unsigned();
            $table->string('mime');
            $table->string('type', 63);
            $table->timestamps();

            $table->index(['disk','directory']);
            $table->index('mime');
            $table->unique(['disk', 'directory', 'basename']);
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
