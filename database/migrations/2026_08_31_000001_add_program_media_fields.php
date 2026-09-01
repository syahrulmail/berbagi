<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProgramMediaFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('program_category', 20)->nullable()->after('slug');
            $table->string('video_url', 500)->nullable()->after('image');
            $table->json('media')->nullable()->after('video_url');
            $table->boolean('show_goal')->default(true)->after('media');
        });

        // Setiap program wajib memiliki 1 Kategori Program.
        // Backfill data lama dengan kategori default (Quran/WAP).
        DB::table('programs')->whereNull('program_category')->update(['program_category' => 'WAP']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['program_category', 'video_url', 'media', 'show_goal']);
        });
    }
}
