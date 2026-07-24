<?php

namespace LBHurtado\XDocument\Browser\Html;

use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocument\Exceptions\InvalidBrowserHtmlProjection;
use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserField;
use LBHurtado\XDocument\Projection\Browser\BrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserSection;
use LBHurtado\XDocument\Projection\Browser\ValidateBrowserProjection;
use stdClass;

final readonly class BrowserHtmlProjectionAdapter
{
    public const Format = 'browser-html/1.0';

    public const MediaType = 'text/html; charset=utf-8';

    public function __construct(private ValidateBrowserProjection $validateProjection = new ValidateBrowserProjection) {}

    public function adapt(BrowserProjection $projection): DocumentOutput
    {
        $projection = $this->validateProjection->handle($projection);
        $html = $this->html($projection);
        $this->assertHtml($html);

        return DocumentOutput::inline(
            mediaType: self::MediaType,
            content: $html,
            filename: $this->filename($projection),
            metadata: [
                'adapter_format' => self::Format,
                'browser_projection_identifier' => $projection->identifier,
            ],
        );
    }

    private function html(BrowserProjection $projection): string
    {
        $lines = [
            '<!doctype html>',
            '<html lang="en">',
            '<head>',
            '    <meta charset="utf-8">',
            '    <meta name="viewport" content="width=device-width, initial-scale=1">',
            '    <title>'.$this->escape($projection->title).'</title>',
            '</head>',
            '<body>',
            '    <main id="'.$this->escape($projection->identifier).'" class="x-document" data-x-document-format="'.self::Format.'" data-x-document-projection-id="'.$this->escape($projection->identifier).'">',
            '        <header class="x-document__header">',
            '            <h1>'.$this->escape($projection->title).'</h1>',
            '            <dl class="x-document__summary">',
            ...$this->summary($projection),
            '            </dl>',
            '        </header>',
        ];

        foreach ($projection->sections as $section) {
            array_push($lines, ...$this->section($section));
        }
        if ($projection->actions !== []) {
            array_push($lines, ...$this->actions($projection->actions));
        }
        if ($projection->attachments !== []) {
            array_push($lines, ...$this->attachments($projection->attachments));
        }
        if ($projection->evidence !== []) {
            array_push($lines, ...$this->evidence($projection->evidence));
        }

        return implode("\n", [
            ...$lines,
            '    </main>',
            '</body>',
            '</html>',
            '',
        ]);
    }

    /** @return list<string> */
    private function summary(BrowserProjection $projection): array
    {
        $subjectIdentifier = $this->objectString($projection->subject, 'identifier');
        $subjectType = $this->objectString($projection->subject, 'type');
        $artifactIdentifier = $this->objectString($projection->primaryArtifact, 'identifier');
        $artifactType = $this->objectString($projection->primaryArtifact, 'type');

        return [
            '                <dt>Subject</dt>',
            '                <dd>'.$this->escape("{$subjectType} {$subjectIdentifier}").'</dd>',
            '                <dt>Primary artifact</dt>',
            '                <dd>'.$this->escape("{$artifactType} {$artifactIdentifier}").'</dd>',
            '                <dt>Audience</dt>',
            '                <dd>'.$this->escape(implode(', ', $projection->audience)).'</dd>',
            '                <dt>Projection</dt>',
            '                <dd>'.BrowserProjection::Format.' (read-only)</dd>',
        ];
    }

    /** @return list<string> */
    private function section(BrowserSection $section): array
    {
        $headingId = $section->id.'-heading';
        $lines = [
            '        <section id="'.$this->escape($section->id).'" class="x-document__section" aria-labelledby="'.$this->escape($headingId).'">',
            '            <h2 id="'.$this->escape($headingId).'">'.$this->escape($section->label).'</h2>',
            '            <dl class="x-document__fields">',
        ];
        foreach ($section->elements as $field) {
            array_push($lines, ...$this->field($field));
        }

        return [
            ...$lines,
            '            </dl>',
            '        </section>',
        ];
    }

    /** @return list<string> */
    private function field(BrowserField $field): array
    {
        $valueType = $this->objectString($field->canonicalValue, 'type');
        $describedBy = $field->evidenceIds === []
            ? ''
            : ' aria-describedby="'.$this->escape(implode(' ', $field->evidenceIds)).'"';
        $nullClass = $field->displayValue === null ? ' x-document__field-value--null' : '';

        return [
            '                <div id="'.$this->escape($field->id).'" class="x-document__field" data-x-document-field-id="'.$this->escape($field->id).'" data-x-document-value-type="'.$this->escape($valueType).'">',
            '                    <dt class="x-document__field-label">'.$this->escape($field->label).'</dt>',
            '                    <dd class="x-document__field-value'.$nullClass.'"'.$describedBy.'>'.$this->escape($field->displayValue ?? '').'</dd>',
            '                </div>',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<string>
     */
    private function actions(array $actions): array
    {
        $lines = [
            '        <section class="x-document__actions" aria-labelledby="x-document-actions-heading">',
            '            <h2 id="x-document-actions-heading">Actions</h2>',
            '            <ul>',
        ];
        foreach ($actions as $action) {
            $id = $this->arrayString($action, 'id');
            $state = $this->arrayBoolean($action, 'enabled') ? 'enabled' : 'disabled';
            $lines[] = '                <li id="'.$this->escape($id).'" class="x-document__action x-document__action--'.$state.'">';
            $lines[] = '                    <span class="x-document__action-label">'.$this->escape($this->arrayString($action, 'label')).'</span>';
            $lines[] = '                    <span class="x-document__action-type">'.$this->escape($this->arrayString($action, 'type')).'</span>';
            $lines[] = '                </li>';
        }

        return [...$lines, '            </ul>', '        </section>'];
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return list<string>
     */
    private function attachments(array $attachments): array
    {
        $lines = [
            '        <section class="x-document__attachments" aria-labelledby="x-document-attachments-heading">',
            '            <h2 id="x-document-attachments-heading">Attachments</h2>',
            '            <ul>',
        ];
        foreach ($attachments as $attachment) {
            $id = $this->arrayString($attachment, 'id');
            $lines[] = '                <li id="'.$this->escape($id).'" class="x-document__attachment">';
            $lines[] = '                    <span class="x-document__attachment-name">'.$this->escape($this->arrayString($attachment, 'name')).'</span>';
            foreach (['media_type' => 'Media type', 'byte_length' => 'Byte length', 'checksum' => 'Checksum', 'disposition' => 'Disposition'] as $key => $label) {
                $value = $attachment[$key] ?? null;
                if (is_string($value) || is_int($value)) {
                    $lines[] = '                    <span class="x-document__attachment-detail">'.$label.': '.$this->escape((string) $value).'</span>';
                }
            }
            $lines[] = '                </li>';
        }

        return [...$lines, '            </ul>', '        </section>'];
    }

    /**
     * @param  list<array<string, mixed>>  $evidence
     * @return list<string>
     */
    private function evidence(array $evidence): array
    {
        $lines = [
            '        <section class="x-document__evidence" aria-labelledby="x-document-evidence-heading">',
            '            <h2 id="x-document-evidence-heading">Evidence</h2>',
            '            <ul>',
        ];
        foreach ($evidence as $item) {
            $id = $this->arrayString($item, 'id');
            $revision = $item['artifact_revision'] ?? null;
            if (! is_int($revision)) {
                throw new InvalidBrowserProjection('Browser HTML evidence revision is invalid.');
            }
            $description = sprintf(
                '%s %s, revision %d, subject %s',
                $this->arrayString($item, 'artifact_type'),
                $this->arrayString($item, 'artifact_identifier'),
                $revision,
                $this->arrayString($item, 'subject_identifier'),
            );
            $payloadPath = $item['payload_path'] ?? null;
            if ($payloadPath !== null) {
                if (! is_string($payloadPath)) {
                    throw new InvalidBrowserProjection('Browser HTML evidence payload path is invalid.');
                }
                $description .= ', payload '.$payloadPath;
            }
            $lines[] = '                <li id="'.$this->escape($id).'" class="x-document__evidence-item">'.$this->escape($description).'</li>';
        }

        return [...$lines, '            </ul>', '        </section>'];
    }

    private function filename(BrowserProjection $projection): string
    {
        $identifier = $this->objectString($projection->sourceDocument, 'identifier');
        $safeIdentifier = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $identifier), '-');
        $safeIdentifier = ltrim($safeIdentifier, '.-');
        if ($safeIdentifier === '') {
            throw new InvalidBrowserProjection('Browser HTML output filename cannot be derived safely.');
        }

        return $safeIdentifier.'.html';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function assertHtml(string $html): void
    {
        if (
            preg_match('//u', $html) !== 1
            || ! str_starts_with($html, "<!doctype html>\n<html lang=\"en\">\n")
            || ! str_contains($html, '<main ')
            || ! str_contains($html, '<h1>')
            || ! str_ends_with($html, "</html>\n")
        ) {
            throw new InvalidBrowserHtmlProjection('Generated browser HTML violates the adapter structure.');
        }
    }

    private function objectString(stdClass $object, string $key): string
    {
        $value = $object->{$key} ?? null;
        if (! is_string($value) || $value === '') {
            throw new InvalidBrowserProjection("Browser HTML source {$key} must be a non-empty string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $value */
    private function arrayString(array $value, string $key): string
    {
        $item = $value[$key] ?? null;
        if (! is_string($item) || $item === '') {
            throw new InvalidBrowserProjection("Browser HTML source {$key} must be a non-empty string.");
        }

        return $item;
    }

    /** @param array<string, mixed> $value */
    private function arrayBoolean(array $value, string $key): bool
    {
        $item = $value[$key] ?? null;
        if (! is_bool($item)) {
            throw new InvalidBrowserProjection("Browser HTML source {$key} must be boolean.");
        }

        return $item;
    }
}
