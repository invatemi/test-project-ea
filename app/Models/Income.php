<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = [
        'account_id',
        'income_id',
        'number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'total_price',
        'date_close',
        'warehouse_name',
        'nm_id',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'last_change_date' => 'datetime',
        'date_close' => 'datetime',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'income_id' => 'integer',
        'nm_id' => 'integer',
    ];
}
