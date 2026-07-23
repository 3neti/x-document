<?php

namespace LBHurtado\XDocument\Contract;

use LBHurtado\XDocument\Exceptions\InvalidDocumentContract;
use stdClass;

final readonly class DocumentCompilationRequest
{
    /**
     * @param  list<string>  $requestedCapabilities
     * @param  array<string, mixed>  $options
     */
    private function __construct(
        public ContractVersion $contractVersion,
        public string $requestIdentifier,
        public string $requestFingerprint,
        public string $correlationIdentifier,
        public string $requestedDriver,
        public array $requestedCapabilities,
        public array $options,
        public ResolvedDocument $document,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $object = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
        if (! $object instanceof stdClass) {
            throw new InvalidDocumentContract('Request payload must be a JSON object.');
        }

        return self::fromObject($object);
    }

    public static function fromObject(stdClass $payload): self
    {
        $capabilities = $payload->requested_capabilities ?? null;
        $options = $payload->options ?? null;
        $document = $payload->document ?? null;
        if (! is_array($capabilities) || ! array_is_list($capabilities) || ! $options instanceof stdClass || ! $document instanceof stdClass) {
            throw new InvalidDocumentContract('Request capabilities, options, or document have an invalid structure.');
        }
        $validatedCapabilities = [];
        foreach ($capabilities as $capability) {
            if (! is_string($capability)) {
                throw new InvalidDocumentContract('Every requested capability must be a string.');
            }
            $validatedCapabilities[] = $capability;
        }

        return new self(
            new ContractVersion(self::string($payload, 'contract_version')),
            self::string($payload, 'request_identifier'),
            self::string($payload, 'request_fingerprint'),
            self::string($payload, 'correlation_identifier'),
            self::string($payload, 'requested_driver'),
            $validatedCapabilities,
            self::options($options),
            ResolvedDocument::fromObject($document),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => $this->contractVersion->value,
            'request_identifier' => $this->requestIdentifier,
            'request_fingerprint' => $this->requestFingerprint,
            'correlation_identifier' => $this->correlationIdentifier,
            'requested_driver' => $this->requestedDriver,
            'requested_capabilities' => $this->requestedCapabilities,
            'options' => $this->options,
            'document' => $this->document->toPayload(),
        ];
    }

    public function toJson(): string
    {
        return (new CanonicalJson)->encode($this->toArray(), pretty: true).PHP_EOL;
    }

    private static function string(stdClass $payload, string $key): string
    {
        $value = $payload->{$key} ?? null;
        if (! is_string($value)) {
            throw new InvalidDocumentContract("Request {$key} must be a string.");
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function options(stdClass $options): array
    {
        $values = [];
        foreach (get_object_vars($options) as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidDocumentContract('Every request option key must be a string.');
            }
            $values[$key] = $value;
        }

        return $values;
    }
}
