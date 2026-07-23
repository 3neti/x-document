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

This is an architectural bootstrap, not a rendering product. Contract `1.0`, portable DTOs, validation, the driver boundary, the JSON proof driver, and an optional compatibility harness are implemented. Browser and PDF are interfaces only.

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

The JSON driver returns the canonical request as `application/json`; it does not render layout.

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
