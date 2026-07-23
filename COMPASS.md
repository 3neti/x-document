# x-document Compass

**Current milestone:** Package Bootstrap

**North star:** Faithfully express already-resolved document meaning through independent projection drivers.

## Completed

The standalone Composer package, `LBHurtado\XDocument` namespace, reviewed contract `1.0`, portable DTOs, explicit schema registry, strict validation, minimal driver contracts, canonical JSON driver, fixtures, and architecture tests are established.

## Immediate direction

Prove producer-consumer compatibility can be checked automatically without copying implementation code or introducing runtime coupling.

## Explicit deferrals

Browser, HTML, Markdown, Print, Email, and PDF implementations; Adobe; AcroForms; XFDF; signatures; synchronization; batch compilation; storage; queues; remote execution; webhooks; binary transport; editing; OCR; AI; settlement; x-change; action execution.

## Known risks

The package currently supports one contract version and one proof driver. Nested resolved-document data is intentionally represented as validated portable JSON rather than a large graph of redundant PHP DTOs. Capability negotiation and external contract distribution are not yet automated.

## Recommended next task

**x-document Contract Compatibility Harness** — automate parity checks between producer-published schemas/fixtures and the package-installed contract without adding a runtime dependency on GNE.
