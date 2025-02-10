<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'receipts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'receipt_number',
        'total',
        'store',
        'currency_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total' => 'float',
        'date'  => 'date',
    ];

    /**
     * Get the expenses associated with the receipt.
     *
     * This assumes that the related Expense model is using the 'receipt_id' foreign key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenses()
    {
        return $this->hasMany(IncomeExpense::class, 'receipt_id');
    }

    /**
     * Get the currency for this receipt.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
