# x-document Grammar

| Term | Definition |
|---|---|
| x-document | Independent package that compiles validated resolved-document meaning into projections. |
| Contract Version | Version of the external transfer grammar; initially `1.0`. |
| Resolved Document | Presentation-neutral document meaning supplied by a producer. |
| Document Compilation Request | Validated contract containing one resolved document and explicit projection request. |
| Document Driver | Independent compiler backend that converts a request into one projection result. |
| JSON Driver | Proof driver that returns canonical request JSON without layout or rendering. |
| Browser Driver | Concrete projection driver named `browser` that emits portable read-only browser JSON. |
| Browser Projection | Frontend-independent structured expression of resolved meaning; format `browser/1.0`. |
| Browser Section | Ordered source section containing ordered browser fields. |
| Browser Field | Read-only projection of a source field with stable identity, canonical value, display value, evidence links, and metadata. |
| Canonical Value | Original normalized contract value preserved without presentation loss. |
| Display Value | Contract-provided string when available, otherwise a conservative deterministic representation; never a replacement for canonical value. |
| Projection Identity | Deterministic `browser:` identity derived from request fingerprint, driver name, and projection format. |
| Projection Manifest | x-document-owned checksum inventory for one projection format; separate from the producer contract manifest. |
| Browser HTML Adapter | Expression adapter that consumes only `BrowserProjection` and emits complete deterministic HTML. |
| Browser HTML Format | Versioned HTML expression grammar `browser-html/1.0`; distinct from contract `1.0` and browser projection `browser/1.0`. |
| HTML Output Identity | SHA-256 checksum of the exact emitted HTML bytes. |
| Structural Class | Stable `x-document__*` styling hook with no bundled presentation behavior. |
| Inert Action | Action declaration rendered as descriptive list content, never as a button, link, form, or executable command. |
| Escaping Boundary | Projection text becomes escaped HTML text; arbitrary markup is never accepted or sanitized. |
| Basic Semantic Accessibility | One main region, logical headings, definition lists, descriptive lists, and deterministic ARIA relationships; not a claim of WCAG conformance. |
| PDF Driver | Deferred PDF-projection boundary; interface only. |
| Document Compilation Result | Driver outcome containing status and an optional output. |
| Document Compilation Status | External result state: succeeded, unsupported, or failed; never a business-readiness state. |
| Document Output | Portable output metadata and exactly one content form permitted by contract `1.0`. |
| Driver Capability | Stable projection behavior a driver declares and may be explicitly requested; JSON supports actions, attachments, and evidence preservation. |
| Unsupported Result | Valid request the selected driver cannot fulfill because its target or requested capability is unsupported. |
| Failed Result | Safe representation of an expected operational driver failure after request acceptance; not an implementation defect. |
| Output Identity | For the JSON driver, the SHA-256 checksum of the exact canonical output bytes. |
| Semantic Round Trip | Canonical JSON may reorder object keys but preserves every request value, list order, and resolved-document meaning. |
| Canonical JSON | JSON with recursively sorted object keys and preserved list order. |
| Schema Registry | Local mapping from stable contract schema IDs to installed schema files. |
| Compatibility Fixture | Reviewed request JSON used to prove producer-consumer compatibility. |
| Contract Manifest | Versioned canonical inventory of contract schema and fixture paths, stable schema IDs, and exact checksums. |
| Compatibility Harness | Optional development/CI verifier for local contract integrity and producer-snapshot parity; it is not a runtime integration. |
| Local Integrity | Verification of installed assets, checksums, registry, schema validity, canonical bytes, duplicates, and orphans. |
| Producer Snapshot | Out-of-band package-shaped export compared byte-for-byte with the installed contract. |
| Contract Drift | Any missing, modified, duplicated, orphaned, misidentified, non-canonical, or snapshot-divergent contract asset. |
| Projection | Disposable expression of resolved document meaning. |
