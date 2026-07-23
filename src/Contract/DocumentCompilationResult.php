<?php

namespace LBHurtado\XDocument\Contract;

use JsonException;

final readonly class DocumentCompilationResult
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        public ContractVersion $contractVersion,
        public string $requestIdentifier,
        public string $documentIdentifier,
        public string $resolutionFingerprint,
        public string $driver,
        public DocumentCompilationStatus $status,
        public ?DocumentOutput $output,
        public array $warnings = [],
        public array $capabilities = [],
        public array $metadata = [],
    ) {}

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    public static function succeeded(
        DocumentCompilationRequest $request,
        string $driver,
        DocumentOutput $output,
        array $warnings = [],
        array $capabilities = [],
        array $metadata = [],
    ): self {
        return self::make($request, $driver, DocumentCompilationStatus::Succeeded, $output, $warnings, $capabilities, $metadata);
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    public static function unsupported(
        DocumentCompilationRequest $request,
        string $driver,
        array $warnings,
        array $capabilities = [],
        array $metadata = [],
    ): self {
        return self::make($request, $driver, DocumentCompilationStatus::Unsupported, null, $warnings, $capabilities, $metadata);
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    public static function failed(
        DocumentCompilationRequest $request,
        string $driver,
        array $warnings,
        array $capabilities = [],
        array $metadata = [],
    ): self {
        return self::make($request, $driver, DocumentCompilationStatus::Failed, null, $warnings, $capabilities, $metadata);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => $this->contractVersion->value,
            'request_identifier' => $this->requestIdentifier,
            'document_identifier' => $this->documentIdentifier,
            'resolution_fingerprint' => $this->resolutionFingerprint,
            'driver' => $this->driver,
            'status' => $this->status->value,
            'output' => $this->output?->toArray(),
            'warnings' => $this->warnings,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata === [] ? (object) [] : $this->metadata,
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $metadata
     */
    private static function make(
        DocumentCompilationRequest $request,
        string $driver,
        DocumentCompilationStatus $status,
        ?DocumentOutput $output,
        array $warnings,
        array $capabilities,
        array $metadata,
    ): self {
        if ($driver === '') {
            throw new \InvalidArgumentException('Document compilation driver must be non-empty.');
        }
        if ($status === DocumentCompilationStatus::Succeeded && $output === null) {
            throw new \LogicException('A succeeded document compilation requires output.');
        }
        if ($status !== DocumentCompilationStatus::Succeeded && $output !== null) {
            throw new \LogicException('Unsupported and failed document compilations cannot contain output.');
        }
        self::assertStringList($warnings, 'warning');
        self::assertStringList($capabilities, 'capability');
        try {
            (new CanonicalJson)->encode($metadata);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Document compilation result metadata must be JSON-compatible.', previous: $exception);
        }
        $capabilities = array_values(array_unique($capabilities));
        sort($capabilities, SORT_STRING);

        return new self(
            contractVersion: $request->contractVersion,
            requestIdentifier: $request->requestIdentifier,
            documentIdentifier: $request->document->identifier,
            resolutionFingerprint: $request->document->resolutionFingerprint,
            driver: $driver,
            status: $status,
            output: $output,
            warnings: $warnings,
            capabilities: $capabilities,
            metadata: $metadata,
        );
    }

    /** @param array<mixed> $values */
    private static function assertStringList(array $values, string $kind): void
    {
        if (! array_is_list($values)) {
            throw new \InvalidArgumentException("Document compilation {$kind}s must be a list.");
        }
        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                throw new \InvalidArgumentException("Every document compilation {$kind} must be a non-empty string.");
            }
        }
    }
}
