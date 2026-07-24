# x-document Compass

**Current milestone:** Browser HTML Projection Adapter

**North star:** Faithfully express already-resolved document meaning through independent projection drivers.

## Completed

The standalone package, frozen contract `1.0`, compatibility harness, hardened JSON reference driver, frontend-independent browser projection `browser/1.0`, and deterministic semantic HTML adapter `browser-html/1.0` are established.

## Immediate direction

Define a restrained styling contract over stable HTML structural classes without coupling the adapter to a theme, frontend framework, or business semantics.

## Explicit deferrals

CSS themes, responsive layout systems, Vue, React, Inertia, Blade, Livewire, JavaScript, browser editing and interaction, localization, hyperlinks, Markdown, Print, Email, and PDF implementations; Adobe; AcroForms; XFDF; signatures; synchronization; batch compilation; storage; queues; remote execution; webhooks; binary transport; OCR; AI; settlement; x-change; action execution.

## Known risks

The package supports one frozen contract version, two inline JSON-producing drivers, and one HTML adapter. HTML uses fixed `en`, conservative upstream display values, no stylesheet, no arbitrary raw markup, no source-reference links, and metadata-only attachments. Its accessibility is structural rather than certified. Driver dispatch remains deferred.

## Recommended next task

**Browser HTML Styling Contract** — define optional, versioned styling hooks over the stable semantic HTML without changing projection meaning or introducing a frontend framework.
