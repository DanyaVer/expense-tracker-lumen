<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Add two new columns to store conversion rates
        Schema::table('currencies', function (Blueprint $table) {
            // Use decimal(15,6) or whatever precision you need
            $table->decimal('conversion_from_base', 15, 6)->nullable()->after('country');
            $table->decimal('conversion_to_base', 15, 6)->nullable()->after('conversion_from_base');
        });

        // Update the "country" field so commas become ", "
        DB::table('currencies')->update([
            // REPLACE(column, 'findThis', 'replaceWith')
            'country' => DB::raw("REPLACE(country, ',', ', ')")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Remove the new columns
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('conversion_from_base');
            $table->dropColumn('conversion_to_base');
        });

        // Optionally revert "country" back to no space after comma
        DB::table('currencies')->update([
            'country' => DB::raw("REPLACE(country, ', ', ',')")
        ]);
    }
}
