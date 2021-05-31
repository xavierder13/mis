<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcceptanceOverviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acceptance_overviews', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->string('for_delete')->nullable();
            $table->string('intended_users')->nullable();
            $table->string('location1')->nullable();
            $table->string('location2')->nullable();
            $table->text('overview', 65,535);
            $table->text('validator_note', 65,535)->nullable();
            $table->text('manager_note', 65,535)->nullable();
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
        Schema::dropIfExists('acceptance_overviews');
    }
}
