<?php

use LBHurtado\XDocument\Compatibility\VerifyContractCompatibility;
use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\ContractSchemaRegistry;

function packageRoot(): string
{
    return dirname(__DIR__, 2);
}

function createContractCopy(): string
{
    $target = sys_get_temp_dir().'/x-document-contract-'.bin2hex(random_bytes(8));
    $paths = [
        'resources/contracts/x-document/1.0',
        'tests/Fixtures/Contract/1.0',
    ];
    foreach ($paths as $path) {
        $sourceDirectory = packageRoot().'/'.$path;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS)) as $source) {
            if (! $source instanceof SplFileInfo || ! $source->isFile()) {
                continue;
            }
            $relative = substr($source->getPathname(), strlen(packageRoot()) + 1);
            $destination = $target.'/'.$relative;
            if (! is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0777, true);
            }
            copy($source->getPathname(), $destination);
        }
    }

    return $target;
}

function removeContractCopy(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo) {
            continue;
        }
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($directory);
}

it('verifies local contract integrity deterministically without requiring a snapshot', function () {
    $verifier = new VerifyContractCompatibility;

    $first = $verifier->handle();
    $second = $verifier->handle();

    expect($first->isCompatible())->toBeTrue()
        ->and($first->contract)->toBe('1.0')
        ->and($first->snapshot)->toBe(['status' => 'not_supplied', 'path' => null])
        ->and($first->registry['status'])->toBe('verified')
        ->and($first->toJson())->toBe($second->toJson())
        ->and($first->differences)->toBe([]);
});

it('declares every schema and compatibility fixture with matching checksums', function () {
    $manifest = json_decode(
        file_get_contents(packageRoot().'/resources/contracts/x-document/1.0/manifest.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach ([...$manifest['schemas'], ...$manifest['fixtures']] as $asset) {
        expect(hash_file('sha256', packageRoot().'/'.$asset['file']))->toBe($asset['sha256']);
    }

    expect(array_column($manifest['schemas'], 'id'))
        ->toEqualCanonicalizing(array_keys((new ContractSchemaRegistry)->schemas()));
});

it('keeps every fixture in canonical byte form', function (string $fixture) {
    $path = packageRoot().'/tests/Fixtures/Contract/1.0/'.$fixture;
    $decoded = json_decode(file_get_contents($path), false, flags: JSON_THROW_ON_ERROR);

    expect(file_get_contents($path))->toBe((new CanonicalJson)->encode($decoded, pretty: true).PHP_EOL);
})->with([
    'invoice' => ['invoice-request.json'],
    'receipt' => ['receipt-request.json'],
    'reservation certificate' => ['reservation-certificate-request.json'],
]);

it('detects modified and missing contract assets without repairing them', function () {
    $copy = createContractCopy();
    file_put_contents($copy.'/resources/contracts/x-document/1.0/compilation-request.schema.json', "{}\n");
    unlink($copy.'/tests/Fixtures/Contract/1.0/receipt-request.json');

    try {
        $report = (new VerifyContractCompatibility(packageRoot: $copy))->handle();
        $codes = array_column($report->differences, 'code');

        expect($report->isCompatible())->toBeFalse()
            ->and($codes)->toContain('SCHEMA_CHECKSUM_MISMATCH', 'SCHEMA_ID_MISMATCH', 'FIXTURE_MISSING')
            ->and(file_get_contents($copy.'/resources/contracts/x-document/1.0/compilation-request.schema.json'))->toBe("{}\n")
            ->and(is_file($copy.'/tests/Fixtures/Contract/1.0/receipt-request.json'))->toBeFalse();
    } finally {
        removeContractCopy($copy);
    }
});

it('detects duplicate manifest identities and orphaned assets', function () {
    $copy = createContractCopy();
    $manifestPath = $copy.'/resources/contracts/x-document/1.0/manifest.json';
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $manifest['schemas'][1]['id'] = $manifest['schemas'][0]['id'];
    file_put_contents($manifestPath, (new CanonicalJson)->encode($manifest, pretty: true).PHP_EOL);
    file_put_contents($copy.'/resources/contracts/x-document/1.0/orphan.schema.json', "{}\n");

    try {
        $report = (new VerifyContractCompatibility(packageRoot: $copy))->handle();
        $codes = array_column($report->differences, 'code');

        expect($report->isCompatible())->toBeFalse()
            ->and($codes)->toContain('SCHEMA_ID_DUPLICATED', 'SCHEMA_ID_MISMATCH', 'SCHEMA_REGISTRY_MISMATCH', 'CONTRACT_ASSET_ORPHANED');
    } finally {
        removeContractCopy($copy);
    }
});

it('rejects manifest paths that escape the package snapshot', function () {
    $copy = createContractCopy();
    $manifestPath = $copy.'/resources/contracts/x-document/1.0/manifest.json';
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $manifest['fixtures'][0]['file'] = '../../outside.json';
    file_put_contents($manifestPath, (new CanonicalJson)->encode($manifest, pretty: true).PHP_EOL);

    try {
        $report = (new VerifyContractCompatibility(packageRoot: $copy))->handle();

        expect($report->isCompatible())->toBeFalse()
            ->and(array_column($report->differences, 'code'))->toContain('MANIFEST_PATH_UNSAFE');
    } finally {
        removeContractCopy($copy);
    }
});

it('detects schema references that the registry cannot resolve', function () {
    $copy = createContractCopy();
    $schemaPath = $copy.'/resources/contracts/x-document/1.0/compilation-result.schema.json';
    $schema = json_decode(file_get_contents($schemaPath), true, flags: JSON_THROW_ON_ERROR);
    $schema['properties']['unknown_contract'] = ['$ref' => 'https://3neti.dev/contracts/x-document/1.0/unknown.schema.json'];
    file_put_contents($schemaPath, (new CanonicalJson)->encode($schema, pretty: true).PHP_EOL);

    try {
        $report = (new VerifyContractCompatibility(packageRoot: $copy))->handle();

        expect($report->isCompatible())->toBeFalse()
            ->and(array_column($report->differences, 'code'))->toContain('SCHEMA_REFERENCE_UNKNOWN');
    } finally {
        removeContractCopy($copy);
    }
});

it('accepts an identical optional upstream snapshot', function () {
    $snapshot = createContractCopy();

    try {
        $report = (new VerifyContractCompatibility)->handle($snapshot);

        expect($report->isCompatible())->toBeTrue()
            ->and($report->snapshot)->toBe(['status' => 'compatible', 'path' => $snapshot]);
    } finally {
        removeContractCopy($snapshot);
    }
});

it('reports byte drift in an upstream snapshot', function () {
    $snapshot = createContractCopy();
    file_put_contents($snapshot.'/tests/Fixtures/Contract/1.0/invoice-request.json', "{}\n");

    try {
        $report = (new VerifyContractCompatibility)->handle($snapshot);

        expect($report->isCompatible())->toBeFalse()
            ->and($report->snapshot['status'])->toBe('different')
            ->and(array_column($report->differences, 'code'))->toContain('SNAPSHOT_ASSET_DIFFERENT');
    } finally {
        removeContractCopy($snapshot);
    }
});

it('keeps compatibility verification isolated from driver execution', function () {
    $driverSource = file_get_contents(packageRoot().'/src/Drivers/JsonDocumentDriver.php');

    expect($driverSource)->not->toContain('Compatibility', 'VerifyContractCompatibility', 'manifest.json');
});
