<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyIdToReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->integer('currency_id')->nullable()->after('total');
            // Optionally, if you have a currencies table, add a foreign key constraint:
            // $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('receipts', function (Blueprint $table) {
            // If using a foreign key, drop it first:
            // $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
}
