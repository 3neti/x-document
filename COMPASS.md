# x-document Compass

**Current milestone:** JSON Driver Hardening

**North star:** Faithfully express already-resolved document meaning through independent projection drivers.

## Completed

The standalone package, frozen contract `1.0`, compatibility harness, invariant-safe result/output factories, explicit status and capabilities, target/capability rejection, canonical semantic round-trip, deterministic output identity, and post-construction result validation are established.

## Immediate direction

Introduce the first browser projection contract without weakening driver independence or adding business interpretation.

## Explicit deferrals

Browser, HTML, Markdown, Print, Email, and PDF implementations; Adobe; AcroForms; XFDF; signatures; synchronization; batch compilation; storage; queues; remote execution; webhooks; binary transport; editing; OCR; AI; settlement; x-change; action execution.

## Known risks

The package supports one frozen contract version and one inline proof driver. Contract `1.0` warnings remain strings and structured unsupported details live in result metadata. The JSON driver has no expected operational failure category today, and driver versioning and dispatch remain deferred.

## Recommended next task

**Browser Driver Contract and Projection Bootstrap** — define a browser expression of the validated request without repository, lifecycle, or rendering-engine coupling.
