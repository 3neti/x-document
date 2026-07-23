<?php

use LBHurtado\XDocument\Contract\ContractSchemaRegistry;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationResult;
use LBHurtado\XDocument\Contract\DocumentCompilationStatus;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationRequest;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationResult;
use LBHurtado\XDocument\Drivers\JsonDocumentDriver;
use LBHurtado\XDocument\Exceptions\InvalidDocumentCompilationResult;
use LBHurtado\XDocument\Exceptions\InvalidDocumentOutput;

function jsonDriverFixture(string $fixture = 'invoice-request.json'): string
{
    return file_get_contents(dirname(__DIR__).'/Fixtures/Contract/1.0/'.$fixture)
        ?: throw new RuntimeException("Fixture {$fixture} is unavailable.");
}

function jsonDriverRequest(string $fixture = 'invoice-request.json'): DocumentCompilationRequest
{
    return (new ValidateDocumentCompilationRequest)->handleJson(jsonDriverFixture($fixture));
}

it('constructs only invariant-safe result states through named factories', function () {
    $request = jsonDriverRequest();
    $output = DocumentOutput::inline('application/json', "{}\n");

    $succeeded = DocumentCompilationResult::succeeded($request, 'json', $output);
    $unsupported = DocumentCompilationResult::unsupported($request, 'json', ['Unsupported capability.']);
    $failed = DocumentCompilationResult::failed($request, 'json', ['Compilation failed safely.']);
    $constructor = new ReflectionMethod(DocumentCompilationResult::class, '__construct');
    $validator = new ValidateDocumentCompilationResult;

    expect($constructor->isPrivate())->toBeTrue()
        ->and($succeeded->status)->toBe(DocumentCompilationStatus::Succeeded)
        ->and($succeeded->output)->toBe($output)
        ->and($unsupported->status)->toBe(DocumentCompilationStatus::Unsupported)
        ->and($unsupported->output)->toBeNull()
        ->and($failed->status)->toBe(DocumentCompilationStatus::Failed)
        ->and($failed->output)->toBeNull()
        ->and($validator->handle($succeeded))->toBe($succeeded)
        ->and($validator->handle($unsupported))->toBe($unsupported)
        ->and($validator->handle($failed))->toBe($failed);
});

it('rejects malformed result warnings before schema validation', function () {
    DocumentCompilationResult::failed(jsonDriverRequest(), 'json', [123]);
})->throws(InvalidArgumentException::class);

it('constructs inline output with guaranteed checksum byte length and one content mode', function () {
    $content = "{\"ok\":true}\n";
    $output = DocumentOutput::inline('application/json', $content, 'document.json');
    $constructor = new ReflectionMethod(DocumentOutput::class, '__construct');

    expect($constructor->isPrivate())->toBeTrue()
        ->and($output->inlineContent)->toBe($content)
        ->and($output->contentReference)->toBeNull()
        ->and($output->checksum)->toBe('sha256:'.hash('sha256', $content))
        ->and($output->byteLength)->toBe(strlen($content));
});

it('rejects invalid referenced output checksums and unsafe filenames', function () {
    DocumentOutput::referenced('application/json', 'outputs/document.json', checksum: 'invalid');
})->throws(InvalidDocumentOutput::class);

it('rejects unsafe inline output filenames', function (string $filename) {
    DocumentOutput::inline('application/json', '{}', $filename);
})->with([
    'traversal' => ['../document.json'],
    'Windows path' => ['C:\\temp\\document.json'],
    'nested path' => ['outputs/document.json'],
])->throws(InvalidDocumentOutput::class);

it('declares only capabilities the JSON driver actually preserves', function () {
    expect((new JsonDocumentDriver)->capabilities())->toBe(['actions', 'attachments', 'evidence']);
});

it('does not compile a request targeted to another driver', function () {
    $json = str_replace('"requested_driver": "json"', '"requested_driver": "pdf"', jsonDriverFixture());
    $request = (new ValidateDocumentCompilationRequest)->handleJson($json);

    $result = (new JsonDocumentDriver)->compile($request);

    expect($result->status)->toBe(DocumentCompilationStatus::Unsupported)
        ->and($result->output)->toBeNull()
        ->and($result->warnings)->toBe(['Driver json cannot compile a request targeted to pdf.'])
        ->and($result->metadata)->toBe([
            'requested_driver' => 'pdf',
            'supported_driver' => 'json',
        ]);
});

it('reports unsupported capabilities deterministically instead of ignoring them', function () {
    $payload = json_decode(jsonDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $payload->requested_capabilities = ['unknown.beta', 'actions', 'unknown.alpha'];
    $request = DocumentCompilationRequest::fromObject($payload);

    $result = (new JsonDocumentDriver)->compile($request);

    expect($result->status)->toBe(DocumentCompilationStatus::Unsupported)
        ->and($result->output)->toBeNull()
        ->and($result->warnings)->toBe([
            'Unsupported capability: unknown.alpha',
            'Unsupported capability: unknown.beta',
        ])
        ->and($result->metadata['unsupported_capabilities'])->toBe(['unknown.alpha', 'unknown.beta']);
});

it('round trips every fixture without changing contract semantics', function (string $fixture) {
    $request = jsonDriverRequest($fixture);
    $driver = new JsonDocumentDriver;

    $first = $driver->compile($request);
    $second = $driver->compile($request);
    $decoded = json_decode($first->output?->inlineContent ?? '', true, flags: JSON_THROW_ON_ERROR);
    $expected = json_decode(json_encode($request->toArray(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    $resultPayload = json_decode(json_encode($first->toArray(), JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
    $validation = (new ContractSchemaRegistry)->validator()->validate($resultPayload, ContractSchemaRegistry::Result);

    expect($first->status)->toBe(DocumentCompilationStatus::Succeeded)
        ->and($first->output?->inlineContent)->toBe($second->output?->inlineContent)
        ->and($first->output?->checksum)->toBe($second->output?->checksum)
        ->and($first->output?->byteLength)->toBe(strlen($first->output?->inlineContent ?? ''))
        ->and($first->output?->checksum)->toBe('sha256:'.hash('sha256', $first->output?->inlineContent ?? ''))
        ->and($first->metadata['output_identity'])->toBe($first->output?->checksum)
        ->and($first->metadata['request_fingerprint'])->toBe($request->requestFingerprint)
        ->and($decoded)->toEqual($expected)
        ->and($decoded['document']['evidence'])->toEqual($expected['document']['evidence'])
        ->and($decoded['document']['actions'])->toEqual($expected['document']['actions'])
        ->and($decoded['document']['attachments'])->toEqual($expected['document']['attachments'])
        ->and($validation->isValid())->toBeTrue();
})->with([
    'invoice' => ['invoice-request.json'],
    'receipt' => ['receipt-request.json'],
    'reservation certificate' => ['reservation-certificate-request.json'],
]);

it('canonicalizes object key order while preserving list order', function () {
    $firstPayload = json_decode(jsonDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $secondPayload = json_decode(jsonDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $firstPayload->document->metadata = (object) ['zeta' => 2, 'alpha' => 1];
    $secondPayload->document->metadata = (object) ['alpha' => 1, 'zeta' => 2];
    $firstRequest = DocumentCompilationRequest::fromObject($firstPayload);
    $secondRequest = DocumentCompilationRequest::fromObject($secondPayload);

    $firstOutput = (new JsonDocumentDriver)->compile($firstRequest)->output?->inlineContent;
    $secondOutput = (new JsonDocumentDriver)->compile($secondRequest)->output?->inlineContent;
    $decoded = json_decode($firstOutput ?? '', true, flags: JSON_THROW_ON_ERROR);

    expect($firstOutput)->toBe($secondOutput)
        ->and($decoded['document']['audience'])->toBe($firstPayload->document->audience);
});

it('propagates a generated result schema violation as an integration defect', function () {
    $payload = json_decode(jsonDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $payload->request_identifier = 'invalid';
    $request = DocumentCompilationRequest::fromObject($payload);
    $result = DocumentCompilationResult::unsupported($request, 'json', ['Unsupported request.']);

    (new ValidateDocumentCompilationResult)->handle($result);
})->throws(InvalidDocumentCompilationResult::class);

it('propagates unexpected serialization defects', function () {
    $payload = json_decode(jsonDriverFixture(), false, flags: JSON_THROW_ON_ERROR);
    $resource = fopen('php://memory', 'r');
    $payload->document->metadata->unexpected = $resource;
    $request = DocumentCompilationRequest::fromObject($payload);

    try {
        (new JsonDocumentDriver)->compile($request);
    } finally {
        if (is_resource($resource)) {
            fclose($resource);
        }
    }
})->throws(JsonException::class);
