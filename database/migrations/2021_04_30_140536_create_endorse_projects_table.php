<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEndorseProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('endorse_projects', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->integer('programmer_id');
            $table->date('endorse_date')->nullable();
            $table->boolean('endorsed')->nullable();
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
        Schema::dropIfExists('endorse_projects');
    }
}
