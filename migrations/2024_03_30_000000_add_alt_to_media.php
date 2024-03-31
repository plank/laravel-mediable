<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plank\Mediable\Media;

class AddAltToMedia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'media',
            function (Blueprint $table) {
                $table->text('alt');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'media',
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
}
