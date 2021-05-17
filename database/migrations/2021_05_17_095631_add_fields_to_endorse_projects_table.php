<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEndorseProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('endorse_projects', function (Blueprint $table) {
            $table->date('date_receive')->nullable();
            $table->date('program_date')->nullable();
            $table->date('validation_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('endorse_projects', function (Blueprint $table) {
            $table->dropColumn('date_receive');
            $table->dropColumn('program_date');
            $table->dropColumn('validation_date');
        });
    }
}
