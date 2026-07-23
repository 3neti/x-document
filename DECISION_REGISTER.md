# x-document Decision Register

## XDOC-ADR-001 — x-document begins after business resolution

**Status:** Accepted — 2026-07-23. **Context:** Repository interpretation and business reasoning already belong to GNE or another producer. **Decision:** Accept only a validated resolved-document contract. **Rationale:** Drivers should express meaning, never invent it. **Consequences:** No repository, artifact-chain, lifecycle, settlement, or business-engine dependency. **Rejected:** Direct repository consumption and driver-side resolution.

## XDOC-ADR-002 — Contract 1.0 is copied without redesign

**Status:** Accepted — 2026-07-23. **Context:** GNE closed and approved contract `1.0`. **Decision:** Install its schemas and three fixtures byte-for-byte and resolve their stable IDs locally. **Rationale:** Compatibility requires shared semantics rather than parallel invention. **Consequences:** Incompatible changes require a new contract version. **Rejected:** Package-specific schema changes and permissive decoding.

## XDOC-ADR-003 — Drivers share one minimal interface

**Status:** Accepted — 2026-07-23. **Context:** Multiple output forms are planned but only JSON is needed for boundary proof. **Decision:** A driver names itself and compiles one validated request into one result. Browser and PDF remain interfaces without implementations. **Rationale:** This is the smallest durable projection seam. **Consequences:** Drivers remain independent and cannot acquire business-resolution duties. **Rejected:** Universal rendering engine, driver inheritance hierarchy, and PDF-first abstractions.

## XDOC-ADR-004 — JSON is the bootstrap proof driver

**Status:** Accepted — 2026-07-23. **Context:** The package needs an executable projection without layout concerns. **Decision:** Return canonical request JSON as inline `application/json` output with checksum and byte length. **Rationale:** This proves validation, DTO portability, driver dispatch, and deterministic serialization. **Consequences:** JSON output is a projection, not canonical business source. **Rejected:** Browser, HTML, Markdown, or PDF implementation during bootstrap.

## XDOC-ADR-005 — Contract compatibility is verified without producer runtime coupling

**Status:** Accepted — 2026-07-23. **Context:** Copied schemas and fixtures can silently diverge from a producer over time. **Decision:** Freeze contract `1.0` through a versioned manifest, exact checksums, canonical fixture bytes, local registry/schema verification, and optional byte-exact comparison with an out-of-band package-shaped producer snapshot. **Rationale:** The external contract—not shared PHP code—is the integration boundary. **Consequences:** The harness is optional development/CI tooling, performs no network access, reports but never repairs drift, and treats an absent snapshot as an explicit local-only verification. The three fixtures were normalized once to the existing canonical pretty-JSON serializer when this first manifest was established; their meaning did not change. Subsequent semantic or byte changes require an intentional manifest update with explanation or a new contract version, and incompatible changes require a new version. **Rejected:** Composer dependency on GNE, runtime repository access, automatic GitHub fetching, and silent checksum repair.

## XDOC-ADR-006 — Drivers declare capabilities and return invariant-safe deterministic results

**Status:** Accepted — 2026-07-23. **Context:** The proof driver previously relied on result-schema validation to catch invalid PHP construction and did not declare support explicitly. **Decision:** Add explicit driver capabilities, a backed status enum, private result/output constructors, named factories, target and capability checks, derived inline checksum/length, and mandatory result-schema validation. Preserve contract `1.0` unchanged: warnings remain strings, structured unsupported details and output identity use existing metadata, and the output checksum is the JSON projection identity. **Rationale:** Internal APIs should make invalid states difficult while the external schema remains the final compatibility guard. **Consequences:** JSON emits the complete canonical request, preserves semantic values and list order, returns unsupported for known inability, and lets unexpected defects propagate. **Rejected:** Schema-breaking output fields, silent capability ignoring, broad exception conversion, driver registry, and rendering concerns.

## XDOC-ADR-007 — Browser projection is a portable read-only document model

**Status:** Accepted — 2026-07-23. **Context:** Browser consumers need structured expression without coupling x-document to a frontend or moving business interpretation into a driver. **Decision:** Implement driver `browser` as a deterministic mechanical mapping to x-document-owned format `browser/1.0`, emitted as canonical inline `application/vnd.3neti.x-document.browser+json`. Preserve normalized values, source order, subject, evidence links, actions, and attachment metadata; add conservative display strings and stable source-derived identities. Validate both projection and result schemas. **Rationale:** Any frontend can consume the same portable model while GNE remains the owner of meaning. **Consequences:** The projection is explicitly read-only, projection assets have their own manifest, output checksum identifies exact bytes, and defects propagate. Contract `1.0` remains unchanged, so the browser driver declares only its existing `actions`, `attachments`, and `evidence` request capabilities. **Rejected:** Request echoing, HTML/CSS/component output, editing, action execution, repository access, frontend frameworks, and PDF concepts.
