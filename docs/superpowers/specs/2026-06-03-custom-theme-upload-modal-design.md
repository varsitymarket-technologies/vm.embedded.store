# Custom Theme Upload Modal — Design

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co

## Summary

Move the always-visible "Custom Theme" upload card on the admin Themes page
into a modal triggered by an "Upload Custom Theme" button in the page header.
The modal is the single hub for all custom-theme management: uploading a new
HTML file, activating an existing custom file, opening it in the Page Builder,
or removing it. Server-side behavior (where the uploaded file lands, how
activation flips the theme marker, how removal restores the default theme) is
unchanged.

## Goals

- Free vertical space on the Themes page by removing the inline upload card.
- Provide a single, clearly-labeled entry point for custom-theme workflows.
- Preserve every existing capability from the inline UI (upload, activate,
  remove, "Edit in Builder").
- Keep the implementation small — no new backend, no schema, no AJAX.

## Non-goals

- No change to the upload format (still single `.html` / `.htm` file, read as
  text and stored at `sites/{domain}/builder.cache.html`).
- No new server-side validation or sanitization.
- No multi-file upload, no theme packaging (.zip), no remote import.
- No preview rendering inside the modal — that already exists at the
  `/themes/{name}/interface` route which is unrelated.

## User flow

1. User opens `/vm-admin/{domain}/theme`.
2. The Themes header (top-right) shows the "Active" badge as today plus a new
   button labeled **Upload Custom Theme** with a cloud-upload icon.
3. Click the button → the import modal opens.
4. **If no custom HTML file exists** (`builder.cache.html` missing), the modal
   shows only the drop zone + file picker.
5. **If a custom HTML file already exists**, the modal shows two sections:
   - **"Current custom theme"** card with: an "Active" badge (if currently
     active), an "Activate" button (if not active), a link **Edit in Builder**,
     and a **Remove** action (trash icon, with confirm).
   - **"Upload a new file"** drop zone below, used for replacing the existing
     file.
6. Selecting/dropping a file shows a file-name + size preview card; clicking
   **Import & Activate** posts the form and the page reloads.
7. After any form submit (upload, activate, remove), the existing handlers
   already `header('Location: ...')` and the modal naturally closes when the
   page reloads.

## Component split

Only one file changes:

- **`vm-admin/routes/page.theme.php`** *(modify)* — remove the always-visible
  custom-theme card block (lines 197–289 of the current file), add a button
  to the header, add a modal markup block before the existing `<script>` tag,
  and add `openThemeUploadModal` / `closeThemeUploadModal` helpers to the
  existing `<script>`. The current inline drag-drop JS (`handleFile`,
  `clearFile`, `formatSize`, drag listeners) is moved verbatim into the modal
  scope — IDs and selectors stay the same since the markup is just relocated.

No backend changes. No new files.

## Modal markup

Single root `<div id="themeUploadModal" class="fixed inset-0 z-50 hidden">`
with a backdrop, a panel, and a body that conditionally renders the "Current
custom theme" card. The panel uses the existing Tailwind styling from the
products page modal so the UI feels consistent across admin sections (same
backdrop opacity, scale-95 → scale-100 transition, max-w sized for content).

The "Current custom theme" card mirrors the existing `file-card` block that
the page renders today when `$has_custom_file && $custom_is_active` — that
markup moves into the modal body, wrapped in `<?php if ($has_custom_file): ?>`
(not the stricter `$custom_is_active` check, so the card also surfaces when
a custom file exists but a different theme is active, enabling re-activation).

The drop zone, file preview card, and `<form id="uploadForm">` all move into
the modal body below the "Current custom theme" card. Their IDs and inner
markup stay byte-for-byte the same so the existing JS continues to work.

## JS additions

Two new functions added to the existing `<script>` block at the bottom of
`page.theme.php`:

```javascript
function openThemeUploadModal() {
    const m = document.getElementById('themeUploadModal');
    const b = document.getElementById('themeUploadBackdrop');
    const p = document.getElementById('themeUploadPanel');
    m.classList.remove('hidden');
    requestAnimationFrame(() => {
        b.classList.remove('opacity-0'); b.classList.add('opacity-100');
        p.classList.remove('scale-95', 'opacity-0'); p.classList.add('scale-100', 'opacity-100');
    });
}

function closeThemeUploadModal() {
    const m = document.getElementById('themeUploadModal');
    const b = document.getElementById('themeUploadBackdrop');
    const p = document.getElementById('themeUploadPanel');
    b.classList.remove('opacity-100'); b.classList.add('opacity-0');
    p.classList.remove('scale-100', 'opacity-100'); p.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { m.classList.add('hidden'); }, 300);
}
```

The existing Escape-key handling in this file doesn't exist today, so the
modal can be closed via the X button, Cancel button, or backdrop click — no
new keydown listener is required for this small scope.

The existing `dropZone.addEventListener(...)`, `fileInput.addEventListener(...)`,
`handleFile`, `clearFile`, `formatSize`, and `activateCustom` functions stay
unchanged. They live in the page-level `<script>` block at the bottom of the
file. They look up elements by ID (`dropZone`, `fileInput`, `filePreview`,
`htmlContentInput`) and those IDs still exist — just inside the modal now.

## Behavior comparison

| Action | Current (inline) | New (modal) |
|---|---|---|
| See active custom file info | Page-level card visible always | Inside modal after click |
| Upload a new HTML file | Drop on inline zone | Open modal → drop |
| Activate existing custom | Inline "Activate" button | Inside modal, same handler |
| Remove existing custom | Inline trash icon | Inside modal, same handler |
| Edit in Builder | Inline link in the active card | Inside modal, same link |
| Server-side handlers | POST `action=upload_custom` / `remove_custom` / `edthemes=__custom__` | Unchanged |
| Page reload on submit | Yes (`header('Location: ...')`) | Yes — also closes modal |

## Modal states (UI)

The modal has **one state** with conditional sections; no JS state machine.

1. **Top section — Current custom theme** (rendered only when
   `$has_custom_file === true`):
   - File-icon avatar + "Local HTML File" + "Currently active" or "Inactive"
     subtitle.
   - "Active" green badge OR "Activate" violet button (mutually exclusive
     based on `$custom_is_active`).
   - "Edit in Builder" link button → `/vm-admin/{domain}/builder`.
   - "Remove" trash icon button (POST `action=remove_custom`, confirm dialog).

2. **Bottom section — Upload a new file** (always rendered):
   - Drop zone with `bi-file-earmark-arrow-up` icon and helper text.
   - File preview card (hidden until a file is selected), shown by the
     existing `handleFile()` function.
   - Hidden `<form id="uploadForm" method="POST">` with `action=upload_custom`
     and the `html_content` hidden input that the existing JS fills.
   - "Import & Activate" submit button.

3. **Footer:** Cancel button (closes modal) on the right.

When the "Current custom theme" section is absent (no file yet), the modal is
shorter and just shows the upload zone.

## Page header changes

The existing header block:

```php
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2>Themes</h2>
        <p>Choose a design template for your store</p>
    </div>
    <?php if ($active_theme && $active_theme !== '__custom__'): ?>
        <!-- active badge -->
    <?php elseif ($custom_is_active): ?>
        <!-- custom active badge -->
    <?php endif; ?>
</div>
```

Becomes:

```php
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2>Themes</h2>
        <p>Choose a design template for your store</p>
    </div>
    <div class="flex items-center gap-3">
        <?php // existing active badge logic, unchanged ?>
        <button type="button" onclick="openThemeUploadModal()" class="...">
            <i class="bi bi-cloud-upload"></i> Upload Custom Theme
        </button>
    </div>
</div>
```

If a custom file already exists, the button label changes from "Upload Custom
Theme" to **"Manage Custom Theme"** to reflect the modal's expanded content.
This is a single ternary on `$has_custom_file`.

## Sections removed

The block from line 197 through line 289 of the current `page.theme.php`
(the whole `<!-- Custom Theme Upload Section -->` card) is **deleted from the
page body** and reborn inside the modal. The internal markup stays nearly
identical — just relocated. No styles or IDs are renamed.

## Error handling

The existing UI has zero client-side validation beyond an `alert()` for
non-HTML files in `handleFile()`. We keep that behavior. The modal does not
add new validation.

If the user clicks Cancel after picking a file, `clearFile()` is **not**
automatically called — the picked file persists in the form state until the
modal is reopened. That's the same as the current behavior when the form is
visible but unsubmitted. We do **not** add a reset on close because the
existing inline flow doesn't reset either, and the user can clear via the
existing × button on the file preview card. Maintaining parity keeps the
change purely UI-relocation rather than a behavior change.

## Security

No security surface changes:

- The upload still goes through the existing `upload_custom` POST handler.
- The HTML body is still read via JS FileReader and posted as a string in
  `html_content`.
- The file lands at `sites/{domain}/builder.cache.html` via
  `file_put_contents` — same as before.
- The route is still protected by the existing vm-admin auth check.

The existing implementation has the known property that arbitrary HTML is
written to the per-site directory and rendered as the storefront. That's a
deliberate feature, not a vulnerability — it's a customer-facing storefront
template controlled by the store owner.

## Open questions

None. All design decisions resolved during brainstorming on 2026-06-03.
