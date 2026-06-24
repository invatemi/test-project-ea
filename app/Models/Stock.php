<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'account_id',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'quantity_full',
        'quantity_not_in_orders',
        'is_supply',
        'is_realization',
        'warehouse',
        'warehouse_name',
        'in_way_to_client',
        'in_way_from_client',
        'nm_id',
        'subject',
        'category',
        'brand',
        'days_on_site',
        'sc_code',
        'price',
        'discount',
    ];

    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'datetime',
        'is_supply' => 'boolean',
        'is_realization' => 'boolean',
        'quantity' => 'integer',
        'quantity_full' => 'integer',
        'quantity_not_in_orders' => 'integer',
        'warehouse' => 'integer',
        'in_way_to_client' => 'integer',
        'in_way_from_client' => 'integer',
        'days_on_site' => 'integer',
        'nm_id' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];
}
