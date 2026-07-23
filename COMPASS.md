# x-document Compass

**Current milestone:** Browser Driver Contract and Projection Bootstrap

**North star:** Faithfully express already-resolved document meaning through independent projection drivers.

## Completed

The standalone package, frozen contract `1.0`, compatibility harness, hardened JSON reference driver, and frontend-independent browser projection format `browser/1.0` are established. Browser output is canonical vendor JSON with stable identities, preserved meaning, and schema validation.

## Immediate direction

Introduce a browser HTML projection adapter over `BrowserProjection` without changing the portable model or adding a browser application.

## Explicit deferrals

HTML, Vue, React, Inertia, Blade, Livewire, browser editing and interaction, Markdown, Print, Email, and PDF implementations; Adobe; AcroForms; XFDF; signatures; synchronization; batch compilation; storage; queues; remote execution; webhooks; binary transport; OCR; AI; settlement; x-change; action execution.

## Known risks

The package supports one frozen contract version and two inline JSON-producing drivers. Contract `1.0` capabilities limit public browser capability declarations to actions, attachments, and evidence. Display values are conservative rather than localized; attachments are metadata-only; the projection supports fields rather than a widget/layout vocabulary. Driver dispatch remains deferred.

## Recommended next task

**Browser HTML Projection Adapter** — express the stable read-only browser projection as sanitized HTML without changing resolved meaning or introducing a frontend application.
