# x-document Decision Register

## XDOC-ADR-001 — x-document begins after business resolution

**Status:** Accepted — 2026-07-23. **Context:** Repository interpretation and business reasoning already belong to GNE or another producer. **Decision:** Accept only a validated resolved-document contract. **Rationale:** Drivers should express meaning, never invent it. **Consequences:** No repository, artifact-chain, lifecycle, settlement, or business-engine dependency. **Rejected:** Direct repository consumption and driver-side resolution.

## XDOC-ADR-002 — Contract 1.0 is copied without redesign

**Status:** Accepted — 2026-07-23. **Context:** GNE closed and approved contract `1.0`. **Decision:** Install its schemas and three fixtures byte-for-byte and resolve their stable IDs locally. **Rationale:** Compatibility requires shared semantics rather than parallel invention. **Consequences:** Incompatible changes require a new contract version. **Rejected:** Package-specific schema changes and permissive decoding.

## XDOC-ADR-003 — Drivers share one minimal interface

**Status:** Accepted — 2026-07-23. **Context:** Multiple output forms are planned but only JSON is needed for boundary proof. **Decision:** A driver names itself and compiles one validated request into one result. Browser and PDF remain interfaces without implementations. **Rationale:** This is the smallest durable projection seam. **Consequences:** Drivers remain independent and cannot acquire business-resolution duties. **Rejected:** Universal rendering engine, driver inheritance hierarchy, and PDF-first abstractions.

## XDOC-ADR-004 — JSON is the bootstrap proof driver

**Status:** Accepted — 2026-07-23. **Context:** The package needs an executable projection without layout concerns. **Decision:** Return canonical request JSON as inline `application/json` output with checksum and byte length. **Rationale:** This proves validation, DTO portability, driver dispatch, and deterministic serialization. **Consequences:** JSON output is a projection, not canonical business source. **Rejected:** Browser, HTML, Markdown, or PDF implementation during bootstrap.
