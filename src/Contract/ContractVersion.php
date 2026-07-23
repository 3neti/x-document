<?php

namespace LBHurtado\XDocument\Contract;

use LBHurtado\XDocument\Exceptions\UnsupportedContractVersion;

final readonly class ContractVersion
{
    public const Version1 = '1.0';

    public function __construct(public string $value)
    {
        if ($value !== self::Version1) {
            throw new UnsupportedContractVersion("Unsupported x-document contract version: {$value}.");
        }
    }
}
