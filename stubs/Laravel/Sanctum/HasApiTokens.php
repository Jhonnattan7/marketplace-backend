<?php

namespace Laravel\Sanctum;

trait HasApiTokens
{
    public function createToken(string $name, array $abilities = ['*'], $expiresAt = null): object
    {
        return (object) ['plainTextToken' => ''];
    }

    public function currentAccessToken(): mixed
    {
        return null;
    }
}
