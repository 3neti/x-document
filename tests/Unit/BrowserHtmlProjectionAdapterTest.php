<?php

use LBHurtado\XDocument\Browser\Html\BrowserHtmlProjectionAdapter;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationRequest;
use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserField;
use LBHurtado\XDocument\Projection\Browser\BrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserSection;
use LBHurtado\XDocument\Projection\Browser\BuildBrowserProjection;

function browserHtmlFixtureRequest(string $fixture = 'invoice-request.json'): DocumentCompilationRequest
{
    $json = file_get_contents(dirname(__DIR__).'/Fixtures/Contract/1.0/'.$fixture)
        ?: throw new RuntimeException("Fixture {$fixture} is unavailable.");
    $json = str_replace('"requested_driver": "json"', '"requested_driver": "browser"', $json);

    return (new ValidateDocumentCompilationRequest)->handleJson($json);
}

function browserHtmlProjection(string $fixture = 'invoice-request.json'): BrowserProjection
{
    return (new BuildBrowserProjection)->handle(browserHtmlFixtureRequest($fixture));
}

it('adapts every browser projection to deterministic approved HTML', function (string $fixture, string $snapshot) {
    $projection = browserHtmlProjection($fixture);
    $adapter = new BrowserHtmlProjectionAdapter;

    $first = $adapter->adapt($projection);
    $second = $adapter->adapt($projection);
    $expected = file_get_contents(dirname(__DIR__).'/Fixtures/BrowserHtml/1.0/'.$snapshot)
        ?: throw new RuntimeException("Snapshot {$snapshot} is unavailable.");

    expect($first->mediaType)->toBe('text/html; charset=utf-8')
        ->and($first->filename)->toEndWith('.html')
        ->and($first->contentReference)->toBeNull()
        ->and($first->inlineContent)->toBe($expected)
        ->and($first->inlineContent)->toBe($second->inlineContent)
        ->and($first->checksum)->toBe('sha256:'.hash('sha256', $expected))
        ->and($first->byteLength)->toBe(strlen($expected))
        ->and($first->metadata)->toBe([
            'adapter_format' => 'browser-html/1.0',
            'browser_projection_identifier' => $projection->identifier,
        ]);
})->with([
    'invoice' => ['invoice-request.json', 'invoice.html'],
    'receipt' => ['receipt-request.json', 'receipt.html'],
    'reservation certificate' => ['reservation-certificate-request.json', 'reservation-certificate.html'],
]);

it('emits a complete semantic read-only document with stable source-derived identities', function () {
    $projection = browserHtmlProjection();

    $html = (new BrowserHtmlProjectionAdapter)->adapt($projection)->inlineContent ?? '';

    expect($html)->toStartWith("<!doctype html>\n<html lang=\"en\">\n")
        ->and($html)->toEndWith("</html>\n")
        ->and($html)->toContain(
            '<main id="'.$projection->identifier.'" class="x-document"',
            'data-x-document-format="browser-html/1.0"',
            '<h1>'.$projection->title.'</h1>',
            '<section id="section:reservation"',
            '<dl class="x-document__fields">',
            '<dt class="x-document__field-label">Applicant</dt>',
            '<dd class="x-document__field-value"',
            'data-x-document-value-type="string"',
            '<section class="x-document__actions"',
            '<section class="x-document__evidence"',
        )
        ->and($html)->not->toContain('<button', '<form', '<a ', '<script', '<style', 'onclick=');
});

it('escapes every text and attribute context without accepting raw HTML', function () {
    $projection = browserHtmlProjection();
    $payload = '<script>alert(1)</script><img src=x onerror=alert(1)>"><svg onload=alert(1)>& < > " \'';
    $field = new BrowserField(
        id: 'field:security.payload',
        sourceIdentifier: 'payload',
        label: $payload,
        canonicalValue: (object) ['type' => 'string', 'value' => $payload],
        displayValue: $payload,
        evidenceIds: [],
        metadata: new stdClass,
    );
    $malicious = new BrowserProjection(
        identifier: $projection->identifier,
        sourceDocument: $projection->sourceDocument,
        title: $payload,
        audience: [$payload],
        subject: (object) ['identifier' => $payload, 'type' => 'Subject'],
        primaryArtifact: $projection->primaryArtifact,
        sections: [new BrowserSection('section:security', 'security', $payload, [$field], new stdClass)],
        actions: [[
            'id' => 'action:security',
            'source_identifier' => 'security',
            'label' => $payload,
            'type' => $payload,
            'enabled' => true,
            'metadata' => new stdClass,
        ]],
        attachments: [],
        evidence: [],
        metadata: new stdClass,
    );

    $html = (new BrowserHtmlProjectionAdapter)->adapt($malicious)->inlineContent ?? '';

    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', '&quot;&gt;&lt;svg onload=alert(1)&gt;')
        ->and($html)->not->toContain('<script>', '<img ', '<svg ')
        ->and(preg_match('/<[^>]+\son(?:error|load)=/i', $html))->toBe(0);
});

it('rejects malformed UTF-8 before emitting output bytes', function () {
    $projection = browserHtmlProjection();
    $invalidUtf8 = "Invalid \xC3\x28";
    $malformed = new BrowserProjection(
        $projection->identifier,
        $projection->sourceDocument,
        $invalidUtf8,
        $projection->audience,
        $projection->subject,
        $projection->primaryArtifact,
        $projection->sections,
        $projection->actions,
        $projection->attachments,
        $projection->evidence,
        $projection->metadata,
    );

    (new BrowserHtmlProjectionAdapter)->adapt($malformed);
})->throws(JsonException::class);

it('renders null empty false and zero as structurally distinct values', function () {
    $projection = browserHtmlProjection();
    $fields = [
        new BrowserField('field:values.null', 'null', 'Null', (object) ['type' => 'null', 'value' => null], null, [], new stdClass),
        new BrowserField('field:values.empty', 'empty', 'Empty', (object) ['type' => 'string', 'value' => ''], '', [], new stdClass),
        new BrowserField('field:values.false', 'false', 'False', (object) ['type' => 'boolean', 'value' => false], 'false', [], new stdClass),
        new BrowserField('field:values.zero', 'zero', 'Zero', (object) ['type' => 'integer', 'value' => 0], '0', [], new stdClass),
    ];
    $values = new BrowserProjection(
        $projection->identifier,
        $projection->sourceDocument,
        $projection->title,
        $projection->audience,
        $projection->subject,
        $projection->primaryArtifact,
        [new BrowserSection('section:values', 'values', 'Values', $fields, new stdClass)],
        [],
        [],
        [],
        new stdClass,
    );

    $html = (new BrowserHtmlProjectionAdapter)->adapt($values)->inlineContent ?? '';

    expect($html)->toContain(
        'x-document__field-value x-document__field-value--null"></dd>',
        'data-x-document-value-type="string">',
        'data-x-document-value-type="boolean">',
        '>false</dd>',
        'data-x-document-value-type="integer">',
        '>0</dd>',
    );
});

it('renders attachment metadata without exposing source references or creating links', function () {
    $projection = browserHtmlProjection();
    $withAttachment = new BrowserProjection(
        $projection->identifier,
        $projection->sourceDocument,
        $projection->title,
        $projection->audience,
        $projection->subject,
        $projection->primaryArtifact,
        $projection->sections,
        $projection->actions,
        [[
            'id' => 'attachment:terms',
            'source_identifier' => 'terms',
            'name' => 'Terms & conditions',
            'media_type' => 'text/plain',
            'byte_length' => 42,
            'checksum' => 'sha256:'.str_repeat('a', 64),
            'source_reference' => 'private/terms.txt',
            'disposition' => 'attachment',
            'metadata' => new stdClass,
        ]],
        $projection->evidence,
        $projection->metadata,
    );

    $html = (new BrowserHtmlProjectionAdapter)->adapt($withAttachment)->inlineContent ?? '';

    expect($html)->toContain('Terms &amp; conditions', 'Media type: text/plain', 'Byte length: 42')
        ->and($html)->not->toContain('private/terms.txt', '<a ');
});

it('derives a safe deterministic basename without trusting document identity as a path', function () {
    $projection = browserHtmlProjection();
    $unsafe = new BrowserProjection(
        $projection->identifier,
        (object) [...get_object_vars($projection->sourceDocument), 'identifier' => '../../unsafe document'],
        $projection->title,
        $projection->audience,
        $projection->subject,
        $projection->primaryArtifact,
        $projection->sections,
        $projection->actions,
        $projection->attachments,
        $projection->evidence,
        $projection->metadata,
    );

    expect((new BrowserHtmlProjectionAdapter)->adapt($unsafe)->filename)->toBe('unsafe-document.html');
});

it('rejects an invalid browser projection before adaptation', function () {
    $projection = browserHtmlProjection();
    $invalid = new BrowserProjection(
        $projection->identifier,
        $projection->sourceDocument,
        $projection->title,
        $projection->audience,
        $projection->subject,
        new stdClass,
        $projection->sections,
        $projection->actions,
        $projection->attachments,
        $projection->evidence,
        $projection->metadata,
    );

    (new BrowserHtmlProjectionAdapter)->adapt($invalid);
})->throws(InvalidBrowserProjection::class);

it('publishes a separate x-document-owned HTML adapter manifest', function () {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/resources/projections/browser-html/1.0/manifest.json')
            ?: throw new RuntimeException('Browser HTML manifest is unavailable.'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)->toBe([
        'format' => 'browser-html/1.0',
        'input_format' => 'browser/1.0',
        'media_type' => 'text/html; charset=utf-8',
    ]);
});
