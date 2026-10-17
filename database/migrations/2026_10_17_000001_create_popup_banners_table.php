<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePopupBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popup_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->string('schedule_type', 20)->default('always');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('days')->nullable();
            $table->json('pages')->nullable();
            $table->string('agent_scope', 20)->default('all');
            $table->json('agent_ids')->nullable();
            $table->string('trigger_type', 20)->default('welcome');
            $table->unsignedInteger('delay_minutes')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('popup_banners');
    }
}
