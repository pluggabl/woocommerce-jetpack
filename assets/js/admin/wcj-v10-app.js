/* global wcjV10Config */
(function () {
  'use strict';

  const cfg = window.wcjV10Config || {};
  const root = document.getElementById('wcj-v10-root');
  if (!root || !cfg.restUrl) {
    return;
  }

  const editors = window.wcjV10Editors || {};

  const state = {
    view: cfg.initialEditor ? 'editor' : 'catalog',
    catalogTab: cfg.initialTab || 'active',
    selectedCategory: 'all',
    search: '',
    categories: [],
    modules: [],
    recipes: [],
    flagshipEditors: [],
    editorSlug: cfg.initialEditor || '',
    editorData: null,
    draft: null,
    preview: null,
    diffs: [],
    autoEnabled: {},
    previewContext: {
      subtotal: 25,
      shipping: 10,
      invoicePreviewMode: 'split',
    },
    banner: null,
    modalOpen: false,
    historyOpen: false,
    busy: false,
    previewBusy: false,
    previewTimer: null,
    lastPreviewSignature: '',
    editorSeq: 0,
    previewSeq: 0,
    previewAbort: null,
  };

  function getEditor(slug) {
    return editors[slug || state.editorSlug] || null;
  }

  function esc(str) {
    return String(str === null || str === undefined ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function clone(obj) {
    return JSON.parse(JSON.stringify(obj));
  }

  function fmtMoney(num, sym) {
    const val = Number.isFinite(Number(num)) ? Number(num) : 0;
    const prefix = val < 0 ? '-' : '';
    return prefix + (sym || '$') + Math.abs(val).toFixed(2);
  }

  async function api(path, options) {
    const opts = Object.assign(
      {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': cfg.nonce,
        },
        credentials: 'same-origin',
      },
      options || {},
    );
    const res = await fetch(cfg.restUrl.replace(/\/$/, '') + path, opts);
    const json = await res.json();
    if (!res.ok) {
      const msg = (json && json.message) ? json.message : 'Request failed.';
      throw new Error(msg);
    }
    return json;
  }

  function syncBrowserUrl(ctxObj) {
    if (!window.history || !window.history.replaceState || !cfg.dashboardUrl) {
      return;
    }
    let nextUrl = cfg.dashboardUrl;
    if (state.view === 'editor' && state.editorSlug) {
      nextUrl = cfg.dashboardUrl + '&v10_editor=' + encodeURIComponent(state.editorSlug);
      const edMod = getEditor(state.editorSlug);
      const source = ctxObj || state.draft;
      if (edMod && edMod.urlParams && source) {
        nextUrl += edMod.urlParams(source);
      }
    } else if (state.catalogTab === 'all') {
      nextUrl = cfg.dashboardUrl + '&tab=all';
    }
    window.history.replaceState(null, '', nextUrl);
  }

  function getClassicToggleHref() {
    const base = cfg.classicToggleBase || cfg.classicBaseUrl || '#';
    if (!state.editorSlug || base.indexOf('section=') !== -1) {
      return base;
    }
    return base + (base.indexOf('?') === -1 ? '?' : '&') + 'section=' + encodeURIComponent(state.editorSlug);
  }

  function confirmDiscardIfDirty(customMsg) {
    if (state.view === 'editor' && state.diffs.length > 0) {
      return window.confirm(customMsg || 'You have unpublished draft changes. Discard them and continue?');
    }
    return true;
  }

  // =========================================================================
  // ZERO-JUMP SIGNATURE & 0ms PREVIEW ENGINE
  // =========================================================================
  function autoEnableForField(key, formEl) {
    const edMod = getEditor();
    if (!edMod || !state.draft || !state.editorData) {
      return;
    }
    const toggles = edMod.getToggleTarget ? edMod.getToggleTarget(key) : [];
    if (!toggles.length) {
      return;
    }
    const liveVal = state.editorData.live ? state.editorData.live[key] : '';
    const draftVal = state.draft[key];
    if (String(draftVal || '') === String(liveVal || '')) {
      return;
    }
    toggles.forEach((toggleKey) => {
      if (!state.draft[toggleKey]) {
        state.draft[toggleKey] = true;
        state.autoEnabled[toggleKey] = true;
        if (formEl) {
          const cb = formEl.querySelector(`input[data-field="${toggleKey}"]`);
          if (cb) {
            cb.checked = true;
          }
        }
      }
    });
  }

  function previewSignature() {
    return JSON.stringify({
      slug: state.editorSlug,
      mode: state.previewContext.invoicePreviewMode,
      preview: state.preview,
      enabled: state.draft ? !!state.draft.enabled : false,
      autoEnabled: state.autoEnabled,
    });
  }

  function renderPreviewPaneOnly() {
    const pvSlot = document.getElementById('wcj-v10-preview-pane');
    if (pvSlot) {
      pvSlot.innerHTML = renderEditorPreviewHtml();
      state.lastPreviewSignature = previewSignature();
      bindPreviewAndActionEvents();
    }
  }

  function applyLocalPreviewImmediate() {
    const edMod = getEditor();
    if (!edMod || !state.draft) {
      return;
    }
    if (!state.preview) {
      state.preview = { before: {}, after: {} };
    }
    const meta = (state.editorData && state.editorData.meta) || {};
    state.preview.after = edMod.computeLocalAfter(state.draft, meta, state.previewContext, state);
    renderPreviewPaneOnly();
  }

  function applyPreset(presetId) {
    const edMod = getEditor();
    if (!edMod || !state.draft) {
      return;
    }
    const meta = (state.editorData && state.editorData.meta) || {};
    const list = edMod.getPresets ? edMod.getPresets(meta) : [];
    const preset = list.find((p) => p.id === presetId);
    if (!preset) {
      return;
    }
    const form = document.getElementById('wcj-v10-editor-form');
    Object.keys(preset.patch).forEach((key) => {
      const nextVal = preset.patch[key];
      if (typeof nextVal === 'boolean' && nextVal && !state.draft[key]) {
        state.autoEnabled[key] = true;
      }
      state.draft[key] = nextVal;
      if (form) {
        const el = form.querySelector(`[data-field="${key}"]`);
        if (el) {
          if (el.type === 'checkbox') {
            el.checked = !!nextVal;
          } else {
            el.value = String(nextVal);
          }
        }
      }
    });
    if (form && preset.patch.header_text_color) {
      form.querySelectorAll('[data-inv-swatch]').forEach((s) => {
        s.classList.toggle('is-selected', s.dataset.invSwatch === preset.patch.header_text_color);
      });
    }
    applyLocalPreviewImmediate();
    schedulePreviewUpdate();
  }

  // =========================================================================
  // DATA FETCHING
  // =========================================================================
  async function loadCatalog() {
    const data = await api('/modules');
    state.categories = data.categories || [];
    state.modules = data.modules || [];
    state.recipes = data.recipes || [];
    state.flagshipEditors = data.flagship_editors || [];
  }

  async function openEditor(slug, ctxObj) {
    const seq = ++state.editorSeq;
    if (state.previewAbort) {
      state.previewAbort.abort();
      state.previewAbort = null;
    }
    if (state.previewTimer) {
      clearTimeout(state.previewTimer);
      state.previewTimer = null;
    }
    state.banner = null;
    state.autoEnabled = {};
    state.lastPreviewSignature = '';
    state.historyOpen = false;
    state.modalOpen = false;
    state.editorSlug = slug;
    state.view = 'editor';
    syncBrowserUrl(ctxObj);

    const params = [];
    if (ctxObj && ctxObj.gateway) {
      params.push('gateway=' + encodeURIComponent(ctxObj.gateway));
    }
    if (ctxObj && ctxObj.doc_type) {
      params.push('doc_type=' + encodeURIComponent(ctxObj.doc_type));
    }
    const qs = params.length ? '?' + params.join('&') : '';

    const data = await api('/editor/' + encodeURIComponent(slug) + qs);
    if (seq !== state.editorSeq) {
      return;
    }
    state.editorData = data;
    state.draft = clone(data.live);
    syncBrowserUrl();
    await refreshPreview();
    if (seq !== state.editorSeq) {
      return;
    }
    render();
    window.scrollTo(0, 0);
  }

  async function refreshPreview() {
    if (!state.editorSlug || !state.draft) {
      return false;
    }
    const seq = ++state.previewSeq;
    if (state.previewAbort) {
      state.previewAbort.abort();
    }
    const controller = new AbortController();
    state.previewAbort = controller;
    state.previewBusy = true;

    try {
      const res = await api('/preview/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        signal: controller.signal,
        body: JSON.stringify({
          draft: state.draft,
          context: {
            subtotal: state.previewContext.subtotal,
            shipping: state.previewContext.shipping,
          },
        }),
      });
      if (seq !== state.previewSeq) {
        return false;
      }
      state.previewBusy = false;
      state.preview = { before: res.before, after: res.after };
      state.diffs = res.diffs || [];
      return true;
    } catch (err) {
      if (err && err.name === 'AbortError') {
        return false;
      }
      throw err;
    }
  }

  function schedulePreviewUpdate() {
    if (state.previewTimer) {
      clearTimeout(state.previewTimer);
    }
    state.previewBusy = true;
    state.previewTimer = setTimeout(async () => {
      try {
        const updated = await refreshPreview();
        if (updated) {
          updateEditorPreviewAndBar();
        }
      } catch (err) {
        state.previewBusy = false;
        state.banner = { type: 'error', text: err.message };
        renderBannerOnly();
      }
    }, 100);
  }

  async function publishDraft() {
    const edMod = getEditor();
    const validationErr = edMod && edMod.getValidationError ? edMod.getValidationError(state.draft) : '';
    if (validationErr) {
      renderModalOnly();
      return;
    }
    if (state.busy || !state.diffs.length) {
      return;
    }
    state.busy = true;
    renderModalOnly();
    try {
      const res = await api('/publish/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify({ draft: state.draft }),
      });
      state.modalOpen = false;
      state.autoEnabled = {};
      state.editorData.live = clone(res.live);
      state.editorData.meta = res.meta || state.editorData.meta;
      state.editorData.revisions = res.revisions || [];
      state.draft = clone(res.live);
      state.banner = {
        type: 'success',
        text: 'Published to your live store. A rollback snapshot was saved automatically.',
        verifyUrl: (state.editorData.meta && state.editorData.meta.verify_url) || '',
        verifyLabel: (state.editorData.meta && state.editorData.meta.verify_label) || 'Verify on Live Store',
      };
      await refreshPreview();
      await loadCatalog();
    } catch (err) {
      state.modalOpen = false;
      state.banner = { type: 'error', text: err.message };
    } finally {
      state.busy = false;
      render();
    }
  }

  async function undoLastPublish(revisionId) {
    if (state.busy) {
      return;
    }
    state.busy = true;
    renderModalOnly();
    try {
      const payload = { revision_id: revisionId || '' };
      if (state.draft && state.draft.gateway) {
        payload.gateway = state.draft.gateway;
      }
      if (state.draft && state.draft.doc_type) {
        payload.doc_type = state.draft.doc_type;
      }
      const res = await api('/rollback/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      state.historyOpen = false;
      state.autoEnabled = {};
      state.editorData.live = clone(res.live);
      state.editorData.meta = res.meta || state.editorData.meta;
      state.editorData.revisions = res.revisions || [];
      state.draft = clone(res.live);
      state.banner = {
        type: 'success',
        text: revisionId
          ? 'Restored store configuration to before the selected change.'
          : 'Reverted to your previous saved settings via 1-Click Undo.',
      };
      await refreshPreview();
      await loadCatalog();
    } catch (err) {
      state.historyOpen = false;
      state.banner = { type: 'error', text: err.message };
    } finally {
      state.busy = false;
      render();
    }
  }

  // =========================================================================
  // SEARCH & CATALOG RANKING
  // =========================================================================
  function scoreModuleMatch(mod, query) {
    if (!query) {
      return 1;
    }
    const name = mod.name.toLowerCase();
    const keywords = (mod.keywords || '').toLowerCase();
    const cat = (mod.category_label || '').toLowerCase();
    const desc = (mod.desc || '').toLowerCase();
    const escQ = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const wordStart = new RegExp('(?:^|\\b)' + escQ, 'i');

    if (wordStart.test(name)) return 100;
    if (wordStart.test(keywords)) return 80;
    if (name.indexOf(query) !== -1) return 50;
    if (wordStart.test(cat) || wordStart.test(desc)) return 30;
    if (keywords.indexOf(query) !== -1 || cat.indexOf(query) !== -1 || desc.indexOf(query) !== -1) return 10;
    return 0;
  }

  // =========================================================================
  // RENDERING
  // =========================================================================
  function renderTopBar() {
    const isHome = state.view === 'catalog' && state.catalogTab === 'active';
    const isAll = state.view === 'catalog' && state.catalogTab === 'all';
    return `
      <div class="wcj-v10-topbar">
        <div class="wcj-v10-brand">
          <img src="${esc(cfg.logoUrl)}" alt="Booster" />
          <span class="wcj-v10-edition">${esc(cfg.edition || 'free')}</span>
        </div>
        <div class="wcj-v10-nav">
          <button type="button" class="wcj-v10-nav-btn ${isHome ? 'is-active' : ''}" data-nav="home">Home</button>
          <button type="button" class="wcj-v10-nav-btn ${isAll ? 'is-active' : ''}" data-nav="all">All Modules</button>
          <a class="wcj-v10-nav-link" href="${esc(cfg.generalUrl)}">General Settings</a>
          <a class="wcj-v10-nav-link" href="${esc(cfg.toolsUrl)}">Tools</a>
          <a class="wcj-v10-mode-pill" data-classic-switch="1" href="${esc(getClassicToggleHref())}">
            &#8644; Switch to Classic v9
          </a>
        </div>
      </div>
    `;
  }

  function renderBannerHtml() {
    if (!state.banner) {
      return '';
    }
    const cls = state.banner.type === 'error' ? 'is-error' : 'is-success';
    const canUndo = state.view === 'editor' && state.banner.type === 'success' && state.editorData && state.editorData.revisions && state.editorData.revisions.length > 0;
    const verifyBtn = state.banner.verifyUrl
      ? `<a class="wcj-v10-btn wcj-v10-btn-primary" href="${esc(state.banner.verifyUrl)}" target="_blank" rel="noopener">${esc(state.banner.verifyLabel || 'Verify on Live Store')} &#8599;</a>`
      : '';
    return `
      <div class="wcj-v10-banner ${cls}">
        <span>${esc(state.banner.text)}</span>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          ${verifyBtn}
          ${canUndo ? '<button type="button" class="wcj-v10-btn" data-action="undo">Undo Now</button>' : ''}
          <button type="button" class="wcj-v10-btn" data-action="dismiss-banner">Dismiss</button>
        </div>
      </div>
    `;
  }

  function renderRecipesHtml() {
    if (!state.recipes || !state.recipes.length) {
      return '';
    }
    return `
      <div class="wcj-v10-recipes">
        ${state.recipes.map((r) => `
          <div class="wcj-v10-recipe-card">
            <div>
              <h4>${esc(r.title)}</h4>
              <p>${esc(r.description)}</p>
            </div>
            <div>
              ${r.editor_slug
                ? `<button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-open-editor="${esc(r.editor_slug)}">${esc(r.cta || 'Open Preview')} &rarr;</button>`
                : `<a class="wcj-v10-btn" href="${esc(r.classic_url || '#')}">${esc(r.cta || 'Configure')} &rarr;</a>`
              }
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderCatalogBodyHtml() {
    const q = state.search.trim().toLowerCase();
    const activeMods = state.modules.filter((m) => m.enabled);
    const isSearching = q.length > 0;
    const showAll = state.catalogTab === 'all' || isSearching;
    const baseList = showAll ? state.modules : (activeMods.length ? activeMods : state.modules.filter((m) => m.flagship_editor));

    const filtered = baseList
      .filter((m) => !showAll || state.selectedCategory === 'all' || m.category_id === state.selectedCategory)
      .map((m) => ({ mod: m, score: scoreModuleMatch(m, q) }))
      .filter((row) => row.score > 0);

    if (isSearching) {
      filtered.sort((a, b) => b.score - a.score || (b.mod.enabled ? 1 : 0) - (a.mod.enabled ? 1 : 0));
    }

    const catPills = showAll
      ? `<div class="wcj-v10-chips" style="margin-bottom:14px;">
          <button type="button" class="wcj-v10-chip ${state.selectedCategory === 'all' ? 'is-active' : ''}" data-cat="all">All</button>
          ${state.categories.map((c) => `<button type="button" class="wcj-v10-chip ${state.selectedCategory === c.id ? 'is-active' : ''}" data-cat="${esc(c.id)}">${esc(c.label)}</button>`).join('')}
        </div>`
      : '';

    const emptyActiveNotice = !showAll && activeMods.length === 0
      ? `<div class="wcj-v10-empty-note">
          <strong>No active modules yet.</strong> Pick a goal recipe or guided editor below to preview real store changes Before &amp; After, or browse <strong>All Modules (${state.modules.length})</strong>.
        </div>
        ${renderRecipesHtml()}`
      : '';

    const cardsHtml = filtered.length
      ? filtered.map(({ mod: m }) => `
          <div class="wcj-v10-mod-card">
            <div>
              <div class="wcj-v10-mod-head">
                <h3 class="wcj-v10-mod-title">${esc(m.name)}</h3>
                <span class="wcj-v10-badge ${m.enabled ? 'is-on' : ''}">${m.enabled ? 'Active' : 'Inactive'}</span>
              </div>
              <p class="wcj-v10-mod-desc">${esc(m.desc)}</p>
              <div class="wcj-v10-badges">
                <span class="wcj-v10-badge">${esc(m.category_label)}</span>
                <span class="wcj-v10-badge">HPOS Ready</span>
                <span class="wcj-v10-badge">${m.blocks_status === 'blocks' ? 'Checkout Blocks' : 'Classic Checkout'}</span>
                ${m.flagship_editor ? '<span class="wcj-v10-badge is-flagship">Live Before/After Preview</span>' : ''}
              </div>
            </div>
            <div class="wcj-v10-mod-foot">
              ${m.flagship_editor
                ? `<button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-open-editor="${esc(m.flagship_editor)}">Configure &amp; Preview &rarr;</button>
                   <a class="wcj-v10-nav-link" href="${esc(m.classic_url)}">All Classic Options</a>`
                : `<a class="wcj-v10-btn" href="${esc(m.classic_url)}">Configure Settings &rarr;</a>`
              }
            </div>
          </div>
        `).join('')
      : '<div class="wcj-v10-card">No matching modules found. Try "fee", "paypal", "invoice", "vat", or "button".</div>';

    return `
      <div class="wcj-v10-toolbar">
        <div class="wcj-v10-tabs">
          <button type="button" class="wcj-v10-tab ${!isSearching && state.catalogTab === 'active' ? 'is-active' : ''}" data-catalog-tab="active">
            My Active Modules (${activeMods.length})
          </button>
          <button type="button" class="wcj-v10-tab ${isSearching || state.catalogTab === 'all' ? 'is-active' : ''}" data-catalog-tab="all">
            All Modules (${state.modules.length})
          </button>
        </div>
        ${isSearching ? `<span class="wcj-v10-sub">${filtered.length} matching module${filtered.length === 1 ? '' : 's'}</span>` : ''}
      </div>
      ${emptyActiveNotice}
      ${catPills}
      <div class="wcj-v10-grid">${cardsHtml}</div>
    `;
  }

  function renderCatalogView() {
    const activeMods = state.modules.filter((m) => m.enabled);
    return `
      <div class="wcj-v10-card">
        <div class="wcj-v10-hero">
          <div>
            <h1>Booster Command Center</h1>
            <p class="wcj-v10-sub">
              ${activeMods.length
                ? `Showing your ${activeMods.length} active modules first. Preview changes safely before publishing.`
                : 'Preview changes side-by-side before anything affects your live store.'}
            </p>
          </div>
        </div>
        <div class="wcj-v10-search-wrap">
          <input id="wcj-v10-search" class="wcj-v10-search" type="search" value="${esc(state.search)}"
            placeholder="What do you want to set up? (try: fee, paypal, invoice, vat, button, sale...)" />
          <div class="wcj-v10-chips" id="wcj-v10-quick-chips">
            <span>Quick searches:</span>
            ${['fee', 'invoice', 'button', 'sale'].map((term) => `<button type="button" class="wcj-v10-chip" data-quick-search="${term}">"${term}"</button>`).join('')}
            ${state.search ? '<button type="button" class="wcj-v10-chip" data-quick-search="">Clear</button>' : ''}
          </div>
        </div>
      </div>

      <div id="wcj-v10-catalog-body">${renderCatalogBodyHtml()}</div>
    `;
  }

  function renderPresetsHtml(slug) {
    const edMod = getEditor(slug);
    const meta = (state.editorData && state.editorData.meta) || {};
    const presets = edMod && edMod.getPresets ? edMod.getPresets(meta) : [];
    if (!presets.length) {
      return '';
    }
    return `
      <div class="wcj-v10-presets">
        <strong>Starter Presets:</strong>
        ${presets.map((p) => `<button type="button" class="wcj-v10-preset-chip" data-apply-preset="${esc(p.id)}">${esc(p.label)}</button>`).join('')}
      </div>
    `;
  }

  function renderDiagnosticsHtml() {
    const meta = (state.editorData && state.editorData.meta) || {};
    const items = Array.isArray(meta.diagnostics) ? meta.diagnostics.slice() : [];
    const d = state.draft || {};

    if (state.editorSlug === 'pdf_invoicing' && d.attach_completed && !d.create_on_completed) {
      items.unshift({
        level: 'warning',
        text: 'Attach PDF to Completed Order emails is checked, but Auto-create PDF when Order is Completed is off.',
      });
    }

    if (!items.length) {
      return '';
    }

    return `
      <div class="wcj-v10-diag-list">
        ${items.map((item) => `
          <div class="wcj-v10-diag-item is-${item.level === 'warning' ? 'warning' : 'info'}">
            <span>${esc(item.text)}</span>
            ${item.action_url ? `<a class="wcj-v10-btn" href="${esc(item.action_url)}" target="_blank" rel="noopener">${esc(item.action_label || 'Open')} &#8599;</a>` : ''}
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderEditorHeaderHtml() {
    const ed = state.editorData;
    const edMod = getEditor();
    const isOn = edMod && edMod.isLiveOn ? edMod.isLiveOn(ed.live) : !!ed.live.enabled;
    const revCount = (ed.revisions && ed.revisions.length) || 0;
    const diffCount = state.diffs.length;
    const classicLinks = edMod && edMod.renderClassicMenu
      ? edMod.renderClassicMenu(ed, { esc })
      : `<a class="wcj-v10-nav-link" href="${esc(ed.classic_url)}">All Classic Options &rarr;</a>`;

    return `
      <div class="wcj-v10-editor-head">
        <div class="wcj-v10-editor-title">
          <button type="button" class="wcj-v10-btn" data-nav="home">&larr; Home</button>
          <div>
            <h1>${esc((edMod && edMod.title) || state.editorSlug)}</h1>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:4px;">
              <span class="wcj-v10-badge ${isOn ? 'is-on' : ''}">${isOn ? 'Live: Active' : 'Live: Off'}</span>
              ${classicLinks}
            </div>
          </div>
        </div>
        <div class="wcj-v10-editor-actions" id="wcj-v10-action-bar">
          ${renderActionButtonsHtml(diffCount, revCount)}
        </div>
      </div>
    `;
  }

  function renderActionButtonsHtml(diffCount, revCount) {
    return `
      ${revCount > 0 ? `<button type="button" class="wcj-v10-btn" data-action="open-history">History (${revCount})</button>` : ''}
      ${revCount > 0 ? '<button type="button" class="wcj-v10-btn" data-action="undo">&#8630; Undo Last Publish</button>' : ''}
      ${diffCount > 0 ? '<button type="button" class="wcj-v10-btn" data-action="discard">Discard</button>' : ''}
      <button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-action="open-publish" ${diffCount === 0 ? 'disabled' : ''}>
        Review &amp; Publish (${diffCount})
      </button>
    `;
  }

  function renderEditorFormHtml() {
    const edMod = getEditor();
    if (!edMod || !state.draft || !state.editorData) {
      return '';
    }
    return edMod.renderForm(state.draft, state.editorData.meta || {}, {
      esc,
      renderPresetsHtml,
    });
  }

  function renderEditorPreviewHtml() {
    const edMod = getEditor();
    if (!edMod || !state.draft || !state.editorData) {
      return '';
    }
    return edMod.renderPreview(
      state.preview || { before: {}, after: {} },
      state.draft,
      state.editorData.meta || {},
      state.previewContext,
      state,
      { esc, fmtMoney, renderDiagnosticsHtml },
    );
  }

  function renderModalHtml() {
    if (state.historyOpen && state.editorData) {
      const revs = state.editorData.revisions || [];
      return `
        <div class="wcj-v10-modal-backdrop" id="wcj-v10-modal-backdrop">
          <div class="wcj-v10-modal" role="dialog" aria-modal="true">
            <div class="wcj-v10-modal-head">
              <strong>Saved Revision History (${revs.length})</strong>
              <button type="button" class="wcj-v10-btn" data-action="close-modal">Close</button>
            </div>
            <div class="wcj-v10-modal-body">
              ${revs.length
                ? revs.map((rev, idx) => `
                    <div class="wcj-v10-rev-row">
                      <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;">
                          ${esc(rev.timestamp || '')}
                          ${idx === 0 ? ' <span class="wcj-v10-badge is-on">Latest</span>' : ''}
                        </div>
                        <div style="color:#50575e;">${esc(rev.summary || 'Saved configuration')}</div>
                      </div>
                      <button type="button" class="wcj-v10-btn" data-restore-rev="${esc(rev.id)}" ${state.busy ? 'disabled' : ''}>
                        ${state.busy ? 'Restoring...' : 'Restore'}
                      </button>
                    </div>
                  `).join('')
                : '<p style="margin:0;color:#50575e;">No saved revisions yet for this module.</p>'}
            </div>
            <div class="wcj-v10-modal-foot">
              <button type="button" class="wcj-v10-btn" data-action="close-modal">Close</button>
            </div>
          </div>
        </div>
      `;
    }

    if (!state.modalOpen) {
      return '';
    }
    const edMod = getEditor();
    const validationErr = edMod && edMod.getValidationError ? edMod.getValidationError(state.draft) : '';
    return `
      <div class="wcj-v10-modal-backdrop" id="wcj-v10-modal-backdrop">
        <div class="wcj-v10-modal" role="dialog" aria-modal="true">
          <div class="wcj-v10-modal-head">
            <strong>Review &amp; Publish Changes</strong>
            <button type="button" class="wcj-v10-btn" data-action="close-modal">Close</button>
          </div>
          <div class="wcj-v10-modal-body">
            ${state.diffs.map((d) => `
              <div class="wcj-v10-diff-row">
                <strong>${esc(d.label)}</strong>
                <div>
                  <span class="wcj-v10-diff-old">${esc(d.from)}</span> &rarr;
                  <span class="wcj-v10-diff-new">${esc(d.to)}</span>
                </div>
              </div>
            `).join('')}
            ${validationErr ? `<div class="wcj-v10-modal-error">${esc(validationErr)}</div>` : ''}
            <p style="font-size:12px;color:#50575e;margin:10px 0 0;">
              A snapshot of your previous values is saved automatically so you can Undo in one click.
            </p>
          </div>
          <div class="wcj-v10-modal-foot">
            <button type="button" class="wcj-v10-btn" data-action="close-modal">Keep Editing</button>
            <button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-action="confirm-publish" ${(state.busy || !!validationErr) ? 'disabled' : ''}>
              ${state.busy ? 'Publishing...' : 'Confirm & Publish'}
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function renderBannerOnly() {
    const slot = document.getElementById('wcj-v10-banner-slot');
    if (slot) {
      slot.innerHTML = renderBannerHtml();
      bindChromeEvents();
    }
  }

  function renderModalOnly() {
    const slot = document.getElementById('wcj-v10-modal-slot');
    if (slot) {
      slot.innerHTML = renderModalHtml();
      bindChromeEvents();
    }
  }

  function updateCatalogBodyOnly() {
    const bodyEl = document.getElementById('wcj-v10-catalog-body');
    if (bodyEl) {
      bodyEl.innerHTML = renderCatalogBodyHtml();
      bindCatalogBodyEvents();
    }
    const chipsEl = document.getElementById('wcj-v10-quick-chips');
    if (chipsEl) {
      chipsEl.innerHTML = `
        <span>Quick searches:</span>
        ${['fee', 'invoice', 'button', 'sale'].map((term) => `<button type="button" class="wcj-v10-chip" data-quick-search="${term}">"${term}"</button>`).join('')}
        ${state.search ? '<button type="button" class="wcj-v10-chip" data-quick-search="">Clear</button>' : ''}
      `;
      bindQuickChips();
    }
  }

  function updateEditorPreviewAndBar() {
    if (previewSignature() !== state.lastPreviewSignature) {
      renderPreviewPaneOnly();
    }
    const barSlot = document.getElementById('wcj-v10-action-bar');
    if (barSlot && state.editorData) {
      const revCount = (state.editorData.revisions && state.editorData.revisions.length) || 0;
      barSlot.innerHTML = renderActionButtonsHtml(state.diffs.length, revCount);
      bindPreviewAndActionEvents();
    }
  }

  function render() {
    const body = state.view === 'editor' && state.editorData
      ? `
        ${renderEditorHeaderHtml()}
        <div id="wcj-v10-banner-slot">${renderBannerHtml()}</div>
        <div class="wcj-v10-split">
          <form class="wcj-v10-form-card" id="wcj-v10-editor-form" onsubmit="return false;">
            ${renderEditorFormHtml()}
          </form>
          <div class="wcj-v10-preview-card" id="wcj-v10-preview-pane">
            ${renderEditorPreviewHtml()}
          </div>
        </div>
      `
      : `
        <div id="wcj-v10-banner-slot">${renderBannerHtml()}</div>
        ${renderCatalogView()}
      `;

    root.innerHTML = renderTopBar() + body + `<div id="wcj-v10-modal-slot">${renderModalHtml()}</div>`;
    if (state.view === 'editor' && state.editorData) {
      state.lastPreviewSignature = previewSignature();
    } else {
      state.lastPreviewSignature = '';
    }
    bindAllEvents();
  }

  // =========================================================================
  // EVENT BINDING
  // =========================================================================
  function bindChromeEvents() {
    root.querySelectorAll('[data-nav]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (!confirmDiscardIfDirty()) return;
        const target = btn.dataset.nav;
        state.view = 'catalog';
        state.catalogTab = target === 'all' ? 'all' : 'active';
        state.editorSlug = '';
        state.banner = null;
        state.autoEnabled = {};
        state.lastPreviewSignature = '';
        state.historyOpen = false;
        state.modalOpen = false;
        syncBrowserUrl();
        render();
        window.scrollTo(0, 0);
      });
    });

    root.querySelectorAll('[data-classic-switch]').forEach((link) => {
      link.addEventListener('click', (e) => {
        if (!confirmDiscardIfDirty()) {
          e.preventDefault();
        }
      });
    });

    root.querySelectorAll('[data-action="dismiss-banner"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.banner = null;
        renderBannerOnly();
      });
    });

    root.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.modalOpen = false;
        state.historyOpen = false;
        renderModalOnly();
      });
    });

    const backdrop = document.getElementById('wcj-v10-modal-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) {
          state.modalOpen = false;
          state.historyOpen = false;
          renderModalOnly();
        }
      });
    }

    root.querySelectorAll('[data-action="confirm-publish"]').forEach((btn) => {
      btn.addEventListener('click', publishDraft);
    });

    root.querySelectorAll('[data-restore-rev]').forEach((btn) => {
      btn.addEventListener('click', () => undoLastPublish(btn.dataset.restoreRev));
    });
  }

  function bindPreviewAndActionEvents() {
    root.querySelectorAll('[data-action="open-publish"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.historyOpen = false;
        state.modalOpen = true;
        renderModalOnly();
      });
    });

    root.querySelectorAll('[data-action="open-history"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.modalOpen = false;
        state.historyOpen = true;
        renderModalOnly();
      });
    });

    root.querySelectorAll('[data-action="discard"]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        state.autoEnabled = {};
        state.draft = clone(state.editorData.live);
        state.diffs = [];
        applyLocalPreviewImmediate();
        render();
        const updated = await refreshPreview();
        if (updated) {
          updateEditorPreviewAndBar();
        }
      });
    });

    root.querySelectorAll('[data-action="undo"]').forEach((btn) => {
      btn.addEventListener('click', () => undoLastPublish(''));
    });

    root.querySelectorAll('[data-sample-sub]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        state.previewContext.subtotal = Number(btn.dataset.sampleSub);
        applyLocalPreviewImmediate();
        const updated = await refreshPreview();
        if (updated) {
          updateEditorPreviewAndBar();
        }
      });
    });

    root.querySelectorAll('[data-inv-pv]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.previewContext.invoicePreviewMode = btn.dataset.invPv;
        renderPreviewPaneOnly();
      });
    });
  }

  function bindQuickChips() {
    root.querySelectorAll('[data-quick-search]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.search = btn.dataset.quickSearch;
        const inp = document.getElementById('wcj-v10-search');
        if (inp) {
          inp.value = state.search;
          inp.focus();
        }
        updateCatalogBodyOnly();
      });
    });
  }

  function bindCatalogBodyEvents() {
    root.querySelectorAll('[data-catalog-tab]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.catalogTab = btn.dataset.catalogTab;
        if (state.search) {
          state.search = '';
          const inp = document.getElementById('wcj-v10-search');
          if (inp) inp.value = '';
        }
        syncBrowserUrl();
        updateCatalogBodyOnly();
        root.querySelectorAll('[data-nav]').forEach((navBtn) => {
          const isHome = state.catalogTab === 'active';
          navBtn.classList.toggle('is-active', (navBtn.dataset.nav === 'home' && isHome) || (navBtn.dataset.nav === 'all' && !isHome));
        });
      });
    });

    root.querySelectorAll('[data-cat]').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.selectedCategory = btn.dataset.cat;
        updateCatalogBodyOnly();
      });
    });

    root.querySelectorAll('[data-open-editor]').forEach((btn) => {
      btn.addEventListener('click', () => openEditor(btn.dataset.openEditor));
    });
  }

  function bindAllEvents() {
    bindChromeEvents();
    bindPreviewAndActionEvents();
    bindQuickChips();
    bindCatalogBodyEvents();

    const searchInput = document.getElementById('wcj-v10-search');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        state.search = e.target.value;
        updateCatalogBodyOnly();
      });
    }

    const form = document.getElementById('wcj-v10-editor-form');
    const edMod = getEditor();
    if (form && state.draft && edMod) {
      form.querySelectorAll('[data-apply-preset]').forEach((btn) => {
        btn.addEventListener('click', () => applyPreset(btn.dataset.applyPreset));
      });

      form.querySelectorAll('[data-field]').forEach((el) => {
        const key = el.dataset.field;
        const evt = el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(evt, () => {
          if (edMod.onFieldSwitch && edMod.onFieldSwitch(key, el, state, { confirmDiscardIfDirty, openEditor })) {
            return;
          }
          const val = el.type === 'checkbox' ? el.checked : el.value;
          state.draft[key] = val;
          if (el.type === 'checkbox') {
            delete state.autoEnabled[key];
            if (el.checked) {
              autoEnableForField(key, form);
            }
          } else {
            autoEnableForField(key, form);
          }
          if (edMod.onFieldChange) {
            edMod.onFieldChange(key, val, state);
          }
          applyLocalPreviewImmediate();
          schedulePreviewUpdate();
        });
      });

      if (edMod.bindCustomEvents) {
        edMod.bindCustomEvents(form, state, {
          autoEnableForField,
          onDraftMutated: () => {
            applyLocalPreviewImmediate();
            schedulePreviewUpdate();
          },
        });
      }
    }
  }

  async function init() {
    try {
      await loadCatalog();
      if (state.editorSlug) {
        const edMod = getEditor(state.editorSlug);
        const initCtx = edMod && edMod.initContext ? edMod.initContext(cfg) : {};
        await openEditor(state.editorSlug, initCtx);
      } else {
        render();
      }
    } catch (err) {
      root.innerHTML = `<div class="wcj-v10-banner is-error"><span>${esc(err.message)}</span></div>`;
    }
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      let handled = false;
      if (state.modalOpen || state.historyOpen) {
        state.modalOpen = false;
        state.historyOpen = false;
        renderModalOnly();
        handled = true;
      }
      root.querySelectorAll('details.wcj-v10-dropdown[open]').forEach((d) => {
        d.removeAttribute('open');
        handled = true;
      });
      if (handled) {
        return;
      }
    }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      const inp = document.getElementById('wcj-v10-search');
      if (inp) {
        e.preventDefault();
        inp.focus();
      }
    }
  });

  window.addEventListener('beforeunload', (e) => {
    if (state.view === 'editor' && state.diffs.length > 0 && !state.busy) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  init();
})();
