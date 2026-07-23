# x-document Grammar

| Term | Definition |
|---|---|
| x-document | Independent package that compiles validated resolved-document meaning into projections. |
| Contract Version | Version of the external transfer grammar; initially `1.0`. |
| Resolved Document | Presentation-neutral document meaning supplied by a producer. |
| Document Compilation Request | Validated contract containing one resolved document and explicit projection request. |
| Document Driver | Independent compiler backend that converts a request into one projection result. |
| JSON Driver | Proof driver that returns canonical request JSON without layout or rendering. |
| Browser Driver | Deferred browser-projection boundary; interface only. |
| PDF Driver | Deferred PDF-projection boundary; interface only. |
| Document Compilation Result | Driver outcome containing status and an optional output. |
| Document Output | Portable output metadata and exactly one content form permitted by contract `1.0`. |
| Canonical JSON | JSON with recursively sorted object keys and preserved list order. |
| Schema Registry | Local mapping from stable contract schema IDs to installed schema files. |
| Compatibility Fixture | Reviewed request JSON used to prove producer-consumer compatibility. |
| Contract Manifest | Versioned canonical inventory of contract schema and fixture paths, stable schema IDs, and exact checksums. |
| Compatibility Harness | Optional development/CI verifier for local contract integrity and producer-snapshot parity; it is not a runtime integration. |
| Local Integrity | Verification of installed assets, checksums, registry, schema validity, canonical bytes, duplicates, and orphans. |
| Producer Snapshot | Out-of-band package-shaped export compared byte-for-byte with the installed contract. |
| Contract Drift | Any missing, modified, duplicated, orphaned, misidentified, non-canonical, or snapshot-divergent contract asset. |
| Projection | Disposable expression of resolved document meaning. |
