<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddSlugToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->after('username');
        });

        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $base = $user->username ? Str::slug($user->username) : Str::slug($user->name);
            $slug = $base;
            $i = 2;
            while (DB::table('users')->where('slug', $slug)->where('id', '!=', $user->id)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            DB::table('users')->where('id', $user->id)->update(['slug' => $slug]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
}
