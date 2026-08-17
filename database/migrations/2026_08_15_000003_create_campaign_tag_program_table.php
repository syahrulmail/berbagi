<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignTagProgramTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_tag_program', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_tag_id');
            $table->unsignedBigInteger('program_id');
            $table->timestamps();

            $table->unique(['campaign_tag_id', 'program_id']);
            $table->foreign('campaign_tag_id')->references('id')->on('campaign_tags')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_tag_program');
    }
}
