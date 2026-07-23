<?php

namespace LBHurtado\XDocument\Compatibility;

use JsonSerializable;
use LBHurtado\XDocument\Contract\CanonicalJson;

final readonly class ContractCompatibilityReport implements JsonSerializable
{
    /**
     * @param  list<array{file: string, id: string, sha256: string, status: string}>  $schemas
     * @param  list<array{file: string, name: string, sha256: string, status: string}>  $fixtures
     * @param  array{status: string, schema_ids: list<string>}  $registry
     * @param  array{status: string, path: ?string}  $snapshot
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    public function __construct(
        public string $contract,
        public array $schemas,
        public array $fixtures,
        public array $registry,
        public array $snapshot,
        public array $differences,
    ) {}

    public function isCompatible(): bool
    {
        return $this->differences === [];
    }

    /**
     * @return array{
     *     contract: string,
     *     status: string,
     *     schemas: list<array{file: string, id: string, sha256: string, status: string}>,
     *     fixtures: list<array{file: string, name: string, sha256: string, status: string}>,
     *     registry: array{status: string, schema_ids: list<string>},
     *     snapshot: array{status: string, path: ?string},
     *     differences: list<array{code: string, path: ?string, message: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'contract' => $this->contract,
            'status' => $this->isCompatible() ? 'compatible' : 'incompatible',
            'schemas' => $this->schemas,
            'fixtures' => $this->fixtures,
            'registry' => $this->registry,
            'snapshot' => $this->snapshot,
            'differences' => $this->differences,
        ];
    }

    public function toJson(): string
    {
        return (new CanonicalJson)->encode($this->toArray(), pretty: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
