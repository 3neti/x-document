<?php

namespace LBHurtado\XDocument\Projection\Browser;

use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use stdClass;

final readonly class BuildBrowserProjection
{
    public function __construct(private CanonicalJson $json = new CanonicalJson) {}

    public function handle(DocumentCompilationRequest $request): BrowserProjection
    {
        $document = $request->document->toPayload();
        $evidence = [];
        $evidenceById = [];
        $projectEvidence = function (stdClass $source) use (&$evidence, &$evidenceById): string {
            $id = $this->evidenceId($source);
            if (! isset($evidenceById[$id])) {
                $projected = $this->projectEvidence($source, $id);
                $evidenceById[$id] = true;
                $evidence[] = $projected;
            }

            return $id;
        };

        foreach ($this->objectList($document, 'evidence') as $sourceEvidence) {
            $projectEvidence($sourceEvidence);
        }

        $sections = [];
        foreach ($this->objectList($document, 'sections') as $sourceSection) {
            $sourceSectionIdentifier = $this->string($sourceSection, 'identifier');
            $sectionId = 'section:'.BrowserIdentity::token($sourceSectionIdentifier);
            $fields = [];
            foreach ($this->objectList($sourceSection, 'fields') as $sourceField) {
                $sourceFieldIdentifier = $this->string($sourceField, 'identifier');
                $fieldEvidence = [];
                foreach ($this->objectList($sourceField, 'evidence') as $sourceEvidence) {
                    $fieldEvidence[] = $projectEvidence($sourceEvidence);
                }
                $value = $this->object($sourceField, 'value');
                $metadata = $this->object($sourceField, 'metadata');
                $fields[] = new BrowserField(
                    id: 'field:'.BrowserIdentity::token($sourceSectionIdentifier).'.'.BrowserIdentity::token($sourceFieldIdentifier),
                    sourceIdentifier: $sourceFieldIdentifier,
                    label: $this->string($sourceField, 'label'),
                    canonicalValue: $value,
                    displayValue: $this->displayValue($value, $metadata),
                    evidenceIds: $fieldEvidence,
                    metadata: $metadata,
                );
            }
            $sections[] = new BrowserSection(
                id: $sectionId,
                sourceIdentifier: $sourceSectionIdentifier,
                label: $this->string($sourceSection, 'title'),
                elements: $fields,
                metadata: new stdClass,
            );
        }

        return new BrowserProjection(
            identifier: 'browser:'.hash('sha256', BrowserProjection::Format."\nbrowser\n".$request->requestFingerprint),
            sourceDocument: (object) [
                'identifier' => $this->string($document, 'identifier'),
                'definition_identifier' => $this->string($document, 'definition_identifier'),
                'definition_revision' => $document->definition_revision ?? throw new InvalidBrowserProjection('Document definition revision is missing.'),
                'resolution_fingerprint' => $this->string($document, 'resolution_fingerprint'),
                'status' => $this->string($document, 'status'),
            ],
            title: $this->string($document, 'title'),
            audience: $this->stringList($document, 'audience'),
            subject: $this->object($document, 'subject'),
            primaryArtifact: $this->object($document, 'primary_artifact'),
            sections: $sections,
            actions: $this->actions($document),
            attachments: $this->attachments($document),
            evidence: $evidence,
            metadata: (object) [
                'document_metadata' => $this->object($document, 'metadata'),
            ],
        );
    }

    /** @return list<array<string, mixed>> */
    private function actions(stdClass $document): array
    {
        $actions = [];
        foreach ($this->objectList($document, 'actions') as $action) {
            $identifier = $this->string($action, 'identifier');
            $actions[] = [
                'id' => 'action:'.BrowserIdentity::token($identifier),
                'source_identifier' => $identifier,
                'label' => $this->string($action, 'label'),
                'type' => $this->string($action, 'type'),
                'enabled' => $this->boolean($action, 'enabled'),
                'metadata' => $this->object($action, 'metadata'),
            ];
        }

        return $actions;
    }

    /** @return list<array<string, mixed>> */
    private function attachments(stdClass $document): array
    {
        $attachments = [];
        foreach ($this->objectList($document, 'attachments') as $attachment) {
            $identifier = $this->string($attachment, 'identifier');
            $attachments[] = [
                'id' => 'attachment:'.BrowserIdentity::token($identifier),
                'source_identifier' => $identifier,
                'name' => $this->string($attachment, 'name'),
                'media_type' => $attachment->media_type ?? null,
                'byte_length' => $attachment->byte_length ?? null,
                'checksum' => $attachment->checksum ?? null,
                'source_reference' => $attachment->source_reference ?? null,
                'disposition' => $this->string($attachment, 'disposition'),
                'metadata' => $this->object($attachment, 'metadata'),
            ];
        }

        return $attachments;
    }

    private function evidenceId(stdClass $evidence): string
    {
        return 'evidence:'.substr(hash('sha256', $this->json->encode($evidence)), 0, 32);
    }

    /** @return array<string, mixed> */
    private function projectEvidence(stdClass $evidence, string $id): array
    {
        $artifactRevision = $evidence->artifact_revision ?? null;
        $payloadPath = $evidence->payload_path ?? null;

        if (! is_int($artifactRevision) || ($payloadPath !== null && ! is_string($payloadPath))) {
            throw new InvalidBrowserProjection('Browser evidence contains an invalid revision or payload path.');
        }

        return [
            'id' => $id,
            'artifact_identifier' => $this->string($evidence, 'artifact_identifier'),
            'artifact_revision' => $artifactRevision,
            'artifact_type' => $this->string($evidence, 'artifact_type'),
            'subject_identifier' => $this->string($evidence, 'subject_identifier'),
            'source_reference' => $this->string($evidence, 'source_reference'),
            'payload_path' => $payloadPath,
        ];
    }

    private function displayValue(stdClass $value, stdClass $metadata): ?string
    {
        $declaredDisplay = $metadata->display_value ?? null;
        if (is_string($declaredDisplay)) {
            return $declaredDisplay;
        }
        $type = $this->string($value, 'type');
        $canonical = $value->value ?? null;

        return match ($type) {
            'null' => null,
            'string' => is_string($canonical) ? $canonical : throw new InvalidBrowserProjection('String value is malformed.'),
            'integer' => is_int($canonical) ? (string) $canonical : throw new InvalidBrowserProjection('Integer value is malformed.'),
            'boolean' => is_bool($canonical) ? ($canonical ? 'true' : 'false') : throw new InvalidBrowserProjection('Boolean value is malformed.'),
            'list', 'map' => $this->json->encode($canonical),
            default => throw new InvalidBrowserProjection("Unsupported normalized value type: {$type}"),
        };
    }

    private function object(stdClass $source, string $property): stdClass
    {
        $value = $source->{$property} ?? null;
        if (! $value instanceof stdClass) {
            throw new InvalidBrowserProjection("Browser projection source {$property} must be an object.");
        }

        return $value;
    }

    /** @return list<stdClass> */
    private function objectList(stdClass $source, string $property): array
    {
        $values = $source->{$property} ?? null;
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidBrowserProjection("Browser projection source {$property} must be a list.");
        }
        foreach ($values as $value) {
            if (! $value instanceof stdClass) {
                throw new InvalidBrowserProjection("Every browser projection source {$property} item must be an object.");
            }
        }

        return $values;
    }

    /** @return list<string> */
    private function stringList(stdClass $source, string $property): array
    {
        $values = $source->{$property} ?? null;
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidBrowserProjection("Browser projection source {$property} must be a list.");
        }
        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                throw new InvalidBrowserProjection("Every browser projection source {$property} item must be a string.");
            }
        }

        return $values;
    }

    private function string(stdClass $source, string $property): string
    {
        $value = $source->{$property} ?? null;
        if (! is_string($value) || $value === '') {
            throw new InvalidBrowserProjection("Browser projection source {$property} must be a non-empty string.");
        }

        return $value;
    }

    private function boolean(stdClass $source, string $property): bool
    {
        $value = $source->{$property} ?? null;
        if (! is_bool($value)) {
            throw new InvalidBrowserProjection("Browser projection source {$property} must be boolean.");
        }

        return $value;
    }
}
