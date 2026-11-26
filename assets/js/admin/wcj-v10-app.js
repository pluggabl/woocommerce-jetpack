/* global wcjV10Config, wp */
(function () {
  'use strict';

  const cfg = window.wcjV10Config || {};
  const root = document.getElementById('wcj-v10-root');
  if (!root || !cfg.restUrl) {
    return;
  }

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
    editorSeq: 0,
    previewSeq: 0,
    previewAbort: null,
  };

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

  function syncBrowserUrl() {
    if (!window.history || !window.history.replaceState || !cfg.dashboardUrl) {
      return;
    }
    let nextUrl = cfg.dashboardUrl;
    if (state.view === 'editor' && state.editorSlug) {
      nextUrl = cfg.dashboardUrl + '&v10_editor=' + encodeURIComponent(state.editorSlug);
      if (state.editorSlug === 'payment_gateways_fees' && state.draft && state.draft.gateway) {
        nextUrl += '&gateway=' + encodeURIComponent(state.draft.gateway);
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

  function confirmDiscardIfDirty() {
    if (state.view === 'editor' && state.diffs.length > 0) {
      return window.confirm('You have unpublished draft changes. Discard them and continue?');
    }
    return true;
  }

  // =========================================================================
  // INSTANT 0ms PREVIEW + AUTO-ENABLE ON EDIT
  // =========================================================================
  function getFieldToggleTarget(slug, key) {
    if (slug === 'payment_gateways_fees') {
      if (['label', 'fee_type', 'amount', 'min_cart', 'max_cart'].includes(key)) {
        return ['enabled'];
      }
    } else if (slug === 'pdf_invoicing') {
      if (['header_image', 'header_title', 'header_text', 'header_text_color', 'numbering_prefix', 'create_on_completed', 'attach_completed'].includes(key)) {
        return ['enabled'];
      }
    } else if (slug === 'button_price_labels') {
      if (['add_to_cart_single', 'add_to_cart_archive'].includes(key)) {
        return ['add_to_cart_enabled'];
      }
      if (['price_prefix', 'price_suffix'].includes(key)) {
        return ['price_labels_enabled'];
      }
      if (key === 'sale_badge_text') {
        return ['sale_flash_enabled', 'sale_flash_global_enabled'];
      }
      if (key === 'place_order_text') {
        return ['more_button_enabled'];
      }
    }
    return [];
  }

  function autoEnableForField(key, formEl) {
    if (!state.draft || !state.editorData) {
      return;
    }
    const toggles = getFieldToggleTarget(state.editorSlug, key);
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

  function computeLocalAfterPreview() {
    const d = state.draft;
    const meta = (state.editorData && state.editorData.meta) || {};
    if (!d) {
      return {};
    }

    if (state.editorSlug === 'payment_gateways_fees') {
      const subtotal = Math.max(0, Number(state.previewContext.subtotal) || 0);
      const shipping = Math.max(0, Number(state.previewContext.shipping) || 0);
      const baseTotal = subtotal + shipping;
      const decimals = Number.isFinite(Number(meta.decimals)) ? Number(meta.decimals) : 2;
      const factor = Math.pow(10, decimals);
      const roundNum = (n) => Math.round(n * factor) / factor;

      let feeAmount = 0;
      let reason = 'applied';
      const label = String(d.label || '').trim();
      const needsLabel = !label;

      if (!d.enabled) {
        reason = 'module_disabled';
      } else {
        const min = Number(d.min_cart) || 0;
        const max = Number(d.max_cart) || 0;
        if (baseTotal < min || (max > 0 && baseTotal > max)) {
          reason = 'outside_cart_range';
        } else {
          const val = Number(d.amount) || 0;
          feeAmount = d.fee_type === 'percent'
            ? roundNum(baseTotal * (val / 100))
            : roundNum(val);
          if (needsLabel && val !== 0) {
            reason = 'needs_label';
          } else if (needsLabel) {
            reason = 'no_label';
          }
        }
      }

      return {
        module_enabled: !!d.enabled,
        gateway: d.gateway,
        subtotal: roundNum(subtotal),
        shipping: roundNum(shipping),
        fee_label: label || (reason === 'needs_label' ? 'Gateway Fee (add label to publish)' : 'Gateway Fee'),
        fee_amount: feeAmount,
        total: roundNum(baseTotal + feeAmount),
        reason,
      };
    }

    if (state.editorSlug === 'pdf_invoicing') {
      return {
        enabled: !!d.enabled,
        header_enabled: d.header_enabled !== false,
        header_image: d.header_image || '',
        header_title: String(d.header_title || '').trim() || 'Invoice',
        header_text: String(d.header_text || '').trim() || 'Company Name',
        header_text_color: d.header_text_color || '#cccccc',
        header_line_color: d.header_line_color || d.header_text_color || '#cccccc',
        invoice_number: (d.numbering_prefix || '') + '1042',
        create_on_completed: !!d.create_on_completed,
        email_status: d.attach_completed ? 'Attaches to Completed Order email' : 'Manual download only',
      };
    }

    if (state.editorSlug === 'button_price_labels') {
      const sym = meta.currency_symbol || '$';
      const prod = meta.sample_product || {};
      const before = (state.preview && state.preview.before) || {};
      const saleOn = !!d.sale_flash_enabled && (d.sale_flash_global_enabled !== false);
      return {
        add_to_cart_enabled: !!d.add_to_cart_enabled,
        more_button_enabled: !!d.more_button_enabled,
        price_labels_enabled: !!d.price_labels_enabled,
        sale_flash_enabled: !!d.sale_flash_enabled,
        sale_flash_global_enabled: saleOn,
        add_to_cart_text: (d.add_to_cart_enabled && String(d.add_to_cart_single || '').trim()) ? d.add_to_cart_single : 'Add to cart',
        archive_text: (d.add_to_cart_enabled && String(d.add_to_cart_archive || '').trim()) ? d.add_to_cart_archive : 'Add to cart',
        place_order_text: (d.more_button_enabled && String(d.place_order_text || '').trim()) ? d.place_order_text : 'Place order',
        price_prefix: d.price_labels_enabled ? (d.price_prefix || '') : '',
        price_suffix: d.price_labels_enabled ? (d.price_suffix || '') : '',
        sample_name: prod.name || before.sample_name || 'Sample Storefront Product',
        sample_image: prod.image_url || before.sample_image || '',
        sample_regular: prod.regular_price || before.sample_regular || (sym + '95.00'),
        sample_sale: prod.sale_price !== undefined ? prod.sale_price : (before.sample_sale || (sym + '76.00')),
        sample_on_sale: prod.on_sale !== undefined ? !!prod.on_sale : (before.sample_on_sale !== undefined ? !!before.sample_on_sale : true),
        sale_badge_text: (saleOn && String(d.sale_badge_text || '').trim()) ? d.sale_badge_text : 'Sale!',
      };
    }

    return {};
  }

  function renderPreviewPaneOnly() {
    const pvSlot = document.getElementById('wcj-v10-preview-pane');
    if (pvSlot) {
      pvSlot.innerHTML = renderEditorPreviewHtml();
      bindPreviewAndActionEvents();
    }
  }

  function applyLocalPreviewImmediate() {
    if (!state.preview) {
      state.preview = { before: {}, after: {} };
    }
    state.preview.after = computeLocalAfterPreview();
    renderPreviewPaneOnly();
  }

  // =========================================================================
  // STARTER PRESETS PER FLAGSHIP EDITOR
  // =========================================================================
  function getPresetsForEditor(slug) {
    const defaults = (state.editorData && state.editorData.meta && state.editorData.meta.store_defaults) || {};
    const storeAddr = defaults.address || 'Acme Commerce Co.\n100 Market St\nSan Francisco, CA';

    if (slug === 'payment_gateways_fees') {
      return [
        {
          id: 'fee_card_3',
          label: '+3% Processing Fee',
          patch: {
            enabled: true,
            fee_type: 'percent',
            amount: '3',
            label: 'Card Processing Fee',
          },
        },
        {
          id: 'disc_bank_5',
          label: '-5% Bank Discount',
          patch: {
            enabled: true,
            fee_type: 'percent',
            amount: '-5',
            label: 'Bank Transfer Discount',
          },
        },
        {
          id: 'fee_cod_5',
          label: '$5 COD Handling',
          patch: {
            enabled: true,
            fee_type: 'fixed',
            amount: '5',
            label: 'COD Handling Fee',
          },
        },
      ];
    }

    if (slug === 'pdf_invoicing') {
      return [
        {
          id: 'inv_blue',
          label: 'Modern Blue',
          patch: {
            enabled: true,
            header_enabled: true,
            header_title: 'Invoice',
            header_text: storeAddr,
            header_text_color: '#0073aa',
            header_line_color: '#0073aa',
            numbering_prefix: 'INV-2026-',
          },
        },
        {
          id: 'inv_dark',
          label: 'Minimal Dark',
          patch: {
            enabled: true,
            header_enabled: true,
            header_title: 'Tax Invoice',
            header_text: storeAddr,
            header_text_color: '#1a1a2e',
            header_line_color: '#1a1a2e',
            numbering_prefix: 'INV-',
          },
        },
        {
          id: 'inv_auto',
          label: 'Auto-Email on Completed',
          patch: {
            enabled: true,
            header_enabled: true,
            create_on_completed: true,
            attach_completed: true,
          },
        },
      ];
    }

    if (slug === 'button_price_labels') {
      return [
        {
          id: 'lbl_buy',
          label: 'Buy Now CTA',
          patch: {
            add_to_cart_enabled: true,
            add_to_cart_single: 'Buy Now',
            add_to_cart_archive: 'Quick Buy',
          },
        },
        {
          id: 'lbl_vat',
          label: 'EU VAT Suffix',
          patch: {
            price_labels_enabled: true,
            price_suffix: ' incl. VAT',
          },
        },
        {
          id: 'lbl_sale',
          label: 'Save 20% Badge',
          patch: {
            sale_flash_enabled: true,
            sale_flash_global_enabled: true,
            sale_badge_text: 'Save 20%',
          },
        },
      ];
    }

    return [];
  }

  function applyPreset(presetId) {
    const list = getPresetsForEditor(state.editorSlug);
    const preset = list.find((p) => p.id === presetId);
    if (!preset || !state.draft) {
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

  async function openEditor(slug, gateway) {
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
    state.historyOpen = false;
    state.editorSlug = slug;
    state.view = 'editor';
    syncBrowserUrl();

    const qs = gateway ? '?gateway=' + encodeURIComponent(gateway) : '';
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
    const statusEl = document.getElementById('wcj-v10-preview-status');
    if (statusEl) {
      statusEl.textContent = 'Syncing preview...';
    }
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
    try {
      const gateway = state.draft && state.draft.gateway ? state.draft.gateway : '';
      const res = await api('/rollback/' + encodeURIComponent(state.editorSlug), {
        method: 'POST',
        body: JSON.stringify({
          gateway,
          revision_id: revisionId || '',
        }),
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
          ? 'Restored selected revision safely.'
          : 'Reverted to your previous saved settings via 1-Click Undo.',
      };
      await refreshPreview();
      await loadCatalog();
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

  function renderEditorHeaderHtml() {
    const ed = state.editorData;
    const titleMap = {
      payment_gateways_fees: 'Payment Gateways Fees & Discounts',
      pdf_invoicing: 'PDF Invoicing & Packing Slips',
      button_price_labels: 'Button, Price & Sale Labels',
    };
    const isOn = state.editorSlug === 'button_price_labels'
      ? (ed.live.add_to_cart_enabled || ed.live.more_button_enabled || ed.live.price_labels_enabled || ed.live.sale_flash_enabled)
      : !!ed.live.enabled;
    const revCount = (ed.revisions && ed.revisions.length) || 0;
    const diffCount = state.diffs.length;

    let classicLinks = `<a class="wcj-v10-nav-link" href="${esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    if (state.editorSlug === 'button_price_labels' && ed.meta && ed.meta.classic_sections) {
      const cs = ed.meta.classic_sections;
      classicLinks = `
        <details class="wcj-v10-dropdown">
          <summary class="wcj-v10-btn" style="padding:4px 10px;font-size:12px;">All Classic Options &#9662;</summary>
          <div class="wcj-v10-dropdown-menu">
            <a href="${esc(cs.add_to_cart)}">Add to Cart Button Labels</a>
            <a href="${esc(cs.price_labels)}">Custom Price Labels</a>
            <a href="${esc(cs.sale_flash)}">Sale Flash Badge</a>
            <a href="${esc(cs.more_button_labels)}">Place Order Button Label</a>
          </div>
        </details>
      `;
    }

    return `
      <div class="wcj-v10-editor-head">
        <div class="wcj-v10-editor-title">
          <button type="button" class="wcj-v10-btn" data-nav="home">&larr; Home</button>
          <div>
            <h1>${esc(titleMap[state.editorSlug] || state.editorSlug)}</h1>
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

  function renderPresetChipsHtml(slug) {
    const presets = getPresetsForEditor(slug);
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
    const a = (state.preview && state.preview.after) || {};

    if (state.editorSlug === 'payment_gateways_fees' && a.reason === 'needs_label') {
      items.unshift({
        level: 'warning',
        text: 'Checkout Line Label is required before a fee can be saved cleanly to WooCommerce.',
      });
    }
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

  function renderFeesFormHtml() {
    const d = state.draft;
    const gateways = (state.editorData.meta && state.editorData.meta.gateways) || [];
    return `
      <div class="wcj-v10-section-head">
        <h2>Simple Setup</h2>
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="enabled" ${d.enabled ? 'checked' : ''} />
          <span>Module Active</span>
        </label>
      </div>
      ${renderPresetChipsHtml('payment_gateways_fees')}
      <div class="wcj-v10-field">
        <label for="wcj-f-gateway">Payment Method</label>
        <select id="wcj-f-gateway" data-field="gateway">
          ${gateways.map((g) => `<option value="${esc(g.id)}" ${d.gateway === g.id ? 'selected' : ''}>${esc(g.title)}${g.enabled ? '' : ' (Disabled in Woo)'}</option>`).join('')}
        </select>
      </div>
      <div class="wcj-v10-row-2">
        <div class="wcj-v10-field">
          <label for="wcj-f-type">Fee or Discount Type</label>
          <select id="wcj-f-type" data-field="fee_type">
            <option value="fixed" ${d.fee_type === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
            <option value="percent" ${d.fee_type === 'percent' ? 'selected' : ''}>Percentage (%)</option>
          </select>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-f-amount">Value (negative = discount)</label>
          <input id="wcj-f-amount" type="number" step="0.01" data-field="amount" value="${esc(d.amount)}" />
        </div>
      </div>
      <div class="wcj-v10-field">
        <label for="wcj-f-label">Checkout Line Label (required to apply)</label>
        <input id="wcj-f-label" type="text" data-field="label" value="${esc(d.label)}" placeholder="e.g. Payment Processing Fee" />
      </div>
      <div class="wcj-v10-row-2">
        <div class="wcj-v10-field">
          <label for="wcj-f-min">Min Cart Total</label>
          <input id="wcj-f-min" type="number" step="0.01" min="0" data-field="min_cart" value="${esc(d.min_cart)}" />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-f-max">Max Cart Total (0 = no max)</label>
          <input id="wcj-f-max" type="number" step="0.01" min="0" data-field="max_cart" value="${esc(d.max_cart)}" />
        </div>
      </div>
    `;
  }

  function renderFeesPreviewHtml() {
    const pv = state.preview || { before: {}, after: {} };
    const b = pv.before || {};
    const a = pv.after || {};
    const sym = (state.editorData.meta && state.editorData.meta.currency_symbol) || '$';
    const feeChanged = Number(b.fee_amount) !== Number(a.fee_amount) || b.fee_label !== a.fee_label;
    const delta = Number(a.total || 0) - Number(b.total || 0);
    const isOff = !state.draft.enabled;
    const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

    return `
      <div class="wcj-v10-section-head">
        <div>
          <h2>Live Before &amp; After Cart Simulator ${autoBadge}</h2>
          <span id="wcj-v10-preview-status" class="wcj-v10-updating">${state.previewBusy ? 'Syncing preview...' : ''}</span>
        </div>
        <div class="wcj-v10-chips" style="margin-top:0;">
          ${[25, 75, 250].map((v) => `<button type="button" class="wcj-v10-chip ${Number(state.previewContext.subtotal) === v ? 'is-active' : ''}" data-sample-sub="${v}">${esc(sym + v)}</button>`).join('')}
        </div>
      </div>
      <div class="wcj-v10-ba-grid">
        <div class="wcj-v10-ba-box">
          <span class="wcj-v10-ba-tag">BEFORE (Live Store)</span>
          <div class="wcj-v10-ba-content">
            <div class="wcj-v10-line"><span>Items Subtotal</span><span>${esc(fmtMoney(b.subtotal, sym))}</span></div>
            <div class="wcj-v10-line"><span>Shipping</span><span>${esc(fmtMoney(b.shipping, sym))}</span></div>
            <div class="wcj-v10-line"><span>${esc(b.fee_label || 'Gateway Fee')}</span><span>${Number(b.fee_amount) !== 0 ? esc(fmtMoney(b.fee_amount, sym)) : 'None'}</span></div>
            <div class="wcj-v10-total"><span>Total</span><span>${esc(fmtMoney(b.total, sym))}</span></div>
          </div>
        </div>
        <div class="wcj-v10-ba-box is-after ${isOff ? 'is-dimmed' : ''}">
          <span class="wcj-v10-ba-tag">AFTER (Your Draft)</span>
          ${isOff ? '<div class="wcj-v10-off-banner">Module is Off in draft &mdash; no fee will apply at checkout.</div>' : ''}
          <div class="wcj-v10-ba-content">
            <div class="wcj-v10-line"><span>Items Subtotal</span><span>${esc(fmtMoney(a.subtotal, sym))}</span></div>
            <div class="wcj-v10-line"><span>Shipping</span><span>${esc(fmtMoney(a.shipping, sym))}</span></div>
            <div class="wcj-v10-line ${feeChanged ? 'is-changed' : ''}">
              <span>${esc(a.fee_label || 'Gateway Fee')}</span>
              <span>${Number(a.fee_amount) !== 0 ? esc(fmtMoney(a.fee_amount, sym)) : 'None'}</span>
            </div>
            <div class="wcj-v10-total"><span>Total</span><span>${esc(fmtMoney(a.total, sym))}</span></div>
            ${delta !== 0 ? `<div class="wcj-v10-delta">Order total changes by ${delta > 0 ? '+' : ''}${esc(fmtMoney(delta, sym))} for this cart.</div>` : ''}
          </div>
        </div>
      </div>
      ${renderDiagnosticsHtml()}
    `;
  }

  function renderInvoiceFormHtml() {
    const d = state.draft;
    const swatches = ['#cccccc', '#0073aa', '#00507c', '#2f5d50', '#1a1a2e', '#ff5722'];
    return `
      <div class="wcj-v10-section-head">
        <h2>Simple Setup</h2>
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="enabled" ${d.enabled ? 'checked' : ''} />
          <span>Module Active</span>
        </label>
      </div>
      ${renderPresetChipsHtml('pdf_invoicing')}
      <div class="wcj-v10-field">
        <label>Brand Color</label>
        <div class="wcj-v10-swatches">
          ${swatches.map((hex) => `<button type="button" class="wcj-v10-swatch ${d.header_text_color === hex ? 'is-selected' : ''}" data-inv-swatch="${hex}" style="background:${hex}" title="${hex}"></button>`).join('')}
          <input type="color" data-field="header_text_color" value="${esc(d.header_text_color || '#cccccc')}" />
        </div>
      </div>
      <div class="wcj-v10-field">
        <label for="wcj-i-img">Logo URL</label>
        <div style="display:flex;gap:8px;">
          <input id="wcj-i-img" type="text" data-field="header_image" value="${esc(d.header_image)}" placeholder="https://..." />
          ${window.wp && wp.media ? '<button type="button" class="wcj-v10-btn" data-action="pick-logo">Choose</button>' : ''}
        </div>
      </div>
      <div class="wcj-v10-row-2">
        <div class="wcj-v10-field">
          <label for="wcj-i-title">Document Title</label>
          <input id="wcj-i-title" type="text" data-field="header_title" value="${esc(d.header_title)}" />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-i-prefix">Number Prefix</label>
          <input id="wcj-i-prefix" type="text" data-field="numbering_prefix" value="${esc(d.numbering_prefix)}" placeholder="INV-2026-" />
        </div>
      </div>
      <div class="wcj-v10-field">
        <label for="wcj-i-text">Business Name &amp; Address Header</label>
        <textarea id="wcj-i-text" rows="3" data-field="header_text">${esc(d.header_text)}</textarea>
      </div>
      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="create_on_completed" ${d.create_on_completed ? 'checked' : ''} />
          <span>Auto-create PDF when Order is Completed</span>
        </label>
      </div>
      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="attach_completed" ${d.attach_completed ? 'checked' : ''} />
          <span>Attach PDF to Completed Order emails</span>
        </label>
      </div>
    `;
  }

  function renderInvoiceSheetHtml(doc, label, isAfter) {
    const color = doc.header_text_color || '#cccccc';
    const sym = (state.editorData.meta && state.editorData.meta.currency_symbol) || '$';
    const isOff = isAfter && !state.draft.enabled;

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isOff ? 'is-dimmed' : ''}">
        <span class="wcj-v10-ba-tag">${esc(label)}</span>
        ${isOff ? '<div class="wcj-v10-off-banner">PDF Invoicing is Off in draft &mdash; documents stay paused.</div>' : ''}
        <div class="wcj-v10-ba-content">
          <div class="wcj-v10-sheet">
            <div class="wcj-v10-sheet-head" style="border-color:${esc(color)}">
              <div style="min-width:0;flex:1;">
                ${doc.header_image ? `<img src="${esc(doc.header_image)}" alt="" style="max-height:32px;margin-bottom:4px;display:block;" />` : ''}
                <div class="wcj-v10-sheet-biz">${esc(doc.header_text || 'Company Name')}</div>
              </div>
              <div style="text-align:right;flex-shrink:0;max-width:100%;">
                <div style="font-weight:700;font-size:14px;color:${esc(color)}">${esc(doc.header_title || 'Invoice')}</div>
                <div class="wcj-v10-sheet-num">#${esc(doc.invoice_number || '1042')}</div>
              </div>
            </div>
            <div style="color:#50575e;margin-bottom:6px;">
              ${doc.create_on_completed ? 'Auto-creates on Completed &bull; ' : 'Manual creation &bull; '}
              ${esc(doc.email_status || '')}
            </div>
            <table class="wcj-v10-sheet-table">
              <thead>
                <tr style="background:${esc(color)}">
                  <th>Item</th>
                  <th style="text-align:right;">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>Sample Product &times; 1</td><td style="text-align:right;">${esc(sym)}76.00</td></tr>
                <tr><td>Shipping</td><td style="text-align:right;">${esc(sym)}10.00</td></tr>
              </tbody>
            </table>
            <div style="text-align:right;font-weight:700;">Total: ${esc(sym)}86.00</div>
          </div>
        </div>
      </div>
    `;
  }

  function renderInvoicePreviewHtml() {
    const pv = state.preview || { before: {}, after: {} };
    const mode = state.previewContext.invoicePreviewMode || 'split';
    const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

    return `
      <div class="wcj-v10-section-head">
        <div>
          <h2>Live Before &amp; After Document Preview ${autoBadge}</h2>
          <span id="wcj-v10-preview-status" class="wcj-v10-updating">${state.previewBusy ? 'Syncing preview...' : ''}</span>
        </div>
        <div class="wcj-v10-tabs">
          <button type="button" class="wcj-v10-tab ${mode === 'split' ? 'is-active' : ''}" data-inv-pv="split">Side-by-Side</button>
          <button type="button" class="wcj-v10-tab ${mode === 'single' ? 'is-active' : ''}" data-inv-pv="single">After Only</button>
        </div>
      </div>
      <div class="${mode === 'split' ? 'wcj-v10-ba-grid' : ''}">
        ${mode === 'split' ? renderInvoiceSheetHtml(pv.before || {}, 'BEFORE (Live Store)', false) : ''}
        ${renderInvoiceSheetHtml(pv.after || {}, 'AFTER (Your Draft)', true)}
      </div>
      ${renderDiagnosticsHtml()}
    `;
  }

  function renderLabelsFormHtml() {
    const d = state.draft;
    const prefixLocked = !!(state.editorData.meta && state.editorData.meta.price_prefix_locked);
    return `
      <div class="wcj-v10-section-head">
        <h2>Simple Setup</h2>
      </div>
      ${renderPresetChipsHtml('button_price_labels')}
      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="add_to_cart_enabled" ${d.add_to_cart_enabled ? 'checked' : ''} />
          <span>Enable Add to Cart Button Labels</span>
        </label>
      </div>
      <div class="wcj-v10-row-2">
        <div class="wcj-v10-field">
          <label for="wcj-l-single">Single Product Button</label>
          <input id="wcj-l-single" type="text" data-field="add_to_cart_single" value="${esc(d.add_to_cart_single)}" />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-l-archive">Shop / Archive Button</label>
          <input id="wcj-l-archive" type="text" data-field="add_to_cart_archive" value="${esc(d.add_to_cart_archive)}" />
        </div>
      </div>

      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="price_labels_enabled" ${d.price_labels_enabled ? 'checked' : ''} />
          <span>Enable Price Labels</span>
        </label>
      </div>
      <div class="wcj-v10-row-2">
        <div class="wcj-v10-field">
          <label for="wcj-l-prefix">Before Price ${prefixLocked ? '<span class="wcj-v10-badge">Plus / Elite</span>' : ''}</label>
          <input id="wcj-l-prefix" type="text" data-field="price_prefix" value="${esc(d.price_prefix)}" ${prefixLocked ? 'disabled' : ''} />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-l-suffix">After Price</label>
          <input id="wcj-l-suffix" type="text" data-field="price_suffix" value="${esc(d.price_suffix)}" placeholder=" incl. VAT" />
        </div>
      </div>

      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="sale_flash_enabled" ${d.sale_flash_enabled ? 'checked' : ''} />
          <span>Enable Custom Sale Badge</span>
        </label>
      </div>
      <div class="wcj-v10-field">
        <label for="wcj-l-sale">Sale Badge Text</label>
        <input id="wcj-l-sale" type="text" data-field="sale_badge_text" value="${esc(d.sale_badge_text)}" placeholder="Sale!" />
      </div>

      <div class="wcj-v10-field">
        <label class="wcj-v10-check">
          <input type="checkbox" data-field="more_button_enabled" ${d.more_button_enabled ? 'checked' : ''} />
          <span>Enable Checkout Place Order Label</span>
        </label>
      </div>
      <div class="wcj-v10-field">
        <label for="wcj-l-place">Checkout Place Order Button Text</label>
        <input id="wcj-l-place" type="text" data-field="place_order_text" value="${esc(d.place_order_text)}" placeholder="Place order" />
      </div>
    `;
  }

  function renderProductCardHtml(card, label, isAfter, compare) {
    const btnDiff = compare && card.add_to_cart_text !== compare.add_to_cart_text;
    const priceDiff = compare && (card.price_prefix !== compare.price_prefix || card.price_suffix !== compare.price_suffix);
    const badgeDiff = compare && card.sale_badge_text !== compare.sale_badge_text;
    const badgeText = card.sale_badge_text || 'Sale!';
    const showOnSale = card.sample_on_sale !== false && !!card.sample_sale;

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''}">
        <span class="wcj-v10-ba-tag">${esc(label)}</span>
        <div class="wcj-v10-pcard">
          <div class="wcj-v10-pimg">
            ${showOnSale ? `<span class="wcj-v10-sale-pill ${badgeDiff ? 'wcj-v10-highlight' : ''}">${esc(badgeText)}</span>` : ''}
            ${card.sample_image
              ? `<img src="${esc(card.sample_image)}" alt="${esc(card.sample_name || 'Product')}" />`
              : '<span>PRODUCT IMAGE</span>'}
          </div>
          <div class="wcj-v10-pbody">
            <div style="font-weight:600;">${esc(card.sample_name || 'Sample Storefront Product')}</div>
            <div class="wcj-v10-pprice ${priceDiff ? 'wcj-v10-highlight' : ''}" style="border-radius:6px;padding:3px;">
              <span>${esc(card.price_prefix || '')}</span>
              ${showOnSale
                ? `<span style="text-decoration:line-through;color:#8c8f94;">${esc(card.sample_regular || '$95.00')}</span>
                   <strong>${esc(card.sample_sale || '$76.00')}</strong>`
                : `<strong>${esc(card.sample_regular || '$95.00')}</strong>`}
              <span>${esc(card.price_suffix || '')}</span>
            </div>
            <div class="wcj-v10-pbtn ${btnDiff ? 'wcj-v10-highlight' : ''}">${esc(card.add_to_cart_text || 'Add to cart')}</div>
          </div>
        </div>
      </div>
    `;
  }

  function renderLabelsPreviewHtml() {
    const pv = state.preview || { before: {}, after: {} };
    const b = pv.before || {};
    const a = pv.after || {};
    const anyAuto = Object.keys(state.autoEnabled).some((k) => state.autoEnabled[k]);
    const autoBadge = anyAuto ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

    return `
      <div class="wcj-v10-section-head">
        <div>
          <h2>Live Before &amp; After Storefront Card ${autoBadge}</h2>
          <span id="wcj-v10-preview-status" class="wcj-v10-updating">${state.previewBusy ? 'Syncing preview...' : ''}</span>
        </div>
      </div>
      <div class="wcj-v10-ba-grid">
        ${renderProductCardHtml(b, 'BEFORE (Live Store)', false, null)}
        ${renderProductCardHtml(a, 'AFTER (Your Draft)', true, b)}
      </div>
      <div class="wcj-v10-extra-preview">
        <div class="wcj-v10-extra-row">
          <strong>Shop / Archive Button:</strong>
          <span>${esc(b.archive_text || 'Add to cart')} &rarr; <strong>${esc(a.archive_text || 'Add to cart')}</strong></span>
        </div>
        <div class="wcj-v10-extra-row">
          <strong>Checkout Place Order Button:</strong>
          <span>${esc(b.place_order_text || 'Place order')} &rarr; <strong>${esc(a.place_order_text || 'Place order')}</strong></span>
        </div>
      </div>
      ${renderDiagnosticsHtml()}
    `;
  }

  function renderEditorPreviewHtml() {
    if (state.editorSlug === 'payment_gateways_fees') return renderFeesPreviewHtml();
    if (state.editorSlug === 'pdf_invoicing') return renderInvoicePreviewHtml();
    if (state.editorSlug === 'button_price_labels') return renderLabelsPreviewHtml();
    return '';
  }

  function renderEditorFormHtml() {
    if (state.editorSlug === 'payment_gateways_fees') return renderFeesFormHtml();
    if (state.editorSlug === 'pdf_invoicing') return renderInvoiceFormHtml();
    if (state.editorSlug === 'button_price_labels') return renderLabelsFormHtml();
    return '';
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
                ? revs.map((rev) => `
                    <div class="wcj-v10-rev-row">
                      <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;">${esc(rev.timestamp || '')}</div>
                        <div style="color:#50575e;">${esc(rev.summary || 'Saved configuration')}</div>
                      </div>
                      <button type="button" class="wcj-v10-btn" data-restore-rev="${esc(rev.id)}" ${state.busy ? 'disabled' : ''}>
                        Restore
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
            <p style="font-size:12px;color:#50575e;margin:10px 0 0;">
              A snapshot of your previous values is saved automatically so you can Undo in one click.
            </p>
          </div>
          <div class="wcj-v10-modal-foot">
            <button type="button" class="wcj-v10-btn" data-action="close-modal">Keep Editing</button>
            <button type="button" class="wcj-v10-btn wcj-v10-btn-primary" data-action="confirm-publish" ${state.busy ? 'disabled' : ''}>
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
    renderPreviewPaneOnly();
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
    bindAllEvents();
  }

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
        state.historyOpen = false;
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
    if (form && state.draft) {
      form.querySelectorAll('[data-apply-preset]').forEach((btn) => {
        btn.addEventListener('click', () => applyPreset(btn.dataset.applyPreset));
      });

      form.querySelectorAll('[data-field]').forEach((el) => {
        const key = el.dataset.field;
        const evt = el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(evt, async () => {
          if (key === 'gateway' && state.editorSlug === 'payment_gateways_fees') {
            if (state.diffs.length > 0 && !window.confirm('Switch gateway and discard unsaved draft changes?')) {
              el.value = state.draft.gateway;
              return;
            }
            await openEditor('payment_gateways_fees', el.value);
            return;
          }
          if (el.type === 'checkbox') {
            state.draft[key] = el.checked;
            delete state.autoEnabled[key];
            if (key === 'sale_flash_enabled') {
              state.draft.sale_flash_global_enabled = el.checked;
              delete state.autoEnabled.sale_flash_global_enabled;
            }
          } else {
            state.draft[key] = el.value;
            autoEnableForField(key, form);
            if (key === 'header_text_color') {
              state.draft.header_line_color = el.value;
              state.draft.header_enabled = true;
            }
            if (state.editorSlug === 'pdf_invoicing' && ['header_image', 'header_title', 'header_text'].includes(key)) {
              state.draft.header_enabled = true;
            }
          }
          applyLocalPreviewImmediate();
          schedulePreviewUpdate();
        });
      });

      form.querySelectorAll('[data-inv-swatch]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const hex = btn.dataset.invSwatch;
          state.draft.header_text_color = hex;
          state.draft.header_line_color = hex;
          state.draft.header_enabled = true;
          autoEnableForField('header_text_color', form);
          const colorInput = form.querySelector('input[data-field="header_text_color"]');
          if (colorInput) colorInput.value = hex;
          form.querySelectorAll('[data-inv-swatch]').forEach((s) => s.classList.toggle('is-selected', s === btn));
          applyLocalPreviewImmediate();
          schedulePreviewUpdate();
        });
      });

      const pickLogo = form.querySelector('[data-action="pick-logo"]');
      if (pickLogo && window.wp && wp.media) {
        pickLogo.addEventListener('click', () => {
          const frame = wp.media({ title: 'Select Invoice Logo', multiple: false, library: { type: 'image' } });
          frame.on('select', () => {
            const att = frame.state().get('selection').first().toJSON();
            if (att && att.url) {
              state.draft.header_image = att.url;
              state.draft.header_enabled = true;
              autoEnableForField('header_image', form);
              const inp = form.querySelector('input[data-field="header_image"]');
              if (inp) inp.value = att.url;
              applyLocalPreviewImmediate();
              schedulePreviewUpdate();
            }
          });
          frame.open();
        });
      }
    }
  }

  async function init() {
    try {
      await loadCatalog();
      if (state.editorSlug) {
        await openEditor(state.editorSlug, cfg.initialGateway || '');
      } else {
        render();
      }
    } catch (err) {
      root.innerHTML = `<div class="wcj-v10-banner is-error"><span>${esc(err.message)}</span></div>`;
    }
  }

  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      const inp = document.getElementById('wcj-v10-search');
      if (inp) {
        e.preventDefault();
        inp.focus();
      }
    }
  });

  init();
})();
