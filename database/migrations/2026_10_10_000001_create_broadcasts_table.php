<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->text('message_template');
            $table->string('media_path')->nullable();
            $table->string('media_type', 20)->nullable();
            $table->unsignedBigInteger('target_branch_id')->nullable();
            $table->unsignedBigInteger('target_agen_id')->nullable();
            $table->json('target_statuses')->nullable();
            $table->json('target_followups')->nullable();
            $table->string('mechanism', 10)->default('auto');
            $table->timestamp('stop_at')->nullable();
            $table->unsignedInteger('limit_count')->nullable();
            $table->string('schedule_type', 10)->default('now');
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('interval_min')->default(20);
            $table->unsignedInteger('interval_max')->default(60);
            $table->string('provider', 10)->nullable();
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->timestamp('last_tick_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('broadcasts');
    }
}
