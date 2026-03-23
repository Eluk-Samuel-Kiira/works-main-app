{{--
|--------------------------------------------------------------------------
| Reusable Rich Text Editor — @richEditor(id, name, placeholder, height)
|
| Usage anywhere in any blade:
|   @richEditor('descEditor', 'job_description', 'Describe the role…', 200)
|
| The macro outputs:
|   - A styled toolbar with working icons (SVG-based)
|   - A contenteditable div with id = {id}
|   - A hidden input with name = {name} and id = {name}_hidden
|   - JS is loaded once via @once/@push('rich-editor-scripts')
|
| To read the value in JS:
|   richEditorSync('{id}')   — syncs editor → hidden input
|   richEditorGet('{id}')    — returns HTML string
|   richEditorSet('{id}', html) — sets editor content
|   richEditorClear('{id}')  — clears editor
|--------------------------------------------------------------------------
--}}

{{-- ============================================================
     BLADE COMPONENT FILE: resources/views/components/rich-editor.blade.php
     Include like: <x-rich-editor id="descEditor" name="job_description" />
     Or use the raw include below directly.
============================================================ --}}

@props([
    'id'          => 'richEditor',
    'name'        => 'content',
    'placeholder' => 'Start typing…',
    'height'      => 180,
    'value'       => '',
])

<div class="rich-editor-wrapper border rounded overflow-hidden" data-editor-id="{{ $id }}">

    {{-- ── Toolbar ── --}}
    <div class="rich-editor-toolbar d-flex flex-wrap align-items-center gap-1 px-2 py-1 border-bottom bg-light">

        {{-- History --}}
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','undo')" title="Undo">
            <svg viewBox="0 0 24 24"><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.7"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','redo')" title="Redo">
            <svg viewBox="0 0 24 24"><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.7"/></svg>
        </button>

        <div class="re-sep"></div>

        {{-- Text style --}}
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','bold')" title="Bold (Ctrl+B)">
            <svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 0 1 0 8H6z"/><path d="M6 12h9a4 4 0 0 1 0 8H6z"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','italic')" title="Italic (Ctrl+I)">
            <svg viewBox="0 0 24 24"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','underline')" title="Underline (Ctrl+U)">
            <svg viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 0 0 12 0V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','strikeThrough')" title="Strikethrough">
            <svg viewBox="0 0 24 24"><line x1="4" y1="12" x2="20" y2="12"/><path d="M17.5 6.5A4.5 4 0 0 0 12 5c-2.76 0-5 1.34-5 3.5 0 1.54 1.2 2.8 3 3.5"/><path d="M6.5 17.5A4.5 4 0 0 0 12 19c2.76 0 5-1.34 5-3.5 0-1-.37-1.9-1-2.6"/></svg>
        </button>

        <div class="re-sep"></div>

        {{-- Headings --}}
        <button type="button" class="re-btn re-btn-text" onclick="reFmt('{{ $id }}','formatBlock','h2')" title="Heading 2">H2</button>
        <button type="button" class="re-btn re-btn-text" onclick="reFmt('{{ $id }}','formatBlock','h3')" title="Heading 3">H3</button>
        <button type="button" class="re-btn re-btn-text" onclick="reFmt('{{ $id }}','formatBlock','p')"  title="Paragraph">P</button>

        <div class="re-sep"></div>

        {{-- Lists --}}
        <button type="button" class="re-btn" onclick="reInsertList('{{ $id }}', false)" title="Bullet list">
            <svg viewBox="0 0 24 24"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reInsertList('{{ $id }}', true)" title="Numbered list">
            <svg viewBox="0 0 24 24"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','outdent')" title="Outdent">
            <svg viewBox="0 0 24 24"><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M7 8l-4 4 4 4"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','indent')" title="Indent">
            <svg viewBox="0 0 24 24"><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M3 8l4 4-4 4"/></svg>
        </button>

        <div class="re-sep"></div>

        {{-- Alignment --}}
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','justifyLeft')" title="Align left">
            <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','justifyCenter')" title="Align center">
            <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','justifyRight')" title="Align right">
            <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="re-sep"></div>

        {{-- Link --}}
        <button type="button" class="re-btn" onclick="reInsertLink('{{ $id }}')" title="Insert link">
            <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </button>

        <div class="re-sep"></div>

        {{-- Colors --}}
        <label class="re-btn re-color-btn position-relative" title="Text color">
            <svg viewBox="0 0 24 24"><path d="M9 3H5l7 14 7-14h-4l-3 6-3-6z"/><line x1="3" y1="21" x2="21" y2="21" stroke-width="3"/></svg>
            <input type="color" class="re-color-input" id="{{ $id }}_fgColor" value="#000000"
                onchange="reFmt('{{ $id }}','foreColor',this.value)">
        </label>
        <label class="re-btn re-color-btn position-relative" title="Highlight color">
            <svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5" fill="currentColor"/></svg>
            <input type="color" class="re-color-input" id="{{ $id }}_bgColor" value="#ffff00"
                onchange="reFmt('{{ $id }}','hiliteColor',this.value)">
        </label>

        <div class="re-sep"></div>

        {{-- Font family --}}
        <select class="re-select" onchange="reFmt('{{ $id }}','fontName',this.value)" title="Font">
            <option value="">Font</option>
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Verdana">Verdana</option>
            <option value="'Times New Roman'">Times New Roman</option>
            <option value="'Courier New'">Courier New</option>
        </select>

        {{-- Font size --}}
        <select class="re-select" onchange="reFmt('{{ $id }}','fontSize',this.value)" title="Size">
            <option value="">Size</option>
            <option value="1">8pt</option>
            <option value="2">10pt</option>
            <option value="3">12pt</option>
            <option value="4">14pt</option>
            <option value="5">18pt</option>
            <option value="6">24pt</option>
            <option value="7">36pt</option>
        </select>

        <div class="re-sep"></div>

        {{-- Clear --}}
        <button type="button" class="re-btn" onclick="reFmt('{{ $id }}','removeFormat')" title="Clear formatting">
            <svg viewBox="0 0 24 24"><path d="M12 3l9 18H3z"/><line x1="2" y1="20" x2="22" y2="20"/><line x1="12" y1="8" x2="12" y2="14"/></svg>
        </button>
        <button type="button" class="re-btn re-btn-danger" onclick="richEditorClear('{{ $id }}')" title="Clear all content">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </button>

    </div>

    {{-- ── Editable area ── --}}
    <div id="{{ $id }}"
        contenteditable="true"
        class="rich-editor-body p-3"
        style="min-height:{{ $height }}px;max-height:{{ $height * 2 }}px;overflow-y:auto;outline:none;font-size:14px;line-height:1.7"
        data-placeholder="{{ $placeholder }}"
        oninput="richEditorSync('{{ $id }}')">
        {!! $value !!}
    </div>

</div>

{{-- Hidden input that form submissions read --}}
<input type="hidden" name="{{ $name }}" id="{{ $id }}_hidden" value="{{ $value }}">

@once
@push('rich-editor-styles')
<style>
.rich-editor-wrapper { background: #fff; }
.rich-editor-toolbar { background: #f8f9fa !important; }
.re-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 28px; padding: 0; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; cursor: pointer; color: #495057;
    flex-shrink: 0; transition: background .1s, border-color .1s;
}
.re-btn:hover  { background: #e9ecef; border-color: #adb5bd; }
.re-btn:active { background: #dee2e6; }
.re-btn svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.re-btn-text { width: auto; padding: 0 6px; font-size: 12px; font-weight: 600; }
.re-btn-danger:hover { background: #fff5f5; color: #dc3545; border-color: #f5c2c7; }
.re-sep { width: 1px; height: 22px; background: #dee2e6; margin: 0 2px; flex-shrink: 0; }
.re-select {
    height: 28px; font-size: 12px; padding: 0 4px; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; color: #495057; cursor: pointer;
}
.re-color-btn { cursor: pointer; overflow: hidden; }
.re-color-input {
    position: absolute; opacity: 0; width: 100%; height: 100%;
    top: 0; left: 0; cursor: pointer; border: none; padding: 0;
}
.rich-editor-body:empty:before {
    content: attr(data-placeholder);
    color: #adb5bd; pointer-events: none; display: block;
}
.rich-editor-body ul { list-style-type: disc !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body ol { list-style-type: decimal !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body li { display: list-item !important; }
.rich-editor-body h2 { font-size: 1.3em; font-weight: 600; margin: 0.5em 0; }
.rich-editor-body h3 { font-size: 1.1em; font-weight: 600; margin: 0.5em 0; }
</style>
@endpush

@push('rich-editor-scripts')
<script>
// ── Core format command ──
function reFmt(id, cmd, val = null) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, val);
    richEditorSync(id);
}

// ── List insertion — fixes the common execCommand list bug ──
function reInsertList(id, ordered) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();

    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) {
        // No selection — wrap any existing plain text
        document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
        richEditorSync(id);
        return;
    }

    const range    = sel.getRangeAt(0);
    const ancestor = range.commonAncestorContainer;
    const listTag  = ordered ? 'OL' : 'UL';

    // Check if already in a list of same type — toggle off
    let listParent = ancestor.nodeType === 3 ? ancestor.parentNode : ancestor;
    while (listParent && listParent !== el) {
        if (listParent.tagName === listTag) {
            // Already in list — unwrap
            document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
            richEditorSync(id);
            return;
        }
        listParent = listParent.parentNode;
    }

    // Insert new list
    document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
    richEditorSync(id);
}

// ── Link insertion ──
function reInsertLink(id) {
    const el  = document.getElementById(id);
    if (!el) return;
    const sel = window.getSelection();
    const txt = sel && sel.toString().trim() ? sel.toString() : '';
    const url = prompt('Enter URL:', 'https://');
    if (!url) return;
    el.focus();
    if (txt) {
        document.execCommand('createLink', false, url);
    } else {
        document.execCommand('insertHTML', false,
            `<a href="${url}" target="_blank" rel="noopener">${url}</a>`);
    }
    richEditorSync(id);
}

// ── Sync editor HTML → hidden input ──
function richEditorSync(id) {
    const el     = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el && hidden) hidden.value = el.innerHTML;
}

// ── Get HTML value ──
function richEditorGet(id) {
    const el = document.getElementById(id);
    return el ? el.innerHTML : '';
}

// ── Set HTML value ──
function richEditorSet(id, html) {
    const el     = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el) el.innerHTML = html ?? '';
    if (hidden) hidden.value = html ?? '';
}

// ── Clear ──
function richEditorClear(id) {
    richEditorSet(id, '');
    document.getElementById(id)?.focus();
}

// ── Keyboard shortcuts (active only when editor focused) ──
document.addEventListener('keydown', e => {
    const active = document.activeElement;
    if (!active || active.getAttribute('contenteditable') !== 'true') return;
    const id = active.id;
    if (!id) return;
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'b') { e.preventDefault(); reFmt(id, 'bold'); }
        else if (e.key === 'i') { e.preventDefault(); reFmt(id, 'italic'); }
        else if (e.key === 'u') { e.preventDefault(); reFmt(id, 'underline'); }
        else if (e.key === 'z') { e.preventDefault(); reFmt(id, 'undo'); }
        else if (e.key === 'y') { e.preventDefault(); reFmt(id, 'redo'); }
    }
});

// ── Sync all editors on form submit ──
document.addEventListener('submit', () => {
    document.querySelectorAll('[contenteditable="true"][id]').forEach(el => {
        richEditorSync(el.id);
    });
});
</script>
@endpush
@endonce