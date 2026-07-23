<?php

namespace LBHurtado\XDocument\Projection\Browser;

use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use stdClass;

final readonly class BrowserSection
{
    /** @param list<BrowserField> $elements */
    public function __construct(
        public string $id,
        public string $sourceIdentifier,
        public string $label,
        public array $elements,
        public stdClass $metadata,
    ) {
        BrowserIdentity::assert($id, 'section');
        if ($sourceIdentifier === '' || $label === '') {
            throw new InvalidBrowserProjection('Browser section source identity and label must be non-empty.');
        }
        $elementIds = array_map(fn (BrowserField $element): string => $element->id, $elements);
        if (count($elementIds) !== count(array_unique($elementIds))) {
            throw new InvalidBrowserProjection("Browser section {$id} contains duplicate element identities.");
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source_identifier' => $this->sourceIdentifier,
            'label' => $this->label,
            'elements' => array_map(fn (BrowserField $element): array => $element->toArray(), $this->elements),
            'metadata' => $this->metadata,
        ];
    }
}
