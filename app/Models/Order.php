<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'g_number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'total_price',
        'discount_percent',
        'warehouse_name',
        'warehouse_type',
        'country_name',
        'oblast',
        'oblast_okrug_name',
        'region_name',
        'income_id',
        'odid',
        'srid',
        'nm_id',
        'subject',
        'category',
        'brand',
        'is_supply',
        'is_realization',
        'spp',
        'finished_price',
        'price_with_disc',
        'is_cancel',
        'cancel_dt',
        'sticker',
    ];

    protected $casts = [
        'date' => 'datetime',
        'last_change_date' => 'datetime',
        'cancel_dt' => 'datetime',
        'is_supply' => 'boolean',
        'is_realization' => 'boolean',
        'is_cancel' => 'boolean',
        'total_price' => 'decimal:2',
        'spp' => 'decimal:2',
        'finished_price' => 'decimal:2',
        'price_with_disc' => 'decimal:2',
        'discount_percent' => 'integer',
        'income_id' => 'integer',
        'nm_id' => 'integer',
    ];
}
