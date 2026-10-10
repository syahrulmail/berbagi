<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarmingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warming_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->string('from_phone', 30)->nullable();
            $table->string('to_phone', 30);
            $table->text('message')->nullable();
            $table->string('provider', 10)->nullable();
            $table->string('direction', 3)->default('out');
            $table->text('response')->nullable();
            $table->timestamps();

            $table->index(['from_user_id', 'created_at']);
            $table->index(['to_user_id', 'created_at']);
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warming_logs');
    }
}
