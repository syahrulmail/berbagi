<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentDateToDonationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('donations', 'payment_date')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->date('payment_date')->nullable()->after('donation_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('donations', 'payment_date')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->dropColumn('payment_date');
            });
        }
    }
}
