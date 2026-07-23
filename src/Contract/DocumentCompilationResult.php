<?php

namespace LBHurtado\XDocument\Contract;

final readonly class DocumentCompilationResult
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ContractVersion $contractVersion,
        public string $requestIdentifier,
        public string $documentIdentifier,
        public string $resolutionFingerprint,
        public string $driver,
        public string $status,
        public ?DocumentOutput $output,
        public array $warnings = [],
        public array $capabilities = [],
        public array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => $this->contractVersion->value,
            'request_identifier' => $this->requestIdentifier,
            'document_identifier' => $this->documentIdentifier,
            'resolution_fingerprint' => $this->resolutionFingerprint,
            'driver' => $this->driver,
            'status' => $this->status,
            'output' => $this->output?->toArray(),
            'warnings' => $this->warnings,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata === [] ? (object) [] : $this->metadata,
        ];
    }
}
