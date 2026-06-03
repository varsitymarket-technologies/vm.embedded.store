# Custom Theme Upload Modal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Relocate the always-visible "Custom Theme" upload card on the admin Themes page into a button-triggered modal, preserving all existing capabilities (upload, activate, remove, edit-in-builder) and server handlers.

**Architecture:** Pure UI relocation in one file (`vm-admin/routes/page.theme.php`). The block from line 197 through line 289 (the entire `<!-- Custom Theme Upload Section -->`) is removed from the page body and reborn inside a new modal block. A new button in the page header opens the modal. Two small JS helpers (`openThemeUploadModal`, `closeThemeUploadModal`) are added; the existing drag-drop and form-submit JS continues to work unchanged because all DOM IDs are preserved.

**Tech Stack:** PHP 7.4+, vanilla JS, Tailwind utility classes. No new dependencies. No backend or schema changes.

**Spec:** [docs/superpowers/specs/2026-06-03-custom-theme-upload-modal-design.md](../specs/2026-06-03-custom-theme-upload-modal-design.md)

---

## File Map

**Modify (single file, three regions):**
- `vm-admin/routes/page.theme.php`:
  - **Header region** (around lines 129–153) — add a button to the right of the header row, with label that flips based on `$has_custom_file`.
  - **Body region** (lines 197–289 currently) — delete the inline "Custom Theme Upload Section" card entirely.
  - **Modal region** (new, inserted before the existing `<script>` tag near line 391) — render the modal markup, which conditionally contains the "Current custom theme" management card and always contains the drop zone + file preview + upload form. Most of the content is the inline card's markup verbatim, just wrapped in modal chrome.
  - **Script region** (the existing `<script>` block) — add `openThemeUploadModal` and `closeThemeUploadModal` helpers. All other existing JS (`handleFile`, `clearFile`, `formatSize`, `activateCustom`, drag listeners, filter logic) stays unchanged.

**No new files. No tests** — this is pure markup relocation; server handlers (`upload_custom`, `remove_custom`, `edthemes=__custom__`) are untouched.

---

## Task 1: Relocate markup — delete inline card, add header button, insert modal block

**Files:**
- Modify: `vm-admin/routes/page.theme.php`

### Step 1.1: Add the "Upload Custom Theme" / "Manage Custom Theme" button to the page header

- [ ] **Step 1.1: Edit `vm-admin/routes/page.theme.php`**

Find the existing page header block (around lines 128–153). The current block:

```php
        <!-- Page Header -->
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Themes</h2>
                    <p class="text-sm text-zinc-500 mt-1">Choose a design template for your store</p>
                </div>
                <?php if ($active_theme && $active_theme !== '__custom__'): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-zinc-500">Active:</span>
                        <span class="inline-flex items-center gap-1.5 bg-violet-500/10 text-violet-400 border border-violet-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                            <i class="bi bi-check-circle-fill text-[10px]"></i>
                            <?php echo ucwords(str_replace(['_', '-', '.'], ' ', $active_theme)); ?>
                        </span>
                    </div>
                <?php elseif ($custom_is_active): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-zinc-500">Active:</span>
                        <span class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                            <i class="bi bi-file-earmark-code text-[10px]"></i>
                            Custom HTML File
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
```

Replace with (existing badge logic preserved verbatim, new button added inside a wrapping flex container):

```php
        <!-- Page Header -->
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Themes</h2>
                    <p class="text-sm text-zinc-500 mt-1">Choose a design template for your store</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <?php if ($active_theme && $active_theme !== '__custom__'): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-zinc-500">Active:</span>
                            <span class="inline-flex items-center gap-1.5 bg-violet-500/10 text-violet-400 border border-violet-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                                <i class="bi bi-check-circle-fill text-[10px]"></i>
                                <?php echo ucwords(str_replace(['_', '-', '.'], ' ', $active_theme)); ?>
                            </span>
                        </div>
                    <?php elseif ($custom_is_active): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-zinc-500">Active:</span>
                            <span class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                                <i class="bi bi-file-earmark-code text-[10px]"></i>
                                Custom HTML File
                            </span>
                        </div>
                    <?php endif; ?>
                    <button type="button" onclick="openThemeUploadModal()" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700/60 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="bi bi-cloud-upload"></i>
                        <?php echo $has_custom_file ? 'Manage Custom Theme' : 'Upload Custom Theme'; ?>
                    </button>
                </div>
            </div>
        </div>
```

### Step 1.2: Delete the inline "Custom Theme Upload Section" block

- [ ] **Step 1.2: Edit `vm-admin/routes/page.theme.php`**

Locate the entire block that begins with `<!-- Custom Theme Upload Section -->` (currently at line 197) and ends with the matching closing `</div>` (currently around line 289). Delete the whole block. The block to delete:

```php
        <!-- Custom Theme Upload Section -->
        <div class="px-8 pb-6">
            <div class="bg-zinc-900/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
                <!-- ...everything inside, ~92 lines including drop zone, file preview, manage actions... -->
            </div>
        </div>
```

After deletion, the next visible block in the file should be:

```php
        <!-- Search & Filters -->
        <div class="px-8 pb-4">
```

The `<style>` block at the top of the file (containing `.drop-zone`, `.file-card`, `@keyframes slideUp`, etc.) **stays put** — those styles will be used by the relocated markup inside the modal.

### Step 1.3: Insert the modal markup before the existing `<script>` tag

- [ ] **Step 1.3: Edit `vm-admin/routes/page.theme.php`**

Find the closing tag of `<main>` and the page's outermost wrapper `<div>`, just before the `<script>` tag. The current structure near the end of the markup looks like:

```php
        </div>
    </main>
</div>

<script>
// --- File Upload / Drop Zone ---
```

Insert the new modal block **between the closing `</div>` of the outermost wrapper and the `<script>` tag**:

```php
        </div>
    </main>
</div>

<!-- Custom Theme Modal -->
<div id="themeUploadModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="themeUploadTitle">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div id="themeUploadBackdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeThemeUploadModal()"></div>

        <div id="themeUploadPanel" class="relative w-full max-w-2xl bg-zinc-900 rounded-2xl shadow-2xl shadow-black/40 border border-zinc-800/60 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex justify-between items-center px-6 pt-6 pb-4 border-b border-zinc-800/60">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i class="bi bi-cloud-arrow-up text-amber-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white" id="themeUploadTitle">Custom Theme</h3>
                        <p class="text-xs text-zinc-500">Import your own HTML store design</p>
                    </div>
                </div>
                <button type="button" onclick="closeThemeUploadModal()" class="h-8 w-8 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/5 transition-all duration-150">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Modal Body (scrollable) -->
            <div class="px-6 py-5 space-y-5 overflow-y-auto" style="max-height: calc(90vh - 140px);">

                <?php if ($has_custom_file): ?>
                    <!-- Current custom theme card -->
                    <div class="file-card flex items-center gap-4 bg-zinc-800/40 border border-zinc-700/40 rounded-xl p-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-file-earmark-code text-amber-400 text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">Local HTML File</p>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                Custom imported design &middot;
                                <?php echo $custom_is_active ? 'Currently active' : 'Inactive'; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($custom_is_active): ?>
                                <span class="text-xs text-emerald-400 font-medium flex items-center gap-1">
                                    <i class="bi bi-check-circle-fill"></i> Active
                                </span>
                            <?php else: ?>
                                <button type="button" onclick="activateCustom()" class="text-xs bg-violet-600 hover:bg-violet-500 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                                    Activate
                                </button>
                            <?php endif; ?>
                            <a href="/vm-admin/<?php echo __DOMAIN__; ?>/builder" class="text-xs bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-1.5 rounded-lg transition-colors font-medium flex items-center gap-1.5">
                                <i class="bi bi-pencil-square"></i> Edit in Builder
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Remove custom theme and switch to default?')">
                                <input type="hidden" name="action" value="remove_custom">
                                <button type="submit" class="text-xs text-zinc-500 hover:text-red-400 px-2 py-1.5 rounded-lg transition-colors" title="Remove">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-xs uppercase tracking-widest text-zinc-600 font-semibold pt-2">Replace with new file</div>
                <?php endif; ?>

                <!-- Drop Zone -->
                <input type="file" id="fileInput" accept=".html,.htm" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none">
                <div id="dropZone" class="drop-zone border-2 border-dashed border-zinc-700/60 rounded-xl p-8 text-center cursor-pointer hover:border-zinc-600">
                    <div class="drop-icon w-14 h-14 rounded-2xl bg-zinc-800/80 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-file-earmark-arrow-up text-zinc-400 text-2xl"></i>
                    </div>
                    <p class="text-sm font-medium text-zinc-300 mb-1">Drop your HTML file here</p>
                    <p class="text-xs text-zinc-500">or click to browse &middot; Single .html file</p>
                </div>

                <!-- File preview (hidden by default) -->
                <div id="filePreview" class="hidden">
                    <div class="file-card flex items-center gap-4 bg-zinc-800/40 border border-zinc-700/40 rounded-xl p-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-file-earmark-code text-violet-400 text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p id="fileName" class="text-sm font-medium text-white truncate"></p>
                            <p id="fileSize" class="text-xs text-zinc-500 mt-0.5"></p>
                        </div>
                        <button type="button" onclick="clearFile()" class="text-xs text-zinc-500 hover:text-red-400 p-1.5 rounded-lg transition-colors">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form id="uploadForm" method="POST" class="mt-3 flex justify-end">
                        <input type="hidden" name="action" value="upload_custom">
                        <input type="hidden" name="html_content" id="htmlContentInput" value="">
                        <button type="submit" class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Import &amp; Activate
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-zinc-800/60 flex justify-end">
                <button type="button" onclick="closeThemeUploadModal()" class="px-4 py-2 rounded-lg border border-zinc-700/60 text-sm font-medium text-zinc-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// --- File Upload / Drop Zone ---
```

Note: the `<script>` line at the end of the block is the **existing** script tag — keep it in place. You're inserting the modal block ABOVE that line.

### Step 1.4: Syntax check + commit

- [ ] **Step 1.4: Syntax-check**

```bash
docker compose exec vm-emb-sites php -l /var/www/html/public/vm-admin/routes/page.theme.php
```
Or if Docker isn't running: `php -l vm-admin/routes/page.theme.php`. Expected: `No syntax errors detected`.

- [ ] **Step 1.5: Verify the `dropZone` ID is referenced exactly once now**

The old inline section and the new modal both contained `<div id="dropZone">`. After deleting the inline section, only the modal copy should remain. Verify:

```bash
grep -n 'id="dropZone"' vm-admin/routes/page.theme.php
# Expect exactly one match
grep -n 'id="fileInput"' vm-admin/routes/page.theme.php
# Expect exactly one match
grep -n 'id="filePreview"' vm-admin/routes/page.theme.php
# Expect exactly one match
grep -n 'id="uploadForm"' vm-admin/routes/page.theme.php
# Expect exactly one match
grep -n 'id="htmlContentInput"' vm-admin/routes/page.theme.php
# Expect exactly one match
```

If any of these return zero or more than one match, find the duplicate or stale tag and fix it.

- [ ] **Step 1.6: Commit**

```bash
git add vm-admin/routes/page.theme.php
git commit -m "feat: relocate custom theme upload into a modal

Replaces the always-visible Custom Theme card on the admin Themes
page with a single 'Upload Custom Theme' / 'Manage Custom Theme'
button in the header that opens a modal. The modal holds the
upload drop zone plus management actions (Activate, Remove,
Edit in Builder) when a custom file already exists.

Server-side handlers (upload_custom / remove_custom /
edthemes=__custom__) are unchanged; the existing drag-drop and
form-submit JS continues to work because all DOM IDs are
preserved inside the modal scope. JS open/close helpers land in
the next commit."
```

---

## Task 2: Add modal open/close JS and verify in the browser

**Files:**
- Modify: `vm-admin/routes/page.theme.php` (existing `<script>` block, before its closing `</script>`)

### Step 2.1: Append the modal open/close helpers to the existing `<script>` block

- [ ] **Step 2.1: Edit `vm-admin/routes/page.theme.php`**

Find the closing `</script>` tag near the end of the file. Immediately **before** that tag, append:

```javascript

// --- Custom Theme Modal ---
function openThemeUploadModal() {
    const modal = document.getElementById('themeUploadModal');
    const backdrop = document.getElementById('themeUploadBackdrop');
    const panel = document.getElementById('themeUploadPanel');
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
        panel.classList.remove('scale-95', 'opacity-0');
        panel.classList.add('scale-100', 'opacity-100');
    });
}

function closeThemeUploadModal() {
    const modal = document.getElementById('themeUploadModal');
    const backdrop = document.getElementById('themeUploadBackdrop');
    const panel = document.getElementById('themeUploadPanel');
    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');
    panel.classList.remove('scale-100', 'opacity-100');
    panel.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    const m = document.getElementById('themeUploadModal');
    if (m && !m.classList.contains('hidden')) closeThemeUploadModal();
});
```

Do NOT touch the existing JS above this block. In particular, `handleFile`, `clearFile`, `formatSize`, `activateCustom`, the `dropZone` event listeners, the `fileInput` change listener, `filterThemes`, `applyFilters`, and `searchInput` are all unchanged.

### Step 2.2: Syntax check

- [ ] **Step 2.2: Syntax check**

```bash
docker compose exec vm-emb-sites php -l /var/www/html/public/vm-admin/routes/page.theme.php
```
Or local: `php -l vm-admin/routes/page.theme.php`. Expected: `No syntax errors detected`.

### Step 2.3: Browser smoke test — open the modal and verify all interactions

- [ ] **Step 2.3: Manual browser test**

Start the stack (if not already running):

```bash
docker compose ps
docker compose up -d   # only if not already running
```

Navigate to `http://localhost:8016/`, click **Demo Account**, then go to
`http://localhost:8016/vm-admin/<your-domain>/theme` (for the demo flow, the
domain is typically `claude.test` — check the dashboard for the actual value).

**Test 1: Initial state — no custom theme yet**

1. Confirm the page no longer has the large inline Custom Theme card between the stat cards and the Search & Filters row.
2. Confirm the button in the page header reads **"Upload Custom Theme"** (with cloud icon).
3. Click the button → modal opens with a backdrop, scale-in animation, drop zone visible, **no** "Current custom theme" card section above it (since there's no custom file yet).
4. Press Escape → modal closes.
5. Reopen, click the X button → modal closes.
6. Reopen, click the backdrop (the dark area outside the panel) → modal closes.
7. Reopen, click Cancel → modal closes.

**Test 2: Upload a custom HTML file**

1. Create a tiny test HTML file anywhere convenient:
   ```bash
   echo '<!DOCTYPE html><html><body><h1>Hello custom theme</h1></body></html>' > /tmp/test-theme.html
   ```
   (Or any HTML file you already have.)
2. Open the modal, drag/drop the file (or click the drop zone and pick it).
3. Confirm the file preview card appears with the file name and size.
4. Click **Import & Activate**.
5. Page reloads. Confirm:
   - The header button now reads **"Manage Custom Theme"**.
   - The "Active" badge (top right) now reads "Custom HTML File" in amber.
6. Open the modal again → the "Current custom theme" management card is visible at the top with: an "Active" badge, an "Edit in Builder" link button, and a Remove (trash) button. Below the card is the drop zone for replacement uploads.

**Test 3: Deactivate, reactivate, remove**

1. With the custom file active, switch to a regular theme by clicking Activate on any theme card in the grid (or use the existing flow).
2. Reopen the Custom Theme modal. Confirm:
   - The "Current custom theme" card is still visible.
   - The "Active" badge is replaced by an **Activate** button.
3. Click **Activate** → page reloads, custom theme is active again, header button says "Manage Custom Theme".
4. Reopen the modal, click the Remove trash icon → confirm prompt.
5. Confirm → page reloads, the `builder.cache.html` is gone (`ls sites/<your-domain>/builder.cache.html` returns "No such file"), the header button reads "Upload Custom Theme" again, and the modal (when reopened) shows only the drop zone.

**Test 4: Cancel after picking a file (parity with existing behavior)**

1. Open the modal, pick a file → preview card appears.
2. Click Cancel → modal closes.
3. Open the modal again → the preview card is still visible (because the existing inline behavior also persisted unsubmitted picks; we deliberately did not reset on close).
4. Click the × on the preview card → preview clears, drop zone returns.

If any of these tests fail, debug and fix in the same task. Common pitfalls:
- A duplicate DOM ID inside the file (the inline card might not have been fully deleted) → grep for `id="dropZone"` to confirm exactly one match.
- The drag-drop JS lookups happening at script load time before the modal renders → not a problem here because `document.getElementById` on a hidden but DOM-present element works fine.

### Step 2.4: Commit

- [ ] **Step 2.4: Commit**

```bash
git add vm-admin/routes/page.theme.php
git commit -m "feat: wire up custom theme upload modal open/close JS

Adds openThemeUploadModal/closeThemeUploadModal helpers and an
Escape-key handler that closes the modal when it's open. The
existing drag-drop, file-read, and form-submit JS continues to
work unchanged because the underlying DOM IDs (dropZone,
fileInput, filePreview, uploadForm, htmlContentInput) live inside
the modal markup added in the previous commit."
```

---

## Verification checklist

Before declaring the feature done, confirm:

- [ ] `php -l vm-admin/routes/page.theme.php` reports no syntax errors.
- [ ] Page header shows the new button with the correct label ("Upload Custom Theme" when no file exists, "Manage Custom Theme" when one does).
- [ ] The inline Custom Theme card no longer appears in the page body.
- [ ] Clicking the button opens the modal; clicking X / Cancel / backdrop / pressing Escape closes it.
- [ ] Uploading an HTML file through the modal triggers a page reload that flips the header to the "active custom" state.
- [ ] Re-opening the modal with a custom file present shows the "Current custom theme" card with Active badge / Activate button / Edit in Builder / Remove.
- [ ] Removing the custom file restores the no-custom modal state.
- [ ] Existing inline styles (`.drop-zone`, `.file-card`, `.drop-zone.drag-over`) still apply correctly because the styles block at the top of the file was not deleted.
