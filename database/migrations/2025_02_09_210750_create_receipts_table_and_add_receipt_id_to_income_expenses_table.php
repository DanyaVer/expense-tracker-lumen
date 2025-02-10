<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTableAndAddReceiptIdToIncomeExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the receipts table
        Schema::create('receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->string('receipt_number', 255);
            $table->float('total', 10, 2);
            $table->string('store', 255);
            // Additional user reference fields
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        // Add a nullable receipt_id column to the income_expenses table
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_id')->nullable()->after('id');
            
            // Foreign key constraint; on delete, set the receipt_id to null.
            $table->foreign('receipt_id')
                  ->references('id')
                  ->on('receipts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the foreign key and column from income_expenses first.
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropForeign(['receipt_id']);
            $table->dropColumn('receipt_id');
        });

        // Then, drop the receipts table.
        Schema::dropIfExists('receipts');
    }
}
