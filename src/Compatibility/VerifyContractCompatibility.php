<?php

namespace LBHurtado\XDocument\Compatibility;

use JsonException;
use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\ContractSchemaRegistry;
use stdClass;

final readonly class VerifyContractCompatibility
{
    private const ManifestPath = 'resources/contracts/x-document/1.0/manifest.json';

    public function __construct(
        private ?string $packageRoot = null,
        private ?ContractSchemaRegistry $schemaRegistry = null,
        private ?CanonicalJson $canonicalJson = null,
    ) {}

    public function handle(?string $snapshotRoot = null): ContractCompatibilityReport
    {
        $packageRoot = $this->packageRoot ?? dirname(__DIR__, 2);
        $schemaRegistry = $this->schemaRegistry ?? new ContractSchemaRegistry;
        $canonicalJson = $this->canonicalJson ?? new CanonicalJson;
        $differences = [];
        $manifest = $this->loadManifest($packageRoot, $differences);

        if ($manifest === null) {
            return new ContractCompatibilityReport(
                contract: 'unknown',
                schemas: [],
                fixtures: [],
                registry: ['status' => 'unverified', 'schema_ids' => []],
                snapshot: $this->snapshotStatus($snapshotRoot, 'not_compared'),
                differences: $this->sortedDifferences($differences),
            );
        }

        $contractVersion = $this->stringValue($manifest, 'contract_version', $differences, self::ManifestPath);
        $schemaEntries = $this->assetEntries($manifest, 'schemas', $differences);
        $fixtureEntries = $this->assetEntries($manifest, 'fixtures', $differences);
        $schemas = $this->verifySchemas($packageRoot, $schemaEntries, $schemaRegistry, $differences);
        $fixtures = $this->verifyFixtures($packageRoot, $fixtureEntries, $schemaRegistry, $canonicalJson, $differences);
        $registry = $this->verifyRegistry($schemaEntries, $schemaRegistry, $differences);
        $this->verifyNoOrphans($packageRoot, $schemaEntries, $fixtureEntries, $differences);
        $snapshot = $this->compareSnapshot($packageRoot, $snapshotRoot, $schemaEntries, $fixtureEntries, $differences);

        return new ContractCompatibilityReport(
            contract: $contractVersion ?? 'unknown',
            schemas: $schemas,
            fixtures: $fixtures,
            registry: $registry,
            snapshot: $snapshot,
            differences: $this->sortedDifferences($differences),
        );
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function loadManifest(string $packageRoot, array &$differences): ?stdClass
    {
        $manifestPath = $packageRoot.'/'.self::ManifestPath;
        if (! is_file($manifestPath)) {
            $this->difference($differences, 'MANIFEST_MISSING', self::ManifestPath, 'The contract manifest is missing or unreadable.');

            return null;
        }
        $contents = file_get_contents($manifestPath);
        if ($contents === false) {
            $this->difference($differences, 'MANIFEST_MISSING', self::ManifestPath, 'The contract manifest is missing or unreadable.');

            return null;
        }

        try {
            $manifest = json_decode($contents, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->difference($differences, 'MANIFEST_MALFORMED', self::ManifestPath, $exception->getMessage());

            return null;
        }

        if (! $manifest instanceof stdClass) {
            $this->difference($differences, 'MANIFEST_INVALID', self::ManifestPath, 'The contract manifest must be a JSON object.');

            return null;
        }

        return $manifest;
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function stringValue(stdClass $object, string $property, array &$differences, string $path): ?string
    {
        $value = $object->{$property} ?? null;
        if (! is_string($value) || $value === '') {
            $this->difference($differences, 'MANIFEST_INVALID', $path, "{$property} must be a non-empty string.");

            return null;
        }

        return $value;
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return list<stdClass>
     */
    private function assetEntries(stdClass $manifest, string $property, array &$differences): array
    {
        $entries = $manifest->{$property} ?? null;
        if (! is_array($entries)) {
            $this->difference($differences, 'MANIFEST_INVALID', self::ManifestPath, "{$property} must be an array.");

            return [];
        }

        $validEntries = [];
        foreach ($entries as $entry) {
            if (! $entry instanceof stdClass) {
                $this->difference($differences, 'MANIFEST_INVALID', self::ManifestPath, "Each {$property} entry must be an object.");

                continue;
            }
            $validEntries[] = $entry;
        }

        return $validEntries;
    }

    /**
     * @param  list<stdClass>  $entries
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return list<array{file: string, id: string, sha256: string, status: string}>
     */
    private function verifySchemas(string $packageRoot, array $entries, ContractSchemaRegistry $registry, array &$differences): array
    {
        $schemas = [];
        $seenFiles = [];
        $seenIds = [];
        foreach ($entries as $entry) {
            $file = $this->entryString($entry, 'file', 'schema', $differences);
            $id = $this->entryString($entry, 'id', 'schema', $differences);
            $checksum = $this->entryString($entry, 'sha256', 'schema', $differences);
            if ($file === null || $id === null || $checksum === null) {
                continue;
            }
            $this->detectDuplicate($seenFiles, $file, 'SCHEMA_FILE_DUPLICATED', $differences);
            $this->detectDuplicate($seenIds, $id, 'SCHEMA_ID_DUPLICATED', $differences);
            $status = $this->verifyAsset($packageRoot, $file, $checksum, 'SCHEMA', $differences);
            $schema = $this->decodeObject($packageRoot, $file, 'SCHEMA_MALFORMED', $differences);
            if ($schema !== null && ($schema->{'$id'} ?? null) !== $id) {
                $this->difference($differences, 'SCHEMA_ID_MISMATCH', $file, 'The schema $id does not match its manifest ID.');
                $status = 'invalid';
            }
            if ($schema !== null) {
                foreach ($this->externalSchemaReferences($schema) as $reference) {
                    if (! array_key_exists($reference, $registry->schemas())) {
                        $this->difference($differences, 'SCHEMA_REFERENCE_UNKNOWN', $file, "Schema reference is not registered: {$reference}");
                        $status = 'invalid';
                    }
                }
            }
            $schemas[] = ['file' => $file, 'id' => $id, 'sha256' => $checksum, 'status' => $status];
        }

        usort($schemas, fn (array $left, array $right): int => $left['file'] <=> $right['file']);

        return $schemas;
    }

    /**
     * @param  list<stdClass>  $entries
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return list<array{file: string, name: string, sha256: string, status: string}>
     */
    private function verifyFixtures(string $packageRoot, array $entries, ContractSchemaRegistry $registry, CanonicalJson $canonicalJson, array &$differences): array
    {
        $fixtures = [];
        $seenFiles = [];
        $seenNames = [];
        foreach ($entries as $entry) {
            $file = $this->entryString($entry, 'file', 'fixture', $differences);
            $name = $this->entryString($entry, 'name', 'fixture', $differences);
            $checksum = $this->entryString($entry, 'sha256', 'fixture', $differences);
            if ($file === null || $name === null || $checksum === null) {
                continue;
            }
            $this->detectDuplicate($seenFiles, $file, 'FIXTURE_FILE_DUPLICATED', $differences);
            $this->detectDuplicate($seenNames, $name, 'FIXTURE_NAME_DUPLICATED', $differences);
            $status = $this->verifyAsset($packageRoot, $file, $checksum, 'FIXTURE', $differences);
            $fixture = $this->decodeObject($packageRoot, $file, 'FIXTURE_MALFORMED', $differences);
            if ($fixture !== null) {
                $requestResult = $registry->validator()->validate($fixture, ContractSchemaRegistry::Request);
                $document = $fixture->document ?? null;
                $documentResult = $registry->validator()->validate($document, ContractSchemaRegistry::ResolvedDocument);
                if (! $requestResult->isValid() || ! $documentResult->isValid()) {
                    $this->difference($differences, 'FIXTURE_SCHEMA_INVALID', $file, 'The fixture does not validate against the request and resolved-document schemas.');
                    $status = 'invalid';
                }
                $contents = file_get_contents($packageRoot.'/'.$file);
                $canonical = $canonicalJson->encode($fixture, pretty: true).PHP_EOL;
                if ($contents !== $canonical) {
                    $this->difference($differences, 'FIXTURE_NOT_CANONICAL', $file, 'Fixture bytes differ from canonical JSON serialization.');
                    $status = 'invalid';
                }
            }
            $fixtures[] = ['file' => $file, 'name' => $name, 'sha256' => $checksum, 'status' => $status];
        }

        usort($fixtures, fn (array $left, array $right): int => $left['file'] <=> $right['file']);

        return $fixtures;
    }

    /**
     * @param  list<stdClass>  $schemaEntries
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return array{status: string, schema_ids: list<string>}
     */
    private function verifyRegistry(array $schemaEntries, ContractSchemaRegistry $registry, array &$differences): array
    {
        $manifestIds = [];
        foreach ($schemaEntries as $entry) {
            if (isset($entry->id) && is_string($entry->id)) {
                $manifestIds[] = $entry->id;
            }
        }
        $registryIds = array_keys($registry->schemas());
        sort($manifestIds, SORT_STRING);
        sort($registryIds, SORT_STRING);
        $status = $manifestIds === $registryIds ? 'verified' : 'invalid';
        if ($status === 'invalid') {
            $this->difference($differences, 'SCHEMA_REGISTRY_MISMATCH', self::ManifestPath, 'Manifest schema IDs differ from the installed registry.');
        }

        return ['status' => $status, 'schema_ids' => $registryIds];
    }

    /**
     * @param  list<stdClass>  $schemaEntries
     * @param  list<stdClass>  $fixtureEntries
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function verifyNoOrphans(string $packageRoot, array $schemaEntries, array $fixtureEntries, array &$differences): void
    {
        $declared = [];
        foreach ([...$schemaEntries, ...$fixtureEntries] as $entry) {
            if (isset($entry->file) && is_string($entry->file)) {
                $declared[] = $entry->file;
            }
        }
        $expected = [
            ...glob($packageRoot.'/resources/contracts/x-document/1.0/*.schema.json') ?: [],
            ...glob($packageRoot.'/tests/Fixtures/Contract/1.0/*.json') ?: [],
        ];
        foreach ($expected as $absolutePath) {
            $relativePath = substr($absolutePath, strlen($packageRoot) + 1);
            if (! in_array($relativePath, $declared, true)) {
                $this->difference($differences, 'CONTRACT_ASSET_ORPHANED', $relativePath, 'Contract asset is not declared by the manifest.');
            }
        }
    }

    /**
     * @param  list<stdClass>  $schemaEntries
     * @param  list<stdClass>  $fixtureEntries
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return array{status: string, path: ?string}
     */
    private function compareSnapshot(string $packageRoot, ?string $snapshotRoot, array $schemaEntries, array $fixtureEntries, array &$differences): array
    {
        if ($snapshotRoot === null) {
            return ['status' => 'not_supplied', 'path' => null];
        }
        $snapshotRoot = rtrim($snapshotRoot, DIRECTORY_SEPARATOR);
        $paths = [self::ManifestPath];
        foreach ([...$schemaEntries, ...$fixtureEntries] as $entry) {
            if (isset($entry->file) && is_string($entry->file)) {
                $paths[] = $entry->file;
            }
        }
        sort($paths, SORT_STRING);
        foreach (array_unique($paths) as $path) {
            $localPath = $packageRoot.'/'.$path;
            $upstreamPath = $snapshotRoot.'/'.$path;
            if (! is_file($localPath)) {
                continue;
            }
            if (! is_file($upstreamPath)) {
                $this->difference($differences, 'SNAPSHOT_ASSET_MISSING', $path, 'The upstream snapshot does not contain this contract asset.');
            } elseif (file_get_contents($localPath) !== file_get_contents($upstreamPath)) {
                $this->difference($differences, 'SNAPSHOT_ASSET_DIFFERENT', $path, 'The upstream snapshot bytes differ from the installed contract.');
            }
        }

        return ['status' => $this->hasSnapshotDifference($differences) ? 'different' : 'compatible', 'path' => $snapshotRoot];
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function hasSnapshotDifference(array $differences): bool
    {
        foreach ($differences as $difference) {
            if (str_starts_with($difference['code'], 'SNAPSHOT_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function entryString(stdClass $entry, string $property, string $kind, array &$differences): ?string
    {
        $value = $entry->{$property} ?? null;
        if (! is_string($value) || $value === '') {
            $this->difference($differences, 'MANIFEST_INVALID', self::ManifestPath, "Each {$kind} {$property} must be a non-empty string.");

            return null;
        }
        if ($property === 'file' && ! $this->isSafeRelativePath($value)) {
            $this->difference($differences, 'MANIFEST_PATH_UNSAFE', self::ManifestPath, "Unsafe package-relative path: {$value}");

            return null;
        }

        return $value;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return false;
        }

        return ! in_array('..', preg_split('/[\\\\\/]+/', $path) ?: [], true);
    }

    /**
     * @param  list<string>  $seen
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function detectDuplicate(array &$seen, string $value, string $code, array &$differences): void
    {
        if (in_array($value, $seen, true)) {
            $this->difference($differences, $code, self::ManifestPath, "Duplicate manifest value: {$value}");
        }
        $seen[] = $value;
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function verifyAsset(string $packageRoot, string $file, string $checksum, string $kind, array &$differences): string
    {
        $absolutePath = $packageRoot.'/'.$file;
        if (! is_file($absolutePath)) {
            $this->difference($differences, "{$kind}_MISSING", $file, 'The manifest asset is missing.');

            return 'missing';
        }
        if (! preg_match('/^[a-f0-9]{64}$/', $checksum)) {
            $this->difference($differences, "{$kind}_CHECKSUM_INVALID", $file, 'The manifest checksum is not a lowercase SHA-256 digest.');

            return 'invalid';
        }
        if (hash_file('sha256', $absolutePath) !== $checksum) {
            $this->difference($differences, "{$kind}_CHECKSUM_MISMATCH", $file, 'Asset bytes do not match the manifest checksum.');

            return 'different';
        }

        return 'verified';
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function decodeObject(string $packageRoot, string $file, string $code, array &$differences): ?stdClass
    {
        $absolutePath = $packageRoot.'/'.$file;
        if (! is_file($absolutePath)) {
            return null;
        }
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }
        try {
            $decoded = json_decode($contents, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->difference($differences, $code, $file, $exception->getMessage());

            return null;
        }
        if (! $decoded instanceof stdClass) {
            $this->difference($differences, $code, $file, 'Contract JSON must decode to an object.');

            return null;
        }

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function externalSchemaReferences(mixed $value): array
    {
        $references = [];
        if ($value instanceof stdClass) {
            foreach (get_object_vars($value) as $key => $item) {
                if ($key === '$ref' && is_string($item) && ! str_starts_with($item, '#')) {
                    $references[] = $item;
                }
                $references = [...$references, ...$this->externalSchemaReferences($item)];
            }
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                $references = [...$references, ...$this->externalSchemaReferences($item)];
            }
        }

        sort($references, SORT_STRING);

        return array_values(array_unique($references));
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     */
    private function difference(array &$differences, string $code, ?string $path, string $message): void
    {
        $differences[] = ['code' => $code, 'path' => $path, 'message' => $message];
    }

    /**
     * @param  list<array{code: string, path: ?string, message: string}>  $differences
     * @return list<array{code: string, path: ?string, message: string}>
     */
    private function sortedDifferences(array $differences): array
    {
        usort($differences, fn (array $left, array $right): int => [$left['path'], $left['code'], $left['message']] <=> [$right['path'], $right['code'], $right['message']]);

        return $differences;
    }

    /**
     * @return array{status: string, path: ?string}
     */
    private function snapshotStatus(?string $snapshotRoot, string $status): array
    {
        return ['status' => $snapshotRoot === null ? 'not_supplied' : $status, 'path' => $snapshotRoot];
    }
}
