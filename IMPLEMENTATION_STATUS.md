# Implementation Status

| Capability | Implementation | Tests | Limitations | Status | Next action |
|---|---|---|---|---|---|
| Contract 1.0 | Reviewed request, resolved-document, and result schemas with local stable-ID registry | Fixture and schema tests | Version 1.0 only | Implemented | Preserve compatibility |
| Portable DTOs | Request, resolved document, result, output, and version objects | Unit tests | Deliberately retain validated nested payload | Implemented | Evolve only with contract versioning |
| Validation | Opis schema validation with explicit unsupported-version failure | Positive and negative tests | No downgrade negotiation | Implemented | Keep strict |
| Driver contract | Stable name, deterministic capabilities, and compile operation; JSON and Browser are independent peers; PDF remains interface-only | Architecture and capability tests | No registry or package discovery | Implemented | Preserve independence |
| Result model | Backed status enum and named succeeded/unsupported/failed factories with invariant-safe output ownership | Factory and negative tests | Contract 1.0 warnings remain strings | Implemented | Keep schema-compatible |
| Output model | Private construction with inline/referenced factories, safe references, parameterized media types, derived inline checksum and byte length | Invariant and negative tests | No binary content or persistence | Implemented | Keep transport-only |
| JSON driver | Complete canonical request round-trip, target/capability checks, deterministic checksum identity, and result-schema validation | Three-fixture semantic and deterministic tests | No formatting, layout, or classified operational failure | Implemented | Reference future drivers |
| Contract manifest | Contract `1.0` schema IDs, package-relative assets, and exact SHA-256 checksums | Integrity and drift tests | One frozen version | Implemented | Change only deliberately |
| Compatibility harness | Deterministic local integrity report and optional package-shaped snapshot comparison | Missing, modified, duplicate, orphan, canonicalization, registry, and snapshot tests | Snapshot supplied manually; byte compatibility only | Implemented | Run in CI |
| Browser projection | Read-only `browser/1.0` DTO model, builder, schema/manifest, deterministic IDs, canonical/display values, and vendor JSON driver output | Three-fixture semantic, schema, identity, ordering, and negative tests | No UI, localization, editing, or action execution | Implemented | Preserve as browser-facing IR |
| Browser HTML adapter | Complete `browser-html/1.0` semantic HTML over validated browser projection with escaping, stable IDs, inert declarations, exact output identity, and snapshots | Three snapshots, security, structure, attachment, null-value, filename, validation, and architecture tests | Fixed English language; no CSS, JavaScript, links, localization, UI, or editing | Implemented | Define styling separately |
| PDF rendering | Interface only | Architecture checks | No Adobe, AcroForms, XFDF, or PDF library | Deferred | Keep deferred |
| Storage/execution | None | Architecture checks | No queues, persistence, webhooks, binary transport, or action execution | Deferred | Keep host-owned |
