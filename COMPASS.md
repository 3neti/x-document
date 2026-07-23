# x-document Compass

**Current milestone:** Contract Compatibility Harness

**North star:** Faithfully express already-resolved document meaning through independent projection drivers.

## Completed

The standalone Composer package, reviewed contract `1.0`, portable DTOs, strict validation, minimal drivers, canonical JSON proof driver, versioned asset manifest, deterministic integrity verifier, optional producer-snapshot comparison, and drift tests are established.

## Immediate direction

Harden the JSON proof driver’s explicit output guarantees now that contract drift can be detected independently.

## Explicit deferrals

Browser, HTML, Markdown, Print, Email, and PDF implementations; Adobe; AcroForms; XFDF; signatures; synchronization; batch compilation; storage; queues; remote execution; webhooks; binary transport; editing; OCR; AI; settlement; x-change; action execution.

## Known risks

The package currently supports one frozen contract version and one proof driver. Producer snapshots must be supplied out of band; the harness intentionally performs no network fetch. It compares exact bytes and does not explain semantic compatibility across different contract versions.

## Recommended next task

**JSON Driver Hardening** — formalize output/error guarantees and expand driver-specific regressions without introducing rendering.
