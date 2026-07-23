<?php

use LBHurtado\XDocument\Contract\ContractSchemaRegistry;
use LBHurtado\XDocument\Contract\ContractVersion;
use LBHurtado\XDocument\Contract\DocumentCompilationStatus;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationRequest;
use LBHurtado\XDocument\Drivers\JsonDocumentDriver;
use LBHurtado\XDocument\Exceptions\InvalidDocumentContract;
use LBHurtado\XDocument\Exceptions\UnsupportedContractVersion;

function fixtureJson(string $fixture): string
{
    return file_get_contents(dirname(__DIR__).'/Fixtures/Contract/1.0/'.$fixture) ?: throw new RuntimeException("Fixture {$fixture} is unavailable.");
}

it('validates each approved contract fixture independently', function (string $fixture) {
    $json = fixtureJson($fixture);
    $request = (new ValidateDocumentCompilationRequest)->handleJson($json);
    $payload = json_decode($json, false, flags: JSON_THROW_ON_ERROR);
    $documentResult = (new ContractSchemaRegistry)->validator()->validate($payload->document, ContractSchemaRegistry::ResolvedDocument);

    expect($request->contractVersion->value)->toBe(ContractVersion::Version1)
        ->and($request->document->identifier)->toBeString()->not->toBeEmpty()
        ->and($documentResult->isValid())->toBeTrue();
})->with([
    'invoice' => ['invoice-request.json'],
    'receipt' => ['receipt-request.json'],
    'reservation certificate' => ['reservation-certificate-request.json'],
]);

it('passes each fixture through the JSON driver without changing its contract meaning', function (string $fixture) {
    $request = (new ValidateDocumentCompilationRequest)->handleJson(fixtureJson($fixture));
    $result = (new JsonDocumentDriver)->compile($request);
    $compiled = json_decode($result->output?->inlineContent ?? '', true, flags: JSON_THROW_ON_ERROR);
    $expected = json_decode(fixtureJson($fixture), true, flags: JSON_THROW_ON_ERROR);
    $resultPayload = json_decode(json_encode($result->toArray(), JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
    $resultValidation = (new ContractSchemaRegistry)->validator()->validate($resultPayload, ContractSchemaRegistry::Result);

    expect($result->driver)->toBe('json')
        ->and($result->status)->toBe(DocumentCompilationStatus::Succeeded)
        ->and($compiled)->toBe($expected)
        ->and($resultValidation->isValid())->toBeTrue();
})->with([
    'invoice' => ['invoice-request.json'],
    'receipt' => ['receipt-request.json'],
    'reservation certificate' => ['reservation-certificate-request.json'],
]);

it('serializes and compiles deterministically', function () {
    $request = (new ValidateDocumentCompilationRequest)->handleJson(fixtureJson('invoice-request.json'));
    $driver = new JsonDocumentDriver;

    expect($request->toJson())->toBe($request->toJson())
        ->and(json_encode($driver->compile($request)->toArray(), JSON_THROW_ON_ERROR))
        ->toBe(json_encode($driver->compile($request)->toArray(), JSON_THROW_ON_ERROR));
});

it('rejects unsupported contract versions explicitly', function () {
    $payload = json_decode(fixtureJson('invoice-request.json'), true, flags: JSON_THROW_ON_ERROR);
    $payload['contract_version'] = '2.0';

    (new ValidateDocumentCompilationRequest)->handleJson(json_encode($payload, JSON_THROW_ON_ERROR));
})->throws(UnsupportedContractVersion::class);

it('rejects malformed requests without coercion', function (Closure $mutate) {
    $payload = json_decode(fixtureJson('invoice-request.json'), true, flags: JSON_THROW_ON_ERROR);
    $mutate($payload);

    (new ValidateDocumentCompilationRequest)->handleJson(json_encode($payload, JSON_THROW_ON_ERROR));
})->with([
    'missing document' => [function (array &$payload): void {
        unset($payload['document']);
    }],
    'invalid integer value' => [function (array &$payload): void {
        $payload['document']['sections'][0]['fields'][0]['value'] = ['type' => 'integer', 'value' => 'five'];
    }],
    'unknown core key' => [function (array &$payload): void {
        $payload['repository'] = 'forbidden';
    }],
])->throws(InvalidDocumentContract::class);

it('rejects malformed JSON', function () {
    (new ValidateDocumentCompilationRequest)->handleJson('{invalid');
})->throws(InvalidDocumentContract::class);

it('uses a registry that resolves the canonical document schema reference', function () {
    $requestSchema = json_decode(file_get_contents(dirname(__DIR__, 2).'/resources/contracts/x-document/1.0/compilation-request.schema.json'), false, flags: JSON_THROW_ON_ERROR);

    expect($requestSchema)->toBeInstanceOf(stdClass::class)
        ->and($requestSchema->properties->document->{'$ref'})->toBe(ContractSchemaRegistry::ResolvedDocument)
        ->and(property_exists($requestSchema, '$defs'))->toBeFalse();
});
