<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AccountToken extends Model
{
    protected $fillable = [
        'account_id',
        'api_service_id',
        'token_type_id',
        'credentials',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function apiService(): BelongsTo
    {
        return $this->belongsTo(ApiService::class);
    }

    public function tokenType(): BelongsTo
    {
        return $this->belongsTo(TokenType::class);
    }

    /** @param  array<string, string>  $credentials */
    public function setCredentialsArray(array $credentials): void
    {
        $this->credentials = Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, string> */
    public function getCredentialsArray(): array
    {
        $decoded = json_decode(Crypt::decryptString($this->credentials), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
