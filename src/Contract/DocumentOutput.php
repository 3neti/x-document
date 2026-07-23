<?php

namespace LBHurtado\XDocument\Contract;

use JsonException;
use LBHurtado\XDocument\Exceptions\InvalidDocumentOutput;

final readonly class DocumentOutput
{
    /** @param array<string, mixed> $metadata */
    private function __construct(
        public string $mediaType,
        public ?string $filename = null,
        public ?string $checksum = null,
        public ?int $byteLength = null,
        public ?string $inlineContent = null,
        public ?string $contentReference = null,
        public array $metadata = [],
    ) {}

    /** @param array<string, mixed> $metadata */
    public static function inline(
        string $mediaType,
        string $content,
        ?string $filename = null,
        array $metadata = [],
    ): self {
        self::assertMediaType($mediaType);
        self::assertFilename($filename);
        self::assertJsonCompatible($metadata);

        return new self(
            mediaType: $mediaType,
            filename: $filename,
            checksum: 'sha256:'.hash('sha256', $content),
            byteLength: strlen($content),
            inlineContent: $content,
            metadata: $metadata,
        );
    }

    /** @param array<string, mixed> $metadata */
    public static function referenced(
        string $mediaType,
        string $contentReference,
        ?string $filename = null,
        ?string $checksum = null,
        ?int $byteLength = null,
        array $metadata = [],
    ): self {
        self::assertMediaType($mediaType);
        self::assertFilename($filename);
        self::assertSourceReference($contentReference);
        self::assertChecksum($checksum);
        if ($byteLength !== null && $byteLength < 0) {
            throw new InvalidDocumentOutput('Document output byte length cannot be negative.');
        }
        self::assertJsonCompatible($metadata);

        return new self(
            mediaType: $mediaType,
            filename: $filename,
            checksum: $checksum,
            byteLength: $byteLength,
            contentReference: $contentReference,
            metadata: $metadata,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'media_type' => $this->mediaType,
            'filename' => $this->filename,
            'checksum' => $this->checksum,
            'byte_length' => $this->byteLength,
            'inline_content' => $this->inlineContent,
            'content_reference' => $this->contentReference,
            'metadata' => $this->metadata === [] ? (object) [] : $this->metadata,
        ];
    }

    private static function assertMediaType(string $mediaType): void
    {
        if (preg_match('/^[^\s\/]+\/[^\s\/]+$/', $mediaType) !== 1) {
            throw new InvalidDocumentOutput('Document output media type is invalid.');
        }
    }

    private static function assertFilename(?string $filename): void
    {
        if ($filename !== null && ($filename === '' || str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, "\0"))) {
            throw new InvalidDocumentOutput('Document output filename must be a safe basename.');
        }
    }

    private static function assertSourceReference(string $sourceReference): void
    {
        if ($sourceReference === '' || preg_match('/^(?!\/|\\\\|[A-Za-z]:[\\\\\/]|[Ff][Ii][Ll][Ee]:).+$/', $sourceReference) !== 1) {
            throw new InvalidDocumentOutput('Document output content reference is unsafe.');
        }
    }

    private static function assertChecksum(?string $checksum): void
    {
        if ($checksum !== null && preg_match('/^(?:sha256:)?[a-f0-9]{64}$/', $checksum) !== 1) {
            throw new InvalidDocumentOutput('Document output checksum must be a SHA-256 digest.');
        }
    }

    /** @param array<string, mixed> $metadata */
    private static function assertJsonCompatible(array $metadata): void
    {
        try {
            (new CanonicalJson)->encode($metadata);
        } catch (JsonException $exception) {
            throw new InvalidDocumentOutput('Document output metadata must be JSON-compatible.', previous: $exception);
        }
    }
}
