<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFollowupFieldsToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('followup_count')->default(0)->after('notes');
            $table->timestamp('last_messaged_at')->nullable()->after('followup_count');
            $table->boolean('wa_valid')->nullable()->after('last_messaged_at');

            $table->index('followup_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['followup_count']);
            $table->dropColumn(['followup_count', 'last_messaged_at', 'wa_valid']);
        });
    }
}
