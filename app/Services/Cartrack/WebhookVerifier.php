<?php

namespace App\Services\Cartrack;

use App\Services\Cartrack\Exceptions\InvalidSignatureException;

class WebhookVerifier
{
    public static function verify(string $secret, string $rawBody, string $receivedSignature): bool
    {
        if ($secret === '' || $receivedSignature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, trim($receivedSignature));
    }

    public static function assertValid(string $secret, string $rawBody, ?string $receivedSignature): void
    {
        if (!$receivedSignature || !self::verify($secret, $rawBody, $receivedSignature)) {
            throw new InvalidSignatureException('Assinatura do webhook inválida.', 400);
        }
    }
}
