<?php

use LBHurtado\XDocument\Contract\ContractSchemaRegistry;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationStatus;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationRequest;
use LBHurtado\XDocument\Drivers\BrowserDocumentDriver;
use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserField;
use LBHurtado\XDocument\Projection\Browser\BrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BrowserProjectionSchemaRegistry;
use LBHurtado\XDocument\Projection\Browser\BuildBrowserProjection;

function browserDriverFixture(string $fixture = 'invoice-request.json'): string
{
    return file_get_contents(dirname(__DIR__).'/Fixtures/Contract/1.0/'.$fixture)
        ?: throw new RuntimeException("Fixture {$fixture} is unavailable.");
}

function browserDriverRequest(string $fixture = 'invoice-request.json'): DocumentCompilationRequest
{
    $json = str_replace('"requested_driver": "json"', '"requested_driver": "browser"', browserDriverFixture($fixture));

    return (new ValidateDocumentCompilationRequest)->handleJson($json);
}

it('declares a stable browser identity and truthful contract capabilities', function () {
    $driver = new BrowserDocumentDriver;

    expect($driver->name())->toBe('browser')
        ->and($driver->capabilities())->toBe(['actions', 'attachments', 'evidence']);
});

it('returns unsupported for a different target without compiling', function () {
    $request = (new ValidateDocumentCompilationRequest)->handleJson(browserDriverFixture());

    $result = (new BrowserDocumentDriver)->compile($request);

    expect($result->status)->toBe(DocumentCompilationStatus::Unsupported)
        ->and($result->output)->toBeNull()
        ->and($result->warnings)->toBe(['Driver browser cannot compile a request targeted to json.']);
});

it('reports unsupported requested capabilities deterministically', function () {
    $payload = json_decode(browserDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $payload->requested_driver = 'browser';
    $payload->requested_capabilities = ['unknown.beta', 'evidence', 'unknown.alpha'];
    $request = DocumentCompilationRequest::fromObject($payload);

    $result = (new BrowserDocumentDriver)->compile($request);

    expect($result->status)->toBe(DocumentCompilationStatus::Unsupported)
        ->and($result->output)->toBeNull()
        ->and($result->warnings)->toBe([
            'Unsupported capability: unknown.alpha',
            'Unsupported capability: unknown.beta',
        ])
        ->and($result->metadata['unsupported_capabilities'])->toBe(['unknown.alpha', 'unknown.beta']);
});

it('projects every contract fixture into deterministic schema-valid browser JSON', function (string $fixture) {
    $request = browserDriverRequest($fixture);
    $driver = new BrowserDocumentDriver;

    $first = $driver->compile($request);
    $second = $driver->compile($request);
    $content = $first->output?->inlineContent ?? '';
    $projection = json_decode($content, false, flags: JSON_THROW_ON_ERROR);
    $result = json_decode(json_encode($first->toArray(), JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
    $projectionValidation = (new BrowserProjectionSchemaRegistry)->validator()
        ->validate($projection, BrowserProjectionSchemaRegistry::Projection);
    $resultValidation = (new ContractSchemaRegistry)->validator()
        ->validate($result, ContractSchemaRegistry::Result);

    expect($first->status)->toBe(DocumentCompilationStatus::Succeeded)
        ->and($first->output?->mediaType)->toBe(BrowserProjection::MediaType)
        ->and($first->output?->contentReference)->toBeNull()
        ->and($first->output?->inlineContent)->toBe($second->output?->inlineContent)
        ->and($first->output?->checksum)->toBe($second->output?->checksum)
        ->and($first->output?->checksum)->toBe('sha256:'.hash('sha256', $content))
        ->and($first->output?->byteLength)->toBe(strlen($content))
        ->and($projectionValidation->isValid())->toBeTrue()
        ->and($resultValidation->isValid())->toBeTrue()
        ->and($projection->projection_format)->toBe('browser/1.0')
        ->and($projection->read_only)->toBeTrue()
        ->and($projection->subject)->toEqual($request->document->toPayload()->subject)
        ->and($projection->primary_artifact)->toEqual($request->document->toPayload()->primary_artifact)
        ->and($projection->actions)->toHaveCount(count($request->document->toPayload()->actions))
        ->and($projection->attachments)->toHaveCount(count($request->document->toPayload()->attachments))
        ->and($projection->evidence)->not->toBeEmpty();
})->with([
    'invoice' => ['invoice-request.json'],
    'receipt' => ['receipt-request.json'],
    'reservation certificate' => ['reservation-certificate-request.json'],
]);

it('preserves section field and list order with stable linked identities', function () {
    $request = browserDriverRequest();

    $projection = (new BuildBrowserProjection)->handle($request)->toArray();

    expect(array_column($projection['sections'], 'source_identifier'))->toBe(['reservation', 'charges'])
        ->and(array_column($projection['sections'][0]['elements'], 'source_identifier'))->toBe(['applicant_alias', 'lot_identifier'])
        ->and($projection['sections'][0]['id'])->toBe('section:reservation')
        ->and($projection['sections'][0]['elements'][0]['id'])->toBe('field:reservation.applicant_alias')
        ->and($projection['sections'][0]['elements'][0]['canonical_value']->value)->toBe('Ana Example')
        ->and($projection['sections'][0]['elements'][0]['display_value'])->toBe('Ana Example')
        ->and($projection['sections'][0]['elements'][0]['evidence_ids'][0])->toStartWith('evidence:')
        ->and(array_column($projection['actions'], 'source_identifier'))->toBe(['submit_payment_evidence']);
});

it('rejects unsafe browser element identities', function () {
    new BrowserField(
        id: 'field:unsafe/path',
        sourceIdentifier: 'unsafe',
        label: 'Unsafe',
        canonicalValue: (object) ['type' => 'string', 'value' => 'value'],
        displayValue: 'value',
        evidenceIds: [],
        metadata: new stdClass,
    );
})->throws(InvalidBrowserProjection::class);

it('rejects duplicate section and element identities', function (string $duplicate) {
    $payload = json_decode(browserDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $payload->requested_driver = 'browser';
    if ($duplicate === 'section') {
        $payload->document->sections[] = clone $payload->document->sections[0];
    } else {
        $payload->document->sections[0]->fields[] = clone $payload->document->sections[0]->fields[0];
    }

    (new BuildBrowserProjection)->handle(DocumentCompilationRequest::fromObject($payload));
})->with(['section', 'field'])->throws(InvalidBrowserProjection::class);

it('propagates unexpected projection serialization defects', function () {
    $payload = json_decode(browserDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $payload->requested_driver = 'browser';
    $resource = fopen('php://memory', 'r');
    $payload->document->metadata->unexpected = $resource;
    $request = DocumentCompilationRequest::fromObject($payload);

    try {
        (new BrowserDocumentDriver)->compile($request);
    } finally {
        if (is_resource($resource)) {
            fclose($resource);
        }
    }
})->throws(JsonException::class);

it('publishes an exact versioned browser projection manifest', function () {
    $root = dirname(__DIR__, 2).'/resources/projections/browser/1.0';
    $manifest = json_decode(
        file_get_contents($root.'/manifest.json') ?: throw new RuntimeException('Projection manifest is unavailable.'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $schema = $manifest['schemas'][0];

    expect($manifest['projection_format'])->toBe('browser/1.0')
        ->and($schema['id'])->toBe(BrowserProjectionSchemaRegistry::Projection)
        ->and($schema['file'])->toBe('browser-projection.schema.json')
        ->and($schema['sha256'])->toBe(hash_file('sha256', $root.'/'.$schema['file']));
});
