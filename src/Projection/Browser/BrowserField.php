<?php

namespace LBHurtado\XDocument\Projection\Browser;

use JsonException;
use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use stdClass;

final readonly class BrowserField
{
    /**
     * @param  list<string>  $evidenceIds
     */
    public function __construct(
        public string $id,
        public string $sourceIdentifier,
        public string $label,
        public stdClass $canonicalValue,
        public ?string $displayValue,
        public array $evidenceIds,
        public stdClass $metadata,
    ) {
        BrowserIdentity::assert($id, 'field');
        if ($sourceIdentifier === '' || $label === '') {
            throw new InvalidBrowserProjection('Browser field source identity and label must be non-empty.');
        }
        if (count($evidenceIds) !== count(array_unique($evidenceIds))) {
            throw new InvalidBrowserProjection("Browser field {$id} contains duplicate evidence identities.");
        }
        foreach ($evidenceIds as $evidenceId) {
            BrowserIdentity::assert($evidenceId, 'evidence');
        }
        try {
            (new CanonicalJson)->encode([$canonicalValue, $metadata]);
        } catch (JsonException $exception) {
            throw new InvalidBrowserProjection("Browser field {$id} is not JSON-compatible.", previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'field',
            'id' => $this->id,
            'source_identifier' => $this->sourceIdentifier,
            'label' => $this->label,
            'canonical_value' => $this->canonicalValue,
            'display_value' => $this->displayValue,
            'evidence_ids' => $this->evidenceIds,
            'metadata' => $this->metadata,
        ];
    }
}
