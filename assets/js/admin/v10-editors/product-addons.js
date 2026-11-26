(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function parseLines(raw) {
    return String(raw || '').split(/\r\n|\r|\n/).map((s) => s.trim()).filter(Boolean);
  }

  function evalPriceLine(priceStr, base) {
    const t = String(priceStr || '').trim();
    if (!t) return 0;
    if (t.endsWith('%')) {
      return Math.max(0, base * (Number(t.slice(0, -1)) || 0) / 100);
    }
    return Math.max(0, Number(t) || 0);
  }

  function renderAddonCard(card, heading, isAfter, draft, meta, previewCtx, deps) {
    const esc = deps.esc;
    const fmtMoney = deps.fmtMoney;
    const sym = (meta && meta.currency_symbol) || '$';
    const prod = (meta && meta.sample_product) || {};
    const active = !!card.active;
    const isDimmed = isAfter && !active;
    const options = Array.isArray(card.options) ? card.options : [];
    const selIdx = Number(previewCtx.addonPreviewIdx || 0);

    let controlsHtml = '<div style="font-size:12px;color:#646970;">No global add-on active.</div>';
    if (active && options.length) {
      if (card.type === 'checkbox') {
        const opt = options[0];
        const checked = isAfter ? selIdx === 0 : !!card.default_checked;
        controlsHtml = `
          <label class="wcj-v10-addon-opt ${checked ? 'is-selected' : ''}" ${isAfter ? 'data-addon-opt="toggle"' : ''}>
            <input type="checkbox" ${checked ? 'checked' : ''} ${isAfter ? '' : 'disabled'} />
            <span>${esc(opt.label)} (+${esc(fmtMoney(opt.extra, sym))})</span>
          </label>
        `;
      } else if (card.type === 'radio' || card.type === 'select') {
        const activeIdx = isAfter ? selIdx : Number(card.selected_idx !== undefined ? card.selected_idx : 0);
        controlsHtml = options.map((opt, idx) => `
          <label class="wcj-v10-addon-opt ${activeIdx === idx ? 'is-selected' : ''}" ${isAfter ? `data-addon-opt="${idx}"` : ''}>
            <input type="radio" name="wcj-pa-pv-${isAfter ? 'after' : 'before'}" ${activeIdx === idx ? 'checked' : ''} ${isAfter ? '' : 'disabled'} />
            <span>${esc(opt.label)} (+${esc(fmtMoney(opt.extra, sym))})</span>
          </label>
        `).join('');
      } else {
        const opt = options[0];
        controlsHtml = `
          <div class="wcj-v10-addon-opt">
            <span>${esc(opt.label)} (+${esc(fmtMoney(opt.extra, sym))})</span>
          </div>
        `;
      }
    }

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isDimmed ? 'is-dimmed' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(heading)}</span>
          ${isAfter ? `<span class="wcj-v10-badge ${isDimmed ? '' : 'is-on'}">${isDimmed ? 'Off in draft' : 'Active in draft'}</span>` : ''}
        </div>
        <div class="wcj-v10-ba-content" ${isAfter ? '' : 'inert'}>
          <div class="wcj-v10-pcard">
            <div class="wcj-v10-pbody">
              <div style="font-weight:700;font-size:13px;margin-bottom:6px;">${esc(prod.name || 'Sample Product')}</div>
              <div style="font-size:12px;color:#50575e;margin-bottom:8px;">Base price: <strong>${esc(fmtMoney(card.base_price || 76, sym))}</strong></div>
              <div class="wcj-v10-addon-box">
                ${card.title ? `<div style="font-weight:600;font-size:12px;margin-bottom:4px;">${esc(card.title)} ${card.required ? '<span style="color:#d63638;">*</span>' : ''}</div>` : ''}
                ${controlsHtml}
              </div>
              <div class="wcj-v10-total" style="margin-top:10px;">
                <span>Cart Item Total</span>
                <span>${esc(fmtMoney(card.cart_total || card.base_price || 76, sym))}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.product_addons = {
    title: 'Product Addons',

    urlParams(draft) {
      return draft && Number(draft.addon_index) > 1
        ? '&addon_index=' + encodeURIComponent(draft.addon_index)
        : '';
    },

    buildContextPayload(draft) {
      return draft && draft.addon_index ? { addon_index: Number(draft.addon_index) } : {};
    },

    initContext(cfg) {
      return cfg.initialAddonIndex ? { addon_index: Number(cfg.initialAddonIndex) } : {};
    },

    nonDiffKeys() {
      return ['addon_index'];
    },

    isLiveOn(live) {
      return !!(live && live.enabled && live.all_enabled && live.addon_enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && (autoEnabled.enabled || autoEnabled.all_enabled || autoEnabled.addon_enabled));
    },

    getToggleTarget(key) {
      if (['addon_title', 'addon_type', 'addon_label', 'addon_price', 'addon_required', 'addon_tooltip', 'addon_placeholder', 'position', 'ajax_enabled'].includes(key)) {
        return ['enabled', 'all_enabled', 'addon_enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const sym = (meta && meta.currency_symbol) || '$';
      return [
        {
          id: 'pa_gift_wrap',
          label: `+${sym}5 Gift Wrapping`,
          patch: {
            enabled: true,
            all_enabled: true,
            addon_enabled: true,
            addon_title: 'Extras',
            addon_type: 'checkbox',
            addon_label: 'Add Premium Gift Wrapping',
            addon_price: '5',
            addon_required: false,
          },
        },
        {
          id: 'pa_rush',
          label: `+${sym}15 Rush Handling`,
          patch: {
            enabled: true,
            all_enabled: true,
            addon_enabled: true,
            addon_title: 'Order Speed',
            addon_type: 'radio',
            addon_label: "Standard Handling (Free)\nExpress Same-Day Handling",
            addon_price: "0\n15",
            addon_required: false,
          },
        },
        {
          id: 'pa_gift_note',
          label: 'Free Gift Message',
          patch: {
            enabled: true,
            all_enabled: true,
            addon_enabled: true,
            addon_title: 'Personalize',
            addon_type: 'text',
            addon_label: 'Printed Gift Note',
            addon_price: '0',
            addon_placeholder: 'Write your message here',
            addon_required: false,
          },
        },
      ];
    },

    getValidationError(draft) {
      if (!draft || !draft.enabled || !draft.all_enabled || !draft.addon_enabled) {
        return '';
      }
      const labels = parseLines(draft.addon_label);
      const prices = parseLines(draft.addon_price);
      if (!labels.length) {
        return 'Enter an Option Label before publishing.';
      }
      if (prices.some((p) => p.includes('%') && !/^\d+%$/.test(p.trim()))) {
        return 'Percentage prices must be whole numbers (for example "5%" or "10%") to match Booster pricing rules.';
      }
      if (['radio', 'select'].includes(draft.addon_type) && labels.length !== prices.length) {
        return `Radio/Select add-ons need matching Label lines (${labels.length}) and Price lines (${prices.length}) — otherwise WooCommerce skips choices.`;
      }
      return '';
    },

    getStoredOnOrdersNote() {
      return 'Chosen add-on line items remain visible on already-placed WooCommerce orders even if you turn this module off or Undo later.';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const addonsNav = (meta && meta.addons_nav) || [];
      const multi = ['radio', 'select'].includes(draft.addon_type);
      const sampleSecond = !!(deps && deps.eliteSample && deps.eliteSample.second_addon);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup ${!isPro ? '<span class="wcj-v10-badge">1 store-wide add-on (Free)</span>' : ''}</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('product_addons')}
        ${isPro && addonsNav.length > 1
          ? `<div class="wcj-v10-field">
              <label for="wcj-pa-idx">Editing Global Add-on</label>
              <select id="wcj-pa-idx" data-field="addon_index">
                ${addonsNav.map((a) => `<option value="${esc(a.index)}" ${Number(draft.addon_index) === Number(a.index) ? 'selected' : ''}>#${esc(a.index)}: ${esc(a.label)}</option>`).join('')}
              </select>
            </div>`
          : ''}
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-pa-title">Section Title</label>
            <input id="wcj-pa-title" type="text" data-field="addon_title" value="${esc(draft.addon_title)}" placeholder="e.g. Gift Options" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-pa-type">Control Type</label>
            <select id="wcj-pa-type" data-field="addon_type">
              <option value="checkbox" ${draft.addon_type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
              <option value="radio" ${draft.addon_type === 'radio' ? 'selected' : ''}>Radio Choices</option>
              <option value="select" ${draft.addon_type === 'select' ? 'selected' : ''}>Dropdown (Select)</option>
              <option value="text" ${draft.addon_type === 'text' ? 'selected' : ''}>Text Input</option>
            </select>
          </div>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-pa-lbl">${multi ? 'Option Labels (1 per line)' : 'Option Label'}</label>
            <textarea id="wcj-pa-lbl" rows="${multi ? 3 : 2}" data-field="addon_label" placeholder="${multi ? "Standard (Free)\nGift Wrap" : 'Add Gift Wrapping'}">${esc(draft.addon_label)}</textarea>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-pa-prc">${multi ? 'Prices (1 per line, or 10%)' : 'Price (or 10%)'}</label>
            <textarea id="wcj-pa-prc" rows="${multi ? 3 : 2}" data-field="addon_price" placeholder="${multi ? "0\n5" : '5'}">${esc(draft.addon_price)}</textarea>
          </div>
        </div>
        ${multi && parseLines(draft.addon_label).length !== parseLines(draft.addon_price).length
          ? `<div class="wcj-v10-calm-note" style="border-color:#d63638;color:#8a2424;margin-bottom:10px;">
              Match Label lines (${parseLines(draft.addon_label).length}) and Price lines (${parseLines(draft.addon_price).length}) so every choice prices cleanly.
            </div>`
          : ''}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="addon_required" ${draft.addon_required ? 'checked' : ''} />
            <span>Require selection (changes archive Add to Cart buttons to Select Options)</span>
          </label>
        </div>
        ${!isPro
          ? `<div class="wcj-v10-elite-sample-box">
              <strong>Unlimited global add-ons &amp; variation targeting in Elite</strong>
              <p>Booster Free runs 1 global add-on plus per-product add-ons on up to 3 products.</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleSecond ? 'Hide 2nd add-on preview' : '+ Preview a 2nd global add-on (Elite)'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=product_addons')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`
          : ''}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label for="wcj-pa-tip">Tooltip Text</label>
              <input id="wcj-pa-tip" type="text" data-field="addon_tooltip" value="${esc(draft.addon_tooltip)}" />
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-pa-pos">Product Page Position</label>
              <select id="wcj-pa-pos" data-field="position">
                <option value="woocommerce_before_add_to_cart_button" ${draft.position === 'woocommerce_before_add_to_cart_button' ? 'selected' : ''}>Before Add to Cart button</option>
                <option value="woocommerce_after_add_to_cart_button" ${draft.position === 'woocommerce_after_add_to_cart_button' ? 'selected' : ''}>After Add to Cart button</option>
              </select>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="ajax_enabled" ${draft.ajax_enabled ? 'checked' : ''} />
                <span>Update displayed product price dynamically with Ajax</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="per_product_enabled" ${draft.per_product_enabled ? 'checked' : ''} />
                <span>Allow per-product add-ons meta box on product screens</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    shouldRerenderForm(key) {
      return key === 'addon_type';
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'addon_index') {
        if (!actions.confirmDiscardIfDirty('Switch add-on and discard unsaved draft changes?')) {
          el.value = String(state.draft.addon_index || 1);
          return true;
        }
        actions.openEditor('product_addons', { addon_index: Number(el.value) });
        return true;
      }
      return false;
    },

    handleClick(target, state, actions) {
      const optEl = target.closest('[data-addon-opt]');
      if (optEl) {
        const val = optEl.dataset.addonOpt;
        if (val === 'toggle') {
          state.previewContext.addonPreviewIdx = state.previewContext.addonPreviewIdx === 0 ? -1 : 0;
        } else {
          state.previewContext.addonPreviewIdx = Number(val);
        }
        actions.applyLocalPreviewImmediate();
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.second_addon = !state.eliteSample.second_addon;
        sampleBtn.innerHTML = state.eliteSample.second_addon ? 'Hide 2nd add-on preview' : '+ Preview a 2nd global add-on (Elite)';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx) {
      const prod = (meta && meta.sample_product) || {};
      const baseNum = Number(prod.price_num || 76);
      const decimals = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const factor = Math.pow(10, decimals);
      const r = (n) => Math.round(n * factor) / factor;

      const labels = parseLines(draft.addon_label);
      const prices = parseLines(draft.addon_price);
      const active = !!(draft.enabled && draft.all_enabled && draft.addon_enabled);
      const options = labels.map((lbl, idx) => {
        const rawP = prices[idx] !== undefined ? prices[idx] : (prices[0] || '0');
        return {
          index: idx,
          label: lbl,
          extra: r(evalPriceLine(rawP, baseNum)),
        };
      });

      const selIdx = Number(previewCtx.addonPreviewIdx !== undefined ? previewCtx.addonPreviewIdx : 0);
      const addonFee = (active && selIdx >= 0 && options[selIdx]) ? options[selIdx].extra : 0;

      return {
        active,
        title: draft.addon_title || '',
        type: draft.addon_type || 'checkbox',
        required: !!draft.addon_required,
        options,
        base_price: r(baseNum),
        addon_fee: r(addonFee),
        cart_total: r(baseNum + addonFee),
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const autoBadge = (state.autoEnabled.enabled || state.autoEnabled.all_enabled || state.autoEnabled.addon_enabled)
        ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>'
        : '';
      const sampleSecond = !!(state.eliteSample && state.eliteSample.second_addon);

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Interactive Product &amp; Cart Total</h2>
            <div class="wcj-v10-preview-meta">
              <span>Click add-on below to test live cart math</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-ba-grid">
          ${renderAddonCard(b, 'BEFORE (Live Store)', false, draft, meta, previewCtx, deps)}
          ${renderAddonCard(a, 'AFTER (Your Draft)', true, draft, meta, previewCtx, deps)}
        </div>
        ${sampleSecond
          ? `<div class="wcj-v10-elite-sample-box" style="margin-top:12px;">
              <strong>Sample Elite 2nd Global Add-on (Preview Only):</strong> <em>+Priority Insurance (+$3.00)</em> &mdash; Unlimited global add-ons available in Elite.
            </div>`
          : ''}
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
