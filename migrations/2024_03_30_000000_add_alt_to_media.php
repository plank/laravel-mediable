<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plank\Mediable\Media;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        Schema::whenTableDoesntHaveColumn(
            'media',
            'alt',
            function (Blueprint $table) {
                $table->text('alt')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::whenTableHasColumn(
            'media',
            'alt',
            function (Blueprint $table) {
                $table->dropColumn('alt');
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return config('mediable.connection_name', parent::getConnection());
    }
};
