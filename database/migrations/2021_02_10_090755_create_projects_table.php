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
            $table->decimal('ideal_prog_hrs', 8, 2)->nullable();
            $table->decimal('ideal_valid_hrs', 8, 2)->nullable();
            $table->decimal('template_percent', 8, 2)->nullable();
            $table->decimal('program_percent', 8, 2)->nullable();
            $table->decimal('validation_percent', 8, 2)->nullable();
            $table->date('program_date')->nullable();
            $table->date('validation_date')->nullable();
            $table->decimal('program_hrs', 8, 2)->nullable();
            $table->decimal('validate_hrs', 8, 2)->nullable();
            $table->date('accepted_date')->nullable();
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
