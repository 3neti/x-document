# 3neti/x-document

`3neti/x-document` is a driver-neutral compiler for already-resolved document meaning. It begins at a validated external contract and produces document projections.

```text
Resolved Document Contract
        ↓
Document Driver
        ↓
Document Projection
```

GNE determines what a document means. x-document determines how that meaning is expressed. This package does not interpret repositories, select artifacts, evaluate lifecycles, execute business actions, or perform settlement.

## Maturity

This is an architectural bootstrap, not a web application. Contract `1.0`, portable DTOs, validation, the JSON reference driver, the read-only browser projection driver, its deterministic HTML adapter, and an optional compatibility harness are implemented. PDF remains an interface only.

## Installation

```bash
composer require 3neti/x-document
```

Laravel 13 can consume the package through normal Composer autoloading. No service provider, database, routes, configuration, or framework boot process is required.

## Usage

```php
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationRequest;
use LBHurtado\XDocument\Drivers\JsonDocumentDriver;

$request = (new ValidateDocumentCompilationRequest)->handleJson($contractJson);
$result = (new JsonDocumentDriver)->compile($request);
```

The JSON driver returns the complete canonical request as inline `application/json`; it does not render layout. It declares the closed-contract capabilities `actions`, `attachments`, and `evidence`, rejects requests targeted to another driver, and returns `unsupported` when any requested capability is unavailable.

The browser driver mechanically maps resolved meaning into portable, frontend-independent JSON:

```php
use LBHurtado\XDocument\Drivers\BrowserDocumentDriver;

$result = (new BrowserDocumentDriver)->compile($browserRequest);
```

Its output media type is `application/vnd.3neti.x-document.browser+json` and its format is `browser/1.0`. It preserves canonical values, derives conservative display strings, retains subject/evidence/action/attachment declarations, and emits stable section and field identities. It produces no HTML, UI, routes, forms, or executable actions.

HTML is a separate expression over the browser projection:

```php
use LBHurtado\XDocument\Browser\Html\BrowserHtmlProjectionAdapter;
use LBHurtado\XDocument\Projection\Browser\BuildBrowserProjection;

$projection = (new BuildBrowserProjection)->handle($browserRequest);
$htmlOutput = (new BrowserHtmlProjectionAdapter)->adapt($projection);
```

The adapter emits complete `browser-html/1.0` documents as inline `text/html; charset=utf-8`. Output has fixed whitespace, semantic read-only structure, stable IDs, escaped text, an exact checksum, and no CSS, JavaScript, links, controls, repository access, or business interpretation.

Successful results are created through invariant-safe factories, validated against the result schema, and include a SHA-256 checksum that serves as the deterministic output identity:

```php
$result->status;                   // DocumentCompilationStatus::Succeeded
$result->output?->checksum;        // sha256:<canonical-output-bytes>
$result->output?->byteLength;      // exact byte length
```

`DocumentCompilationResult::succeeded()`, `unsupported()`, and `failed()` prevent invalid status/output combinations. The JSON driver currently has no classified operational failure: unsupported requests become results, while serialization defects and unexpected implementation failures propagate.

## Development

```bash
composer install
composer compatibility
composer test
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
composer validate --strict
```

`composer compatibility` verifies the installed manifest, asset checksums, schema registry, schema-valid fixtures, and canonical fixture bytes. It does not require GNE. An exported producer snapshot can be compared without runtime coupling:

```bash
composer compatibility -- --snapshot=/path/to/snapshot
composer compatibility -- --json
```

The snapshot root mirrors the package-relative paths listed by `resources/contracts/x-document/1.0/manifest.json`. No snapshot is a successful, explicitly reported local-integrity run.

Canonical project documents: [ARCHITECTURE.md](ARCHITECTURE.md), [GRAMMAR.md](GRAMMAR.md), [DECISION_REGISTER.md](DECISION_REGISTER.md), [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md), and [COMPASS.md](COMPASS.md).
