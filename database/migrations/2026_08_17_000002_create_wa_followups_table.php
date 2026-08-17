<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaFollowupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wa_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agen_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source', 20)->default('program');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['agen_id', 'created_at']);
            $table->index(['program_id', 'created_at']);
            $table->foreign('agen_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wa_followups');
    }
}
