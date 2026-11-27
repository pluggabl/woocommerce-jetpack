(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function fmtCur(amt, sym, dec) {
    const val = Number.isFinite(Number(amt)) ? Number(amt) : 0;
    return (sym || '$') + val.toFixed(Number.isFinite(Number(dec)) ? Number(dec) : 2);
  }

  function convertAmt(baseAmt, rateStr, rounding, prec) {
    if (String(rateStr) === '1') {
      return Number(baseAmt);
    }
    const p = Number(baseAmt) * Number(rateStr || 1);
    if (rounding === 'round') {
      const f = Math.pow(10, prec);
      return Math.round((p + Number.EPSILON) * f) / f;
    }
    if (rounding === 'round_up') {
      return Math.ceil(p);
    }
    if (rounding === 'round_down') {
      return Math.floor(p);
    }
    return p;
  }

  window.wcjV10Editors.multicurrency = {
    title: 'Multicurrency (Currency Switcher)',

    urlParams() {
      return '';
    },

    buildContextPayload() {
      return {};
    },

    initContext() {
      return {};
    },

    nonDiffKeys() {
      return [];
    },

    transformDraftForSubmit(draft, live) {
      if (!draft || typeof draft !== 'object') {
        return draft;
      }
      const copy = Object.assign({}, draft);
      if (copy.rate_update_mode === 'auto' && live) {
        [1, 2, 3].forEach((s) => {
          const k = 'rate_' + s;
          if (String(copy[k] || '') === String(live[k] || '')) {
            delete copy[k];
          }
        });
      }
      return copy;
    },

    isLiveOn(live) {
      return !!(live && live.enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && autoEnabled.enabled);
    },

    getToggleTarget(key) {
      if (['total_currencies', 'currency_1', 'rate_1', 'currency_2', 'rate_2', 'currency_3', 'rate_3', 'rounding', 'rounding_precision', 'revert', 'switcher_template', 'convert_shipping', 'compat_free_ship', 'compat_coupons'].includes(key)) {
        return ['enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const base = String((meta && meta.base_currency) || 'USD').toUpperCase();
      const isPro = !!(meta && meta.is_pro);
      const sec = base === 'USD' ? 'EUR' : 'USD';
      const secRate = base === 'USD' ? '0.92' : '1.08';
      const presets = [
        {
          id: 'mc_eur',
          label: `${base} + ${sec} (Clean .00 Round)`,
          patch: {
            enabled: true,
            total_currencies: '2',
            currency_1: base,
            rate_1: '1',
            currency_2: sec,
            rate_2: secRate,
            rounding: 'round',
            rounding_precision: '2',
            revert: 'no',
          },
        },
      ];
      if (isPro) {
        presets.push({
          id: 'mc_three_auto',
          label: `${base} + EUR + GBP (Auto Rates)`,
          patch: {
            enabled: true,
            total_currencies: '3',
            currency_1: base,
            rate_1: '1',
            currency_2: 'EUR',
            rate_2: '0.92',
            currency_3: 'GBP',
            rate_3: '0.79',
            rate_update_mode: 'auto',
            convert_shipping: true,
            compat_free_ship: true,
            compat_coupons: true,
            revert: 'no',
          },
        });
      }
      return presets;
    },

    getValidationError(draft, meta) {
      const isPro = !!(meta && meta.is_pro);
      if (!draft || !draft.enabled) {
        return '';
      }
      const conflicts = (meta && Array.isArray(meta.mc_conflicts)) ? meta.mc_conflicts : [];
      if (conflicts.length) {
        return `Another currency switcher is active (${conflicts.join(', ')}). Disable it before enabling Booster Multicurrency.`;
      }
      const slots = isPro ? Math.max(2, Math.min(3, Number(draft.total_currencies || 2))) : 2;
      for (let s = 1; s <= slots; s++) {
        const r = String(draft['rate_' + s] || '').trim();
        if (!r || !Number.isFinite(Number(r)) || Number(r) <= 0) {
          return `Exchange rate for Currency #${s} must be greater than 0 so prices never drop to $0.`;
        }
      }
      if (draft.revert !== 'no' && meta && meta.is_blocks) {
        return 'Your Cart/Checkout uses WooCommerce Blocks, where Revert to base currency cannot fire. Set Revert to "Do not revert".';
      }
      return '';
    },

    getExtraDiagnostics(draft, meta) {
      const items = [];
      if (!draft || !meta) {
        return items;
      }
      const base = String(meta.base_currency || 'USD').toUpperCase();
      const slots = (meta.is_pro && Number(draft.total_currencies) >= 3) ? 3 : 2;
      let hasBase = false;
      for (let s = 1; s <= slots; s++) {
        if (String(draft['currency_' + s] || '').toUpperCase() === base) {
          hasBase = true;
        }
      }
      if (['round_up', 'round_down'].includes(draft.rounding) && !hasBase) {
        items.push({
          level: 'warning',
          text: `Base currency (${base}) is missing from active slots while Round Up/Down is chosen — Booster will apply whole-unit rounding to ${base} prices unless you keep ${base} at rate 1.`,
        });
      }
      return items;
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const isAutoRates = isPro && draft.rate_update_mode === 'auto';
      const rawTot = Number((meta && meta.raw_total_currencies) || draft.total_currencies || 2);
      const base = String((meta && meta.base_currency) || 'USD').toUpperCase();
      const currencies = (meta && Array.isArray(meta.currencies)) ? meta.currencies : [{ code: base, name: base, symbol: '$' }];
      const showSlot3 = isPro && Number(draft.total_currencies) >= 3;
      const sampleThird = !!(deps && deps.eliteSample && deps.eliteSample.third_currency);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      const renderCurSelect = (id, field, val) => `
        <select id="${esc(id)}" data-field="${esc(field)}">
          ${currencies.map((c) => `<option value="${esc(c.code)}" ${String(val).toUpperCase() === c.code ? 'selected' : ''}>${esc(c.code)} &mdash; ${esc(c.name)} (${esc(c.symbol)})</option>`).join('')}
        </select>
      `;

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup ${!isPro ? '<span class="wcj-v10-badge">2 currencies (Free)</span>' : ''}</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('multicurrency')}
        <div class="wcj-v10-calm-note" style="margin-bottom:14px;">
          Store Base Currency: <strong>${esc(base)}</strong> &bull;
          1 ${esc(base)} = ${esc(draft.rate_2 || '0.92')} ${esc(draft.currency_2 || 'EUR')}
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-mc-c1">Currency #1 (Shop Base Recommended)</label>
            ${renderCurSelect('wcj-mc-c1', 'currency_1', draft.currency_1)}
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-mc-r1">Rate #1 (1 ${esc(base)} =${isAutoRates ? ' &mdash; auto-synced' : ''})</label>
            <input id="wcj-mc-r1" type="number" step="0.0001" min="0.0001" data-field="rate_1" value="${esc(draft.rate_1)}" ${isAutoRates ? 'readonly' : ''} />
          </div>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-mc-c2">Currency #2</label>
            ${renderCurSelect('wcj-mc-c2', 'currency_2', draft.currency_2)}
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-mc-r2">Rate #2 (1 ${esc(base)} =${isAutoRates ? ' &mdash; auto-synced' : ''})</label>
            <input id="wcj-mc-r2" type="number" step="0.0001" min="0.0001" data-field="rate_2" value="${esc(draft.rate_2)}" ${isAutoRates ? 'readonly' : ''} />
          </div>
        </div>
        ${isPro ? `
          <div class="wcj-v10-row-2">
            <div class="wcj-v10-field">
              <label for="wcj-mc-tot">Active Currency Slots</label>
              <select id="wcj-mc-tot" data-field="total_currencies" ${rawTot > 3 ? 'disabled' : ''}>
                <option value="2" ${Number(draft.total_currencies) === 2 ? 'selected' : ''}>2 Currencies</option>
                <option value="3" ${Number(draft.total_currencies) >= 3 ? 'selected' : ''}>3 Currencies</option>
              </select>
              ${rawTot > 3 ? `<div style="font-size:11px;color:#50575e;margin-top:4px;">${rawTot} currencies saved in Classic View (slots #4+ stay untouched).</div>` : ''}
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-mc-rmode">Exchange Rate Updates</label>
              <select id="wcj-mc-rmode" data-field="rate_update_mode">
                <option value="manual" ${draft.rate_update_mode === 'manual' ? 'selected' : ''}>Manual</option>
                <option value="auto" ${draft.rate_update_mode === 'auto' ? 'selected' : ''}>Automatic (updates daily)</option>
              </select>
            </div>
          </div>
          ${showSlot3 ? `
            <div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-mc-c3">Currency #3</label>
                ${renderCurSelect('wcj-mc-c3', 'currency_3', draft.currency_3)}
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-mc-r3">Rate #3 (1 ${esc(base)} =${isAutoRates ? ' &mdash; auto-synced' : ''})</label>
                <input id="wcj-mc-r3" type="number" step="0.0001" min="0.0001" data-field="rate_3" value="${esc(draft.rate_3)}" ${isAutoRates ? 'readonly' : ''} />
              </div>
            </div>
          ` : ''}
        ` : ''}
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-mc-rnd">Price Rounding</label>
            <select id="wcj-mc-rnd" data-field="rounding">
              <option value="no_round" ${draft.rounding === 'no_round' ? 'selected' : ''}>No rounding</option>
              <option value="round" ${draft.rounding === 'round' ? 'selected' : ''}>Standard round</option>
              <option value="round_up" ${draft.rounding === 'round_up' ? 'selected' : ''}>Round up to whole number (e.g. 45.08 &rarr; 46)</option>
              <option value="round_down" ${draft.rounding === 'round_down' ? 'selected' : ''}>Round down to whole number (e.g. 45.08 &rarr; 45)</option>
            </select>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-mc-prec">Round Decimals (Standard Round)</label>
            <input id="wcj-mc-prec" type="number" min="0" max="4" data-field="rounding_precision" value="${esc(draft.rounding_precision)}" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-mc-rev">Cart &amp; Checkout Currency Behavior</label>
          <select id="wcj-mc-rev" data-field="revert">
            <option value="no" ${draft.revert === 'no' ? 'selected' : ''}>Keep shopper's chosen currency at checkout (Classic &amp; Blocks)</option>
            <option value="cart_only" ${draft.revert === 'cart_only' ? 'selected' : ''}>Revert to ${esc(base)} on Classic Cart page</option>
            <option value="yes" ${draft.revert === 'yes' ? 'selected' : ''}>Revert to ${esc(base)} on Classic Checkout page</option>
            <option value="cart_and_checkout" ${draft.revert === 'cart_and_checkout' ? 'selected' : ''}>Revert to ${esc(base)} on Classic Cart &amp; Checkout</option>
          </select>
        </div>
        <div class="wcj-v10-field">
          <label>Place Switcher on Frontend (Widget or Shortcode)</label>
          <div class="wcj-v10-copy-row">
            <code>[wcj_currency_select_drop_down_list]</code>
            <button type="button" class="wcj-v10-btn" data-copy-text="[wcj_currency_select_drop_down_list]" style="padding:4px 10px;font-size:11px;">Copy</button>
          </div>
        </div>
        ${!isPro
          ? `<div class="wcj-v10-elite-sample-box">
              <strong>Plus &amp; Elite add 3+ currencies, automatic rates &amp; shipping/coupon conversion</strong>
              <p>Two currencies work completely free. Preview a 3rd currency (GBP) before upgrading:</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleThird ? 'Hide 3rd Currency Sample' : 'Preview 3rd Currency (GBP)'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=multicurrency')}" target="_blank" rel="noopener">Upgrade to Booster Plus &#8599;</a>
              </div>
            </div>`
          : ''}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Compatibility</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label for="wcj-mc-tpl">Switcher Option Template</label>
              <input id="wcj-mc-tpl" type="text" data-field="switcher_template" value="${esc(draft.switcher_template)}" />
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="per_product" ${draft.per_product ? 'checked' : ''} />
                <span>Allow custom per-currency prices in product editor</span>
              </label>
            </div>
            ${isPro ? `
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="convert_shipping" ${draft.convert_shipping ? 'checked' : ''} />
                  <span>Convert shipping rates into selected currency</span>
                </label>
              </div>
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="compat_free_ship" ${draft.compat_free_ship ? 'checked' : ''} />
                  <span>Convert WooCommerce Free Shipping minimum threshold</span>
                </label>
              </div>
              <div class="wcj-v10-field" style="margin-bottom:0;">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="compat_coupons" ${draft.compat_coupons ? 'checked' : ''} />
                  <span>Convert fixed-cart coupon amounts &amp; min/max rules</span>
                </label>
              </div>
            ` : ''}
          </div>
        </details>
      `;
    },

    shouldRerenderForm(key) {
      return key === 'total_currencies' || key === 'rate_update_mode';
    },

    handleClick(target, state, actions) {
      const curBtn = target.closest('[data-mc-cur]');
      if (curBtn) {
        state.previewContext.mcCurrency = curBtn.dataset.mcCur;
        actions.applyLocalPreviewImmediate();
        actions.schedulePreviewUpdate();
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.third_currency = !state.eliteSample.third_currency;
        sampleBtn.textContent = state.eliteSample.third_currency ? 'Hide 3rd Currency Sample' : 'Preview 3rd Currency (GBP)';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const isPro = !!(meta && meta.is_pro);
      const baseCur = String((meta && meta.base_currency) || 'USD').toUpperCase();
      const basePrice = Number((meta && meta.sample_price) || 49);
      const baseShip = 10;
      const baseFree = 75;
      const cmap = (meta && meta.currency_map) || {};
      const selected = String((previewCtx && previewCtx.mcCurrency) || '').toUpperCase();
      const showSample3 = !isPro && !!(state && state.eliteSample && state.eliteSample.third_currency);
      const active = !!draft.enabled;
      const slots = isPro ? Math.max(2, Math.min(3, Number(draft.total_currencies || 2))) : (showSample3 ? 3 : 2);
      const list = [];
      const seen = {};

      for (let s = 1; s <= slots; s++) {
        const c = s === 3 && !isPro ? 'GBP' : String(draft['currency_' + s] || baseCur).toUpperCase();
        const r = s === 3 && !isPro ? '0.79' : (String(draft['rate_' + s] || '').trim() || '1');
        if (!seen[c]) {
          seen[c] = true;
          const info = cmap[c] || { code: c, name: c, symbol: c };
          list.push({
            code: c,
            name: info.name,
            symbol: info.symbol,
            rate: r,
            label: String(draft.switcher_template || '%currency_name% (%currency_symbol%)')
              .replace(/%currency_name%/g, info.name)
              .replace(/%currency_symbol%/g, info.symbol)
              .replace(/%currency_code%/g, c),
          });
        }
      }

      let curObj = list.find((item) => item.code === selected)
        || list.find((item) => item.code === String(draft.currency_2 || '').toUpperCase())
        || list[0];
      if (!active && !showSample3) {
        curObj = list.find((item) => item.code === baseCur) || { code: baseCur, symbol: (meta && meta.currency_symbol) || '$', rate: '1', label: baseCur };
      }

      const rate = (active || showSample3) ? String(curObj.rate || '1') : '1';
      const precRaw = String(draft.rounding_precision ?? '').trim();
      const prec = precRaw !== '' && Number.isFinite(Number(precRaw)) ? Math.max(0, Math.min(4, parseInt(precRaw, 10) || 0)) : 0;
      const round2 = (n) => Math.round((Number(n || 0) + Number.EPSILON) * 100) / 100;
      const prodP = convertAmt(basePrice, rate, draft.rounding || 'no_round', prec);
      const convSh = (!(active || showSample3) || !draft.convert_shipping) ? baseShip : Number(baseShip) * Number(rate);
      const freeTh = ((active || showSample3) && isPro && draft.compat_free_ship)
        ? convertAmt(baseFree, rate, draft.rounding || 'no_round', prec)
        : baseFree;
      const reverts = (active || showSample3) && ['yes', 'cart_and_checkout'].includes(draft.revert);

      return {
        active,
        selected_code: curObj.code,
        selected_symbol: curObj.symbol,
        rate,
        product_price: round2(prodP),
        shipping_price: round2(convSh),
        free_threshold: round2(freeTh),
        checkout_amount: reverts ? round2(basePrice) : round2(prodP),
        checkout_symbol: reverts ? ((meta && meta.currency_symbol) || '$') : curObj.symbol,
        checkout_pays: reverts ? baseCur : curObj.code,
        reverts_checkout: reverts,
        switcher_items: list,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const dec = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const showSample3 = !!(state.eliteSample && state.eliteSample.third_currency);
      const isOff = !draft.enabled && !showSample3;
      const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';
      const prodName = (meta && meta.product_name) || 'Sample Product';
      const items = Array.isArray(a.switcher_items) ? a.switcher_items : [];

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Storefront Currency &amp; Checkout Preview</h2>
            <div class="wcj-v10-preview-meta">
              <span>${esc(prodName)} &bull; Rate: ${esc(a.rate || '1')}</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        ${items.length ? `
          <div class="wcj-v10-chips" style="margin-bottom:12px;">
            <span>Switcher Preview:</span>
            ${items.map((it) => `<button type="button" class="wcj-v10-chip ${a.selected_code === it.code ? 'is-active' : ''}" data-mc-cur="${esc(it.code)}">${esc(it.label)}</button>`).join('')}
          </div>
        ` : ''}
        <div class="wcj-v10-ba-grid">
          <div class="wcj-v10-ba-box">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">BEFORE (${esc(b.selected_code || 'USD')})</span>
            </div>
            <div class="wcj-v10-ba-content" inert>
              <div class="wcj-v10-sheet">
                <div class="wcj-v10-line"><span>${esc(prodName)}</span><strong>${esc(fmtCur(b.product_price, b.selected_symbol, dec))}</strong></div>
                <div class="wcj-v10-line"><span>Flat Rate Shipping</span><span>${esc(fmtCur(b.shipping_price, b.selected_symbol, dec))}</span></div>
                <div class="wcj-v10-line"><span>Free Shipping Threshold</span><span>${esc(fmtCur(b.free_threshold, b.selected_symbol, dec))}</span></div>
                <div class="wcj-v10-delta-slot"><span>At checkout, customer pays ${esc(fmtCur(b.checkout_amount !== undefined ? b.checkout_amount : b.product_price, b.checkout_symbol || b.selected_symbol, dec))} ${esc(b.checkout_pays || 'USD')}.</span></div>
              </div>
            </div>
          </div>
          <div class="wcj-v10-ba-box is-after ${isOff ? 'is-dimmed' : ''}">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">AFTER (${esc(a.selected_code || 'EUR')})</span>
              <span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${showSample3 ? '3rd Currency Sample' : (isOff ? 'Off in draft' : 'Converted live')}</span>
            </div>
            <div class="wcj-v10-ba-content">
              <div class="wcj-v10-sheet">
                <div class="wcj-v10-line is-changed"><span>${esc(prodName)}</span><strong>${esc(fmtCur(a.product_price, a.selected_symbol, dec))}</strong></div>
                <div class="wcj-v10-line"><span>Flat Rate Shipping</span><span>${esc(fmtCur(a.shipping_price, a.selected_symbol, dec))}</span></div>
                <div class="wcj-v10-line"><span>Free Shipping Threshold</span><span>${esc(fmtCur(a.free_threshold, a.selected_symbol, dec))}</span></div>
                <div class="wcj-v10-delta-slot is-changed">
                  <span>At checkout, customer pays ${esc(fmtCur(a.checkout_amount !== undefined ? a.checkout_amount : a.product_price, a.checkout_symbol || a.selected_symbol, dec))} ${esc(a.checkout_pays || a.selected_code)}${a.reverts_checkout ? ' (reverted on Classic checkout)' : ''}.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
