<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TokenType extends Model
{
    protected $fillable = [
        'slug',
        'name',
    ];

    public function apiServices(): BelongsToMany
    {
        return $this->belongsToMany(ApiService::class, 'api_service_token_types');
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }
}
