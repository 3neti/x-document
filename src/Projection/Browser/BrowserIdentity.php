<?php

namespace LBHurtado\XDocument\Projection\Browser;

use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;

final readonly class BrowserIdentity
{
    public static function assert(string $identifier, string $kind): void
    {
        if (preg_match('/^'.preg_quote($kind, '/').':[A-Za-z0-9._@:-]+$/', $identifier) !== 1) {
            throw new InvalidBrowserProjection("Browser {$kind} identity is unsafe: {$identifier}");
        }
    }

    public static function token(string $value): string
    {
        $token = preg_replace('/[^A-Za-z0-9._@-]+/', '_', $value);
        if (! is_string($token) || $token === '') {
            throw new InvalidBrowserProjection('Browser projection source identity cannot be normalized safely.');
        }

        return $token;
    }
}
