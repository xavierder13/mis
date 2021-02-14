<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no');
            $table->string('report_title');
            $table->integer('programmer_id');
            $table->integer('validator_id')->nullable();
            $table->date('date_receive')->nullable();
            $table->date('date_approve')->nullable();
            $table->string('type');
            $table->integer('department_id');
            $table->string('ideal')->nullable();
            $table->string('status');
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
        Schema::dropIfExists('projects');
    }

    
}
