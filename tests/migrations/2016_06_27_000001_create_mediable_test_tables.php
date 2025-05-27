<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
<<<<<<< HEAD
 * Create a table for a mock mediable model.
=======
 * Create table for mock mediable model
>>>>>>> parent of 288a94f (Apply fixes from StyleCI)
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sample_mediables', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($this->getConnectionName())->table('media', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::create('prefixed_mediables', function (Blueprint $table) {
            $table->integer('media_id')->unsigned();
            $table->string('mediable_type');
            $table->integer('mediable_id')->unsigned();
            $table->string('tag');
            $table->integer('order')->unsigned();

            $table->primary(['media_id', 'mediable_type', 'mediable_id', 'tag']);
            $table->index(['mediable_id', 'mediable_type']);
            $table->index('tag');
            $table->index('order');
            $table->foreign('media_id')->references('id')->on('media')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::connection($this->getConnectionName())->table('media', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::dropIfExists('sample_mediables');
        Schema::dropIfExists('prefixed_mediables');
    }

    /**
     * Get the connection name that is used by the package.
     *
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return config('mediable.connection_name', $this->getConnection());
    }
};
