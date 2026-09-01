<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDonationItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donation_id');
            $table->unsignedBigInteger('program_id');
            $table->string('program_category', 20)->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index('donation_id');
            $table->index('program_id');
            $table->foreign('donation_id')->references('id')->on('donations')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs');
        });

        // Backfill data donasi lama: 1 donasi -> 1 item (snapshot kategori program).
        $rows = DB::table('donations')
            ->leftJoin('programs', 'donations.program_id', '=', 'programs.id')
            ->select(
                'donations.id as donation_id',
                'donations.program_id',
                'programs.program_category',
                'donations.amount'
            )
            ->get();

        foreach ($rows as $row) {
            DB::table('donation_items')->insert([
                'donation_id'       => $row->donation_id,
                'program_id'        => $row->program_id,
                'program_category'  => $row->program_category,
                'amount'            => $row->amount,
                'created_at'        => DB::raw('NOW()'),
                'updated_at'        => DB::raw('NOW()'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('donation_items');
    }
}
