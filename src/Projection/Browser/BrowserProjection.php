<?php

namespace LBHurtado\XDocument\Projection\Browser;

use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use stdClass;

final readonly class BrowserProjection
{
    public const Format = 'browser/1.0';

    public const MediaType = 'application/vnd.3neti.x-document.browser+json';

    /**
     * @param  list<string>  $audience
     * @param  list<BrowserSection>  $sections
     * @param  list<array<string, mixed>>  $actions
     * @param  list<array<string, mixed>>  $attachments
     * @param  list<array<string, mixed>>  $evidence
     */
    public function __construct(
        public string $identifier,
        public stdClass $sourceDocument,
        public string $title,
        public array $audience,
        public stdClass $subject,
        public stdClass $primaryArtifact,
        public array $sections,
        public array $actions,
        public array $attachments,
        public array $evidence,
        public stdClass $metadata,
    ) {
        if (preg_match('/^browser:[a-f0-9]{64}$/', $identifier) !== 1 || $title === '') {
            throw new InvalidBrowserProjection('Browser projection identity or title is invalid.');
        }
        $this->assertUnique($sections, fn (BrowserSection $section): string => $section->id, 'section');
        $this->assertUnique($actions, fn (array $action): string => $this->arrayIdentity($action, 'action'), 'action');
        $this->assertUnique($attachments, fn (array $attachment): string => $this->arrayIdentity($attachment, 'attachment'), 'attachment');
        $this->assertUnique($evidence, fn (array $item): string => $this->arrayIdentity($item, 'evidence'), 'evidence');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'projection_format' => self::Format,
            'identifier' => $this->identifier,
            'read_only' => true,
            'source_document' => $this->sourceDocument,
            'title' => $this->title,
            'audience' => $this->audience,
            'subject' => $this->subject,
            'primary_artifact' => $this->primaryArtifact,
            'sections' => array_map(fn (BrowserSection $section): array => $section->toArray(), $this->sections),
            'actions' => $this->actions,
            'attachments' => $this->attachments,
            'evidence' => $this->evidence,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @template TValue
     *
     * @param  list<TValue>  $values
     * @param  callable(TValue): string  $identifier
     */
    private function assertUnique(array $values, callable $identifier, string $kind): void
    {
        $identifiers = array_map($identifier, $values);
        foreach ($identifiers as $value) {
            BrowserIdentity::assert($value, $kind);
        }
        if (count($identifiers) !== count(array_unique($identifiers))) {
            throw new InvalidBrowserProjection("Browser projection contains duplicate {$kind} identities.");
        }
    }

    /** @param array<string, mixed> $value */
    private function arrayIdentity(array $value, string $kind): string
    {
        $identifier = $value['id'] ?? null;
        if (! is_string($identifier)) {
            throw new InvalidBrowserProjection("Browser {$kind} identity is missing.");
        }

        return $identifier;
    }
}
