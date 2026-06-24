<?php

namespace Tests\Unit\Models;

use App\Models\AccountToken;
use Tests\TestCase;

class AccountTokenTest extends TestCase
{
    public function test_credentials_are_encrypted_at_rest_and_decrypt_correctly(): void
    {
        $token = new AccountToken;
        $token->setCredentialsArray(['key' => 'secret-api-key-value']);

        $this->assertNotSame('secret-api-key-value', $token->credentials);
        $this->assertStringNotContainsString('secret-api-key-value', $token->credentials);
        $this->assertSame(['key' => 'secret-api-key-value'], $token->getCredentialsArray());
    }

    public function test_bearer_credentials_roundtrip(): void
    {
        $token = new AccountToken;
        $token->setCredentialsArray(['token' => 'bearer-token-xyz']);

        $this->assertSame(['token' => 'bearer-token-xyz'], $token->getCredentialsArray());
    }
}
