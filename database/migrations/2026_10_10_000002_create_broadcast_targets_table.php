<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadcast_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('provider', 10)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('rendered_message')->nullable();
            $table->text('response')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['broadcast_id', 'contact_id']);
            $table->index(['broadcast_id', 'status']);
            $table->foreign('broadcast_id')->references('id')->on('broadcasts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('broadcast_targets');
    }
}
