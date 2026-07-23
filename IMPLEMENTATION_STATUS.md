# Implementation Status

| Capability | Implementation | Tests | Limitations | Status | Next action |
|---|---|---|---|---|---|
| Contract 1.0 | Reviewed request, resolved-document, and result schemas with local stable-ID registry | Fixture and schema tests | Version 1.0 only | Implemented | Preserve compatibility |
| Portable DTOs | Request, resolved document, result, output, and version objects | Unit tests | Deliberately retain validated nested payload | Implemented | Evolve only with contract versioning |
| Validation | Opis schema validation with explicit unsupported-version failure | Positive and negative tests | No downgrade negotiation | Implemented | Keep strict |
| Driver contract | Minimal `DocumentDriver`; Browser/PDF boundaries are interfaces only | Architecture tests | No capability negotiation | Implemented | Preserve independence |
| JSON driver | Canonical request JSON output with checksum and byte length | Fixture round-trip tests | No formatting or layout | Implemented | Use as compatibility harness |
| Browser rendering | Interface only | Architecture checks | No implementation | Deferred | Deliberate future slice |
| PDF rendering | Interface only | Architecture checks | No Adobe, AcroForms, XFDF, or PDF library | Deferred | Keep deferred |
| Storage/execution | None | Architecture checks | No queues, persistence, webhooks, binary transport, or action execution | Deferred | Keep host-owned |
