<?php

namespace LBHurtado\XDocument\Contract;

use LBHurtado\XDocument\Exceptions\InvalidDocumentContract;
use stdClass;

final readonly class ResolvedDocument
{
    private function __construct(
        public string $identifier,
        public string $definitionIdentifier,
        public int|string $definitionRevision,
        public string $resolutionFingerprint,
        public stdClass $payload,
    ) {}

    public static function fromObject(stdClass $payload): self
    {
        $identifier = self::string($payload, 'identifier');
        $definitionIdentifier = self::string($payload, 'definition_identifier');
        $definitionRevision = $payload->definition_revision ?? null;
        $resolutionFingerprint = self::string($payload, 'resolution_fingerprint');
        if (! is_int($definitionRevision) && ! is_string($definitionRevision)) {
            throw new InvalidDocumentContract('Document definition revision must be an integer or string.');
        }

        return new self($identifier, $definitionIdentifier, $definitionRevision, $resolutionFingerprint, $payload);
    }

    public function toPayload(): stdClass
    {
        return $this->payload;
    }

    private static function string(stdClass $payload, string $key): string
    {
        $value = $payload->{$key} ?? null;
        if (! is_string($value)) {
            throw new InvalidDocumentContract("Document {$key} must be a string.");
        }

        return $value;
    }
}
