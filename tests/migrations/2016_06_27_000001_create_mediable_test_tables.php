<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Plank\Mediable\Media;

/**
 * Create table for mock mediable model
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
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection($this->getConnectionName())->table('media', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::create('prefixed_mediables', function (Blueprint $table) {
            $table->foreignIdFor(Media::class)->constrained((new Media())->getTable())->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('tag')->index();
            $table->unsignedBigInteger('order')->index();
            $table->primary(['media_id', 'mediable_type', 'mediable_id', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
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
