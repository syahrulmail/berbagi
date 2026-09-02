<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicProgressAndSocialToProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('terkumpul_publik', 15, 2)->default(0)->after('goal_amount');
            $table->unsignedInteger('suka')->default(0)->after('show_goal');
            $table->unsignedInteger('klik')->default(0)->after('suka');
            $table->unsignedInteger('suka_riil')->default(0)->after('klik');
            $table->unsignedInteger('klik_riil')->default(0)->after('suka_riil');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['terkumpul_publik', 'suka', 'klik', 'suka_riil', 'klik_riil']);
        });
    }
}
