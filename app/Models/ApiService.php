<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiService extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'base_url',
    ];

    public function tokenTypes(): BelongsToMany
    {
        return $this->belongsToMany(TokenType::class, 'api_service_token_types');
    }

    public function accountTokens(): HasMany
    {
        return $this->hasMany(AccountToken::class);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }
}
