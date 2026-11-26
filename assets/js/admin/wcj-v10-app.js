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
    baseHashes: {},
    preview: null,
    diffs: [],
    selectedDiffKeys: [],
    localDirtyCount: 0,
    autoEnabled: {},
    eliteSample: {},
    previewContext: {
      subtotal: 25,
      shipping: 10,
      invoicePreviewMode: 'split',
      pricingRoleView: 'role',
      addonPreviewIdx: 0,
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
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
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
    const ct = (res.headers && res.headers.get('content-type')) || '';
    let json = null;
    let rawText = '';

    if (ct.indexOf('application/json') !== -1) {
      json = await res.json();
    } else {
      rawText = await res.text();
      try {
        json = JSON.parse(rawText);
      } catch (parseErr) {
        json = null;
      }
    }

    if (!res.ok) {
      if (json && json.code === 'rest_cookie_invalid_nonce') {
        throw new Error('Your WordPress session expired. Reload the page to continue safely.');
      }
      const msg = (json && json.message)
        ? json.message
        : (rawText ? 'Server returned an unexpected response.' : 'Request failed.');
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

  function computeLocalDirtyKeys() {
    if (!state.editorData || !state.editorData.live || !state.draft) {
      return [];
    }
    const edMod = getEditor();
    const skip = edMod && edMod.nonDiffKeys ? edMod.nonDiffKeys() : [];
    const live = state.editorData.live;
    const keys = [];
    Object.keys(live).forEach((k) => {
      if (skip.indexOf(k) !== -1) {
        return;
      }
      const oldVal = live[k];
      const newVal = state.draft[k];
      if (typeof oldVal === 'boolean' || typeof newVal === 'boolean') {
        if (Boolean(oldVal) !== Boolean(newVal)) {
          keys.push(k);
        }
      } else if (String(oldVal === undefined || oldVal === null ? '' : oldVal) !== String(newVal === undefined || newVal === null ? '' : newVal)) {
        keys.push(k);
      }
    });
    return keys;
  }

  function hasPendingChanges() {
    return state.localDirtyCount > 0 || state.diffs.length > 0;
  }

  function confirmDiscardIfDirty(customMsg) {
    if (state.view === 'editor' && hasPendingChanges()) {
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
      roleView: state.previewContext.pricingRoleView,
      addonIdx: state.previewContext.addonPreviewIdx,
      eliteSample: state.eliteSample,
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
    }
  }

  function updateActionBarOnly() {
    const barSlot = document.getElementById('wcj-v10-action-bar');
    if (barSlot && state.editorData) {
      const revCount = (state.editorData.revisions && state.editorData.revisions.length) || 0;
      const isOn = (() => {
        const edMod = getEditor();
        return edMod && edMod.isLiveOn ? edMod.isLiveOn(state.editorData.live) : !!state.editorData.live.enabled;
      })();
      const displayCount = Math.max(state.localDirtyCount, state.diffs.length);
      barSlot.innerHTML = renderActionButtonsHtml(displayCount, revCount, isOn);
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
    state.localDirtyCount = computeLocalDirtyKeys().length;
    renderPreviewPaneOnly();
    updateActionBarOnly();
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
    state.previewBusy = false;
    state.banner = null;
    state.autoEnabled = {};
    state.eliteSample = {};
    state.diffs = [];
    state.selectedDiffKeys = [];
    state.localDirtyCount = 0;
    state.lastPreviewSignature = '';
    state.historyOpen = false;
    state.modalOpen = false;
    state.editorSlug = slug;
    state.view = 'editor';
    syncBrowserUrl(ctxObj);

    const params = [];
    if (ctxObj) {
      Object.keys(ctxObj).forEach((k) => {
        if (ctxObj[k]) {
          params.push(encodeURIComponent(k) + '=' + encodeURIComponent(ctxObj[k]));
        }
      });
    }
    const qs = params.length ? '?' + params.join('&') : '';

    const data = await api('/editor/' + encodeURIComponent(slug) + qs);
    if (seq !== state.editorSeq) {
      return;
    }
    const edMod = getEditor(slug);
    state.editorData = data;
    state.baseHashes = data.base_hashes || {};
    state.draft = clone(data.live);
    if (edMod && edMod.initPreviewState) {
      edMod.initPreviewState(data.meta || {}, state.previewContext);
    }
    syncBrowserUrl();
    await refreshPreview();
    if (seq !== state.editorSeq) {
      return;
    }
    render();
    window.scrollTo(0, 0);
  }

  function currentValidationError() {
    const edMod = getEditor();
    const meta = (state.editorData && state.editorData.meta) || {};
    return edMod && edMod.getValidationError ? edMod.getValidationError(state.draft, meta) : '';
  }

  function submitDraftPayload() {
    const edMod = getEditor();
    if (edMod && edMod.transformDraftForSubmit && state.editorData && state.editorData.live) {
      return edMod.transformDraftForSubmit(state.draft, state.editorData.live, state);
    }
    return state.draft;
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
      const edMod = getEditor();
      const res = await api('/preview/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        signal: controller.signal,
        body: JSON.stringify({
          draft: submitDraftPayload(),
          context: {
            subtotal: state.previewContext.subtotal,
            shipping: state.previewContext.shipping,
            addon_selected_idx: state.previewContext.addonPreviewIdx,
          },
        }),
      });
      if (seq !== state.previewSeq) {
        return false;
      }
      state.previewBusy = false;
      state.preview = { before: res.before, after: res.after };
      const hasPreviewSample = Object.keys(state.eliteSample || {}).some((k) => k !== 'unlock_counter' && state.eliteSample[k]);
      if (hasPreviewSample && edMod && edMod.computeLocalAfter) {
        state.preview.after = edMod.computeLocalAfter(state.draft, (state.editorData && state.editorData.meta) || {}, state.previewContext, state);
      }
      state.diffs = res.diffs || [];
      state.localDirtyCount = state.diffs.length;
      return true;
    } catch (err) {
      if (seq === state.previewSeq) {
        state.previewBusy = false;
      }
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
      state.previewTimer = null;
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

  async function openPublishModal() {
    if (state.busy) {
      return;
    }
    if (state.previewTimer) {
      clearTimeout(state.previewTimer);
      state.previewTimer = null;
    }
    try {
      await refreshPreview();
    } catch (err) {
      state.banner = { type: 'error', text: err.message };
      renderBannerOnly();
      return;
    }
    if (!state.diffs.length) {
      updateEditorPreviewAndBar();
      return;
    }
    state.selectedDiffKeys = state.diffs.map((d) => d.key);
    state.historyOpen = false;
    state.modalOpen = true;
    renderModalOnly();
  }

  async function publishDraft() {
    const edMod = getEditor();
    const validationErr = currentValidationError();
    if (validationErr) {
      state.banner = { type: 'error', text: validationErr };
      renderModalOnly();
      renderBannerOnly();
      return;
    }
    if (state.busy || !state.diffs.length || !state.selectedDiffKeys.length) {
      renderModalOnly();
      return;
    }
    state.busy = true;
    renderModalOnly();
    try {
      const res = await api('/publish/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify({
          draft: submitDraftPayload(),
          base_hashes: state.baseHashes,
          selected_keys: state.selectedDiffKeys,
        }),
      });
      state.modalOpen = false;
      state.autoEnabled = {};
      state.eliteSample = {};
      state.editorData.live = clone(res.live);
      state.editorData.meta = res.meta || state.editorData.meta;
      state.editorData.revisions = res.revisions || [];
      state.baseHashes = res.base_hashes || state.baseHashes;
      state.draft = clone(res.live);
      state.diffs = [];
      state.selectedDiffKeys = [];
      state.localDirtyCount = 0;
      const tip = edMod && edMod.getPostPublishTip ? edMod.getPostPublishTip(state.editorData.meta) : '';
      state.banner = {
        type: 'success',
        text: 'Published to your live store. A rollback snapshot was saved automatically.',
        tip: tip || '',
        verifyUrl: (state.editorData.meta && state.editorData.meta.verify_url) || '',
        verifyLabel: (state.editorData.meta && state.editorData.meta.verify_label) || 'Verify on Live Store',
      };
      try {
        await refreshPreview();
        await loadCatalog();
      } catch (_) {}
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
      const edMod = getEditor();
      const ctxPayload = edMod && edMod.buildContextPayload ? edMod.buildContextPayload(state.draft) : {};
      const payload = Object.assign({ revision_id: revisionId || '' }, ctxPayload);
      const res = await api('/rollback/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      state.historyOpen = false;
      state.autoEnabled = {};
      state.eliteSample = {};
      state.editorData.live = clone(res.live);
      state.editorData.meta = res.meta || state.editorData.meta;
      state.editorData.revisions = res.revisions || [];
      state.baseHashes = res.base_hashes || state.baseHashes;
      state.draft = clone(res.live);
      state.diffs = [];
      state.selectedDiffKeys = [];
      state.localDirtyCount = 0;
      const skippedNote = (res.skipped_volatile && res.skipped_volatile.length)
        ? ' Live order counters were left untouched because orders were placed.'
        : '';
      state.banner = {
        type: 'success',
        text: (revisionId
          ? 'Restored store configuration to before the selected change.'
          : 'Reverted to your previous saved settings via 1-Click Undo.') + skippedNote,
      };
      try {
        await refreshPreview();
        await loadCatalog();
      } catch (_) {}
    } catch (err) {
      state.historyOpen = false;
      state.banner = { type: 'error', text: err.message };
    } finally {
      state.busy = false;
      render();
    }
  }

  async function emergencyTurnOff() {
    if (state.busy) {
      return;
    }
    const edMod = getEditor();
    const confirmMsg = (edMod && edMod.getEmergencyOffConfirm && state.editorData)
      ? edMod.getEmergencyOffConfirm(state.editorData.live)
      : 'Turn off this module on your live store immediately? A rollback snapshot will be saved so you can Undo anytime.';
    if (!window.confirm(confirmMsg)) {
      return;
    }
    state.busy = true;
    try {
      const ctxPayload = edMod && edMod.buildContextPayload ? edMod.buildContextPayload(state.draft) : {};
      const res = await api('/emergency-off/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify(ctxPayload),
      });
      state.autoEnabled = {};
      state.eliteSample = {};
      state.editorData.live = clone(res.live);
      state.editorData.meta = res.meta || state.editorData.meta;
      state.editorData.revisions = res.revisions || [];
      state.baseHashes = res.base_hashes || state.baseHashes;
      state.draft = clone(res.live);
      state.diffs = [];
      state.selectedDiffKeys = [];
      state.localDirtyCount = 0;
      state.banner = {
        type: 'success',
        text: res.disabled
          ? 'Turned off on your live store immediately. Use Undo Now anytime to re-enable.'
          : (res.message || 'Already inactive on your live store.'),
      };
      try {
        await refreshPreview();
        await loadCatalog();
      } catch (_) {}
    } catch (err) {
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
            &#8644; Classic View
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
    const tipRow = state.banner.tip
      ? `<div class="wcj-v10-banner-tip">${esc(state.banner.tip)} ${cfg.upgradeUrl ? `<a href="${esc(cfg.upgradeUrl + '?utm_source=booster_free&utm_medium=post_publish&utm_campaign=' + encodeURIComponent(state.editorSlug || 'guided'))}" target="_blank" rel="noopener">Explore Elite &#8599;</a>` : ''}</div>`
      : '';
    return `
      <div class="wcj-v10-banner ${cls}">
        <div style="flex:1;min-width:220px;">
          <div>${esc(state.banner.text)}</div>
          ${tipRow}
        </div>
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

  function renderModuleCardHtml(m) {
    return `
      <div class="wcj-v10-mod-card">
        <div>
          <div class="wcj-v10-mod-head">
            <h3 class="wcj-v10-mod-title">${esc(m.name)}</h3>
            <span class="wcj-v10-badge ${m.enabled ? 'is-on' : ''}">${m.enabled ? 'Active' : 'Inactive'}</span>
          </div>
          <p class="wcj-v10-mod-desc">${esc(m.desc)}</p>
          <div class="wcj-v10-badges">
            <span class="wcj-v10-badge">${esc(m.category_label)}</span>
            <span class="wcj-v10-badge">${m.blocks_status === 'blocks' ? 'Checkout Blocks' : 'Classic Checkout'}</span>
            ${m.flagship_editor ? '<span class="wcj-v10-badge is-flagship">Guided Preview</span>' : ''}
          </div>
        </div>
        <div class="wcj-v10-mod-foot">
          ${m.flagship_editor
            ? `<button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-open-editor="${esc(m.flagship_editor)}">Configure &amp; Preview &rarr;</button>
               <a class="wcj-v10-nav-link" href="${esc(m.classic_url)}">Classic Settings</a>`
            : `<a class="wcj-v10-btn" href="${esc(m.classic_url)}">Configure Settings &rarr;</a>`
          }
        </div>
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

    let cardsHtml = '';
    if (filtered.length) {
      cardsHtml = filtered.map(({ mod: m }) => renderModuleCardHtml(m)).join('');
    } else {
      const closest = state.modules.filter((m) => m.flagship_editor).slice(0, 3);
      cardsHtml = `
        <div class="wcj-v10-empty-search">
          <strong>No exact match for &ldquo;${esc(state.search.trim())}&rdquo;.</strong>
          <span>Here are popular Booster modules to get started (try &ldquo;fee&rdquo;, &ldquo;invoice&rdquo;, &ldquo;button&rdquo;, &ldquo;vat&rdquo;, or &ldquo;currency&rdquo;):</span>
        </div>
        ${closest.map((m) => renderModuleCardHtml(m)).join('')}
      `;
    }

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
        ${presets.map((p) => `<button type="button" class="wcj-v10-preset-chip" data-apply-preset="${esc(p.id)}" title="Applies sample values to your draft preview only">${esc(p.label)}</button>`).join('')}
      </div>
    `;
  }

  function renderDiagnosticsHtml() {
    const edMod = getEditor();
    const meta = (state.editorData && state.editorData.meta) || {};
    const items = Array.isArray(meta.diagnostics) ? meta.diagnostics.slice() : [];
    if (edMod && edMod.getExtraDiagnostics && state.draft) {
      const extra = edMod.getExtraDiagnostics(state.draft, meta) || [];
      extra.forEach((item) => items.unshift(item));
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
    const diffCount = Math.max(state.localDirtyCount, state.diffs.length);
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
          ${renderActionButtonsHtml(diffCount, revCount, isOn)}
        </div>
      </div>
    `;
  }

  function renderActionButtonsHtml(diffCount, revCount, isOn) {
    return `
      ${isOn ? '<button type="button" class="wcj-v10-btn wcj-v10-btn-danger" data-action="emergency-off">Turn Off Now</button>' : ''}
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
      eliteSample: state.eliteSample,
      upgradeUrl: cfg.upgradeUrl || 'https://booster.io/buy-booster/',
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

  function renderDiffGroupRows(rows) {
    return rows.map((d) => {
      const checked = state.selectedDiffKeys.indexOf(d.key) !== -1;
      return `
        <label class="wcj-v10-diff-row wcj-v10-diff-check">
          <input type="checkbox" data-diff-toggle="${esc(d.key)}" ${checked ? 'checked' : ''} />
          <div style="flex:1;min-width:180px;">
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;">
              <strong>${esc(d.label)}</strong>
              <div>
                <span class="wcj-v10-diff-old">${esc(d.from)}</span> &rarr;
                <span class="wcj-v10-diff-new">${esc(d.to)}</span>
              </div>
            </div>
            ${d.impact ? `<div class="wcj-v10-diff-impact">${esc(d.impact)}</div>` : ''}
          </div>
        </label>
      `;
    }).join('');
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
    const validationErr = currentValidationError();
    const storedNote = (edMod && edMod.getStoredOnOrdersNote)
      ? edMod.getStoredOnOrdersNote()
      : 'Orders placed while active keep their numbers/answers/add-ons even if you turn this off or Undo later.';
    const goesLive = state.diffs.filter((d) => d.goes_live && !d.stored_on_orders);
    const storedOnOrders = state.diffs.filter((d) => d.stored_on_orders);
    const configOnly = state.diffs.filter((d) => !d.goes_live && !d.stored_on_orders);
    const selectedCount = state.selectedDiffKeys.length;

    return `
      <div class="wcj-v10-modal-backdrop" id="wcj-v10-modal-backdrop">
        <div class="wcj-v10-modal" role="dialog" aria-modal="true">
          <div class="wcj-v10-modal-head">
            <strong>Review &amp; Publish Changes</strong>
            <button type="button" class="wcj-v10-btn" data-action="close-modal">Close</button>
          </div>
          <div class="wcj-v10-modal-body">
            ${goesLive.length ? `
              <div class="wcj-v10-diff-group-title">Goes live for customers</div>
              ${renderDiffGroupRows(goesLive)}
            ` : ''}
            ${storedOnOrders.length ? `
              <div class="wcj-v10-diff-group-title is-orders" style="margin-top:12px;">Stored on orders</div>
              <div class="wcj-v10-diff-group-note">${esc(storedNote)}</div>
              ${renderDiffGroupRows(storedOnOrders)}
            ` : ''}
            ${configOnly.length ? `
              <div class="wcj-v10-diff-group-title" style="margin-top:12px;">Configuration details</div>
              ${renderDiffGroupRows(configOnly)}
            ` : ''}
            ${validationErr ? `<div class="wcj-v10-modal-error">${esc(validationErr)}</div>` : ''}
            <p style="font-size:12px;color:#50575e;margin:10px 0 0;">
              Uncheck anything you are not ready to push live. A rollback snapshot is saved automatically on publish.
            </p>
          </div>
          <div class="wcj-v10-modal-foot">
            <button type="button" class="wcj-v10-btn" data-action="close-modal">Keep Editing</button>
            <button type="button" class="wcj-v10-btn wcj-v10-btn-primary" id="wcj-v10-confirm-publish-btn" data-action="confirm-publish" ${(state.busy || !!validationErr || selectedCount === 0) ? 'disabled' : ''}>
              ${state.busy ? 'Publishing...' : `Confirm & Publish (${selectedCount})`}
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
    }
  }

  function renderModalOnly() {
    const slot = document.getElementById('wcj-v10-modal-slot');
    if (slot) {
      slot.innerHTML = renderModalHtml();
    }
  }

  function updateCatalogBodyOnly() {
    const bodyEl = document.getElementById('wcj-v10-catalog-body');
    if (bodyEl) {
      bodyEl.innerHTML = renderCatalogBodyHtml();
    }
    const chipsEl = document.getElementById('wcj-v10-quick-chips');
    if (chipsEl) {
      chipsEl.innerHTML = `
        <span>Quick searches:</span>
        ${['fee', 'invoice', 'button', 'sale'].map((term) => `<button type="button" class="wcj-v10-chip" data-quick-search="${term}">"${term}"</button>`).join('')}
        ${state.search ? '<button type="button" class="wcj-v10-chip" data-quick-search="">Clear</button>' : ''}
      `;
    }
  }

  function updateEditorPreviewAndBar() {
    if (previewSignature() !== state.lastPreviewSignature) {
      renderPreviewPaneOnly();
    }
    updateActionBarOnly();
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
  }

  // =========================================================================
  // SINGLE DELEGATED ROOT EVENT LISTENERS (ZERO ACCUMULATION)
  // =========================================================================
  function bindDelegatedEventsOnce() {
    root.addEventListener('click', async (e) => {
      const target = e.target;
      if (!target || !target.closest) {
        return;
      }

      if (target.id === 'wcj-v10-modal-backdrop' || target.closest('[data-action="close-modal"]')) {
        state.modalOpen = false;
        state.historyOpen = false;
        renderModalOnly();
        return;
      }

      const classicSwitch = target.closest('[data-classic-switch]');
      if (classicSwitch) {
        if (!confirmDiscardIfDirty()) {
          e.preventDefault();
        }
        return;
      }

      const navBtn = target.closest('[data-nav]');
      if (navBtn) {
        if (!confirmDiscardIfDirty()) {
          return;
        }
        const dest = navBtn.dataset.nav;
        state.view = 'catalog';
        state.catalogTab = dest === 'all' ? 'all' : 'active';
        state.editorSlug = '';
        state.banner = null;
        state.autoEnabled = {};
        state.eliteSample = {};
        state.diffs = [];
        state.selectedDiffKeys = [];
        state.localDirtyCount = 0;
        state.lastPreviewSignature = '';
        state.historyOpen = false;
        state.modalOpen = false;
        syncBrowserUrl();
        render();
        window.scrollTo(0, 0);
        return;
      }

      const actionEl = target.closest('[data-action]');
      if (actionEl) {
        const act = actionEl.dataset.action;
        if (act === 'dismiss-banner') {
          state.banner = null;
          renderBannerOnly();
          return;
        }
        if (act === 'open-publish') {
          await openPublishModal();
          return;
        }
        if (act === 'open-history') {
          state.modalOpen = false;
          state.historyOpen = true;
          renderModalOnly();
          return;
        }
        if (act === 'confirm-publish') {
          await publishDraft();
          return;
        }
        if (act === 'undo') {
          await undoLastPublish('');
          return;
        }
        if (act === 'emergency-off') {
          await emergencyTurnOff();
          return;
        }
        if (act === 'discard') {
          state.autoEnabled = {};
          state.eliteSample = {};
          state.draft = clone(state.editorData.live);
          state.diffs = [];
          state.selectedDiffKeys = [];
          state.localDirtyCount = 0;
          applyLocalPreviewImmediate();
          render();
          try {
            const updated = await refreshPreview();
            if (updated) {
              updateEditorPreviewAndBar();
            }
          } catch (err) {
            state.banner = { type: 'error', text: err.message };
            renderBannerOnly();
          }
          return;
        }
      }

      const revBtn = target.closest('[data-restore-rev]');
      if (revBtn) {
        await undoLastPublish(revBtn.dataset.restoreRev);
        return;
      }

      const subBtn = target.closest('[data-sample-sub]');
      if (subBtn) {
        state.previewContext.subtotal = Number(subBtn.dataset.sampleSub);
        applyLocalPreviewImmediate();
        try {
          const updated = await refreshPreview();
          if (updated) {
            updateEditorPreviewAndBar();
          }
        } catch (err) {
          state.banner = { type: 'error', text: err.message };
          renderBannerOnly();
        }
        return;
      }

      const presetBtn = target.closest('[data-apply-preset]');
      if (presetBtn) {
        applyPreset(presetBtn.dataset.applyPreset);
        return;
      }

      const quickBtn = target.closest('[data-quick-search]');
      if (quickBtn) {
        state.search = quickBtn.dataset.quickSearch;
        const inp = document.getElementById('wcj-v10-search');
        if (inp) {
          inp.value = state.search;
          inp.focus();
        }
        updateCatalogBodyOnly();
        return;
      }

      const tabBtn = target.closest('[data-catalog-tab]');
      if (tabBtn) {
        state.catalogTab = tabBtn.dataset.catalogTab;
        if (state.search) {
          state.search = '';
          const inp = document.getElementById('wcj-v10-search');
          if (inp) inp.value = '';
        }
        syncBrowserUrl();
        updateCatalogBodyOnly();
        root.querySelectorAll('[data-nav]').forEach((btn) => {
          const isHome = state.catalogTab === 'active';
          btn.classList.toggle('is-active', (btn.dataset.nav === 'home' && isHome) || (btn.dataset.nav === 'all' && !isHome));
        });
        return;
      }

      const catBtn = target.closest('[data-cat]');
      if (catBtn) {
        state.selectedCategory = catBtn.dataset.cat;
        updateCatalogBodyOnly();
        return;
      }

      const openBtn = target.closest('[data-open-editor]');
      if (openBtn) {
        try {
          await openEditor(openBtn.dataset.openEditor);
        } catch (err) {
          state.banner = { type: 'error', text: err.message };
          render();
        }
        return;
      }

      const edMod = getEditor();
      if (edMod && edMod.handleClick) {
        edMod.handleClick(target, state, {
          esc,
          autoEnableForField,
          renderPreviewPaneOnly,
          applyLocalPreviewImmediate,
          render,
          onDraftMutated: () => {
            applyLocalPreviewImmediate();
            schedulePreviewUpdate();
          },
        });
      }
    });

    function handleFieldEvent(el) {
      const edMod = getEditor();
      const form = document.getElementById('wcj-v10-editor-form');
      if (!edMod || !state.draft || !form) {
        return;
      }
      const key = el.dataset.field;
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
      if (edMod.shouldRerenderForm && edMod.shouldRerenderForm(key)) {
        form.innerHTML = renderEditorFormHtml();
      }
      applyLocalPreviewImmediate();
      schedulePreviewUpdate();
    }

    root.addEventListener('input', (e) => {
      const target = e.target;
      if (!target) {
        return;
      }
      if (target.id === 'wcj-v10-search') {
        state.search = target.value;
        updateCatalogBodyOnly();
        return;
      }
      const fieldEl = target.closest && target.closest('#wcj-v10-editor-form [data-field]');
      if (fieldEl && fieldEl.type !== 'checkbox' && fieldEl.tagName !== 'SELECT') {
        handleFieldEvent(fieldEl);
      }
    });

    root.addEventListener('change', (e) => {
      const target = e.target;
      if (!target) {
        return;
      }
      if (target.dataset && target.dataset.diffToggle) {
        const diffKey = target.dataset.diffToggle;
        if (target.checked) {
          if (state.selectedDiffKeys.indexOf(diffKey) === -1) {
            state.selectedDiffKeys.push(diffKey);
          }
        } else {
          state.selectedDiffKeys = state.selectedDiffKeys.filter((k) => k !== diffKey);
        }
        const confirmBtn = document.getElementById('wcj-v10-confirm-publish-btn');
        if (confirmBtn) {
          const validationErr = currentValidationError();
          confirmBtn.textContent = `Confirm & Publish (${state.selectedDiffKeys.length})`;
          confirmBtn.disabled = state.busy || !!validationErr || state.selectedDiffKeys.length === 0;
        }
        return;
      }
      const fieldEl = target.closest && target.closest('#wcj-v10-editor-form [data-field]');
      if (fieldEl && (fieldEl.type === 'checkbox' || fieldEl.tagName === 'SELECT')) {
        handleFieldEvent(fieldEl);
      }
    });
  }

  async function init() {
    bindDelegatedEventsOnce();
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
    if (state.view === 'editor' && hasPendingChanges() && !state.busy) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  init();
})();
