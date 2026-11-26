(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  window.wcjV10Editors.customer_bulk_pricing = {
    title: 'Customer & Bulk Pricing',

    urlParams(draft) {
      return draft && draft.role
        ? '&role=' + encodeURIComponent(draft.role)
        : '';
    },

    buildContextPayload(draft) {
      return draft && draft.role ? { role: draft.role } : {};
    },

    initContext(cfg) {
      return cfg.initialRole ? { role: cfg.initialRole } : {};
    },

    nonDiffKeys() {
      return ['role', 'role_multiplier'];
    },

    isLiveOn(live) {
      return !!(live && (live.role_enabled || live.wholesale_enabled));
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && (autoEnabled.role_enabled || autoEnabled.wholesale_enabled));
    },

    getToggleTarget(key) {
      if (['role_discount_pct', 'role_empty_price', 'role_disable_on_sale'].includes(key)) {
        return ['role_enabled'];
      }
      if (['wholesale_discount_type', 'wholesale_qty_scope', 'wholesale_min_qty', 'wholesale_discount', 'wholesale_min_qty_2', 'wholesale_discount_2', 'wholesale_show_cart_info', 'wholesale_rounding', 'wholesale_no_other_discounts'].includes(key)) {
        return ['wholesale_enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const prod = (meta && meta.sample_product) || {};
      const sym = (meta && meta.currency_symbol) || '$';
      const base = Number(prod.price_num || 76);
      const roleSample = (base * 0.85).toFixed(2);
      return [
        {
          id: 'role_vip_15',
          label: `${sym}${base.toFixed(2)} -> ${sym}${roleSample} (-15% Role)`,
          patch: {
            role_enabled: true,
            role_discount_pct: '15',
          },
        },
        {
          id: 'bulk_5_10',
          label: 'Buy 5+ Save 10% (Show Table)',
          patch: {
            wholesale_enabled: true,
            wholesale_discount_type: 'percent',
            wholesale_min_qty: '5',
            wholesale_discount: '10',
            wholesale_show_cart_info: true,
          },
        },
        {
          id: 'stack_b2b',
          label: 'Role -15% + Buy 10+ -10%',
          patch: {
            role_enabled: true,
            role_discount_pct: '15',
            wholesale_enabled: true,
            wholesale_discount_type: 'percent',
            wholesale_min_qty: '10',
            wholesale_discount: '10',
            wholesale_show_cart_info: true,
          },
        },
      ];
    },

    getValidationError(draft) {
      if (draft && draft.wholesale_enabled) {
        const disc = Number(draft.wholesale_discount || 0);
        const min = Number(draft.wholesale_min_qty || 0);
        if (disc > 0 && min < 1) {
          return 'Set Bulk Tier 1 Min Quantity to at least 1 before publishing — a minimum of 0 never triggers a bulk discount.';
        }
        if (draft.wholesale_discount_type === 'percent' && disc > 100) {
          return 'Bulk percentage discount cannot exceed 100%.';
        }
      }
      return '';
    },

    renderClassicMenu(ed, deps) {
      const esc = deps.esc;
      const cs = ed.meta && ed.meta.classic_sections;
      if (!cs) {
        return `<a class="wcj-v10-nav-link" href="${esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
      }
      return `
        <details class="wcj-v10-dropdown">
          <summary class="wcj-v10-btn" style="padding:4px 10px;font-size:12px;">All Classic Options &#9662;</summary>
          <div class="wcj-v10-dropdown-menu">
            <a href="${esc(cs.price_by_user_role)}">Price by User Role Settings</a>
            <a href="${esc(cs.wholesale_price)}">Wholesale / Bulk Price Settings</a>
          </div>
        </details>
      `;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const roles = (meta && meta.roles) || [];
      const isPro = !!(meta && meta.is_pro);
      const sampleTier2 = !!(deps && deps.eliteSample && deps.eliteSample.tier2_bulk);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';
      const roleName = (meta && meta.role_label) || draft.role;

      return `
        <div class="wcj-v10-section-head">
          <h2>1. Price by User Role</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="role_enabled" ${draft.role_enabled ? 'checked' : ''} />
            <span>Role Pricing Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('customer_bulk_pricing')}
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-pr-role">Target User Role</label>
            <select id="wcj-pr-role" data-field="role">
              ${roles.map((r) => `<option value="${esc(r.id)}" ${draft.role === r.id ? 'selected' : ''}>${esc(r.name)} &bull; ${Number(r.users || 0)} ${Number(r.users || 0) === 1 ? 'user' : 'users'}</option>`).join('')}
            </select>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-pr-pct">Discount % off for ${esc(roleName)}</label>
            <input id="wcj-pr-pct" type="number" step="0.1" min="0" max="100" data-field="role_discount_pct" value="${esc(draft.role_discount_pct)}" />
          </div>
        </div>
        <div class="wcj-v10-calm-note" style="margin-bottom:10px;">
          You&rsquo;re logged in as Administrator &mdash; use the preview toggle or a private browser window to test live Customer prices.
        </div>

        <div class="wcj-v10-section-head" style="margin-top:14px;">
          <h2>2. Bulk Quantity Discounts (Cart)</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="wholesale_enabled" ${draft.wholesale_enabled ? 'checked' : ''} />
            <span>Bulk Pricing Active</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-ws-scope">Count quantity by</label>
          <select id="wcj-ws-scope" data-field="wholesale_qty_scope">
            <option value="no" ${draft.wholesale_qty_scope === 'no' ? 'selected' : ''}>Same product quantity (per line item)</option>
            <option value="total_wholesale" ${draft.wholesale_qty_scope === 'total_wholesale' ? 'selected' : ''}>Combined quantity of wholesale products</option>
            <option value="yes" ${draft.wholesale_qty_scope === 'yes' ? 'selected' : ''}>Total items anywhere in the cart</option>
          </select>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-ws-min">Min Quantity (Tier 1)</label>
            <input id="wcj-ws-min" type="number" min="0" data-field="wholesale_min_qty" value="${esc(draft.wholesale_min_qty)}" placeholder="5" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-ws-disc">Discount Value</label>
            <input id="wcj-ws-disc" type="number" step="0.01" min="0" data-field="wholesale_discount" value="${esc(draft.wholesale_discount)}" placeholder="10" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-ws-type">Bulk Discount Calculation</label>
          <select id="wcj-ws-type" data-field="wholesale_discount_type">
            <option value="percent" ${draft.wholesale_discount_type === 'percent' ? 'selected' : ''}>Percentage (%) off</option>
            <option value="fixed" ${draft.wholesale_discount_type === 'fixed' ? 'selected' : ''}>Fixed amount off per unit</option>
          </select>
        </div>
        ${isPro
          ? `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-ws-min2">Tier 2 Min Qty (optional)</label>
                <input id="wcj-ws-min2" type="number" min="0" data-field="wholesale_min_qty_2" value="${esc(draft.wholesale_min_qty_2)}" />
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-ws-disc2">Tier 2 Discount Value</label>
                <input id="wcj-ws-disc2" type="number" step="0.01" min="0" data-field="wholesale_discount_2" value="${esc(draft.wholesale_discount_2)}" />
              </div>
            </div>`
          : `<div class="wcj-v10-elite-sample-box">
              <strong>Elite adds unlimited bulk tiers &amp; category/tag pricing</strong>
              <p>Booster Free supports global per-role discounts plus 1 bulk quantity tier cleanly.</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleTier2 ? 'Hide Tier 2 preview row' : '+ Preview a 2nd bulk tier (Buy 15+ Save 20%)'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=customer_bulk_pricing')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="wholesale_show_cart_info" ${draft.wholesale_show_cart_info ? 'checked' : ''} />
                <span>Show bulk savings breakdown inside Cart item price</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="role_disable_on_sale" ${draft.role_disable_on_sale ? 'checked' : ''} />
                <span>Do not apply role discount on products already on sale</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="role_empty_price" ${draft.role_empty_price ? 'checked' : ''} />
                <span>Hide price &amp; Add to Cart button for this role</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="wholesale_rounding" ${draft.wholesale_rounding ? 'checked' : ''} />
                <span>Round bulk unit prices to store decimal places</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="wholesale_no_other_discounts" ${draft.wholesale_no_other_discounts ? 'checked' : ''} />
                <span>Skip bulk discount if shopper applies a coupon</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'role') {
        if (!actions.confirmDiscardIfDirty('Switch user role and discard unsaved draft changes?')) {
          el.value = state.draft.role;
          return true;
        }
        actions.openEditor('customer_bulk_pricing', { role: el.value });
        return true;
      }
      return false;
    },

    onFieldChange(key, value, state) {
      if (key === 'role_discount_pct') {
        const pct = Math.max(0, Math.min(100, Number(value) || 0));
        state.draft.role_multiplier = String(Math.round((1 - (pct / 100)) * 1000000) / 1000000);
      }
    },

    handleClick(target, state, actions) {
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.tier2_bulk = !state.eliteSample.tier2_bulk;
        sampleBtn.innerHTML = state.eliteSample.tier2_bulk ? 'Hide Tier 2 preview row' : '+ Preview a 2nd bulk tier (Buy 15+ Save 20%)';
        actions.applyLocalPreviewImmediate();
        return;
      }
      const roleTab = target.closest('[data-pricing-view]');
      if (roleTab) {
        state.previewContext.pricingRoleView = roleTab.dataset.pricingView;
        actions.renderPreviewPaneOnly();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const prod = (meta && meta.sample_product) || {};
      const baseNum = Number(prod.price_num || 76);
      const isSale = !!prod.on_sale;
      const decimals = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const factor = Math.pow(10, decimals);
      const r = (n) => Math.round(n * factor) / factor;

      const roleOn = !!draft.role_enabled && !(isSale && draft.role_disable_on_sale);
      const roleEmpty = roleOn && !!draft.role_empty_price;
      const roleMult = draft.role_discount_pct !== undefined
        ? Math.max(0, 1 - (Number(draft.role_discount_pct || 0) / 100))
        : Number(draft.role_multiplier || 1);
      const afterRole = roleOn ? (baseNum * roleMult) : baseNum;

      const min1 = Number(draft.wholesale_min_qty || 0);
      const disc1 = Number(draft.wholesale_discount || 0);
      const isPro = !!(meta && meta.is_pro);
      const levelsNum = Number((meta && meta.levels_number) || 1);
      const sampleTier2 = !isPro && !!(state.eliteSample && state.eliteSample.tier2_bulk);
      const allowT2 = isPro && (levelsNum >= 2 || Number(draft.wholesale_min_qty_2 || 0) > 0);
      const min2 = allowT2 ? Number(draft.wholesale_min_qty_2 || 0) : (sampleTier2 ? 15 : 0);
      const disc2 = allowT2 ? Number(draft.wholesale_discount_2 || 0) : (sampleTier2 ? 20 : 0);

      const q2 = Math.max(5, min1 || 5);
      const q3 = min2 > q2 ? min2 : (q2 * 2);
      const qtys = Array.from(new Set([1, q2, q3]));

      const calcUnit = (qty) => {
        if (roleEmpty) return null;
        let unit = afterRole;
        if (draft.wholesale_enabled || sampleTier2) {
          let chosen = 0;
          let bestMin = 0;
          if (draft.wholesale_enabled && min1 >= 1 && qty >= min1 && min1 >= bestMin) {
            bestMin = min1;
            chosen = disc1;
          }
          if (min2 >= 1 && qty >= min2 && min2 >= bestMin) {
            chosen = disc2;
          }
          if (chosen > 0) {
            unit = draft.wholesale_discount_type === 'fixed'
              ? Math.max(0, afterRole - chosen)
              : Math.max(0, afterRole * (1 - (chosen / 100)));
            if (draft.wholesale_rounding) {
              unit = r(unit);
            }
          }
        }
        return r(unit);
      };

      return {
        base_price: r(baseNum),
        after_role: roleEmpty ? null : r(afterRole),
        role_empty: roleEmpty,
        role_enabled: !!draft.role_enabled,
        wholesale_enabled: !!draft.wholesale_enabled || sampleTier2,
        stacks_both: roleOn && roleMult !== 1 && !!draft.wholesale_enabled && disc1 > 0,
        tiers: qtys.map((q) => {
          const u = calcUnit(q);
          return {
            qty: q,
            unit_price: u,
            line_total: u === null ? null : r(u * q),
          };
        }),
      };
    },

    getEmergencyOffConfirm(live) {
      const parts = [];
      if (live && live.role_enabled) parts.push('Price by User Role');
      if (live && live.wholesale_enabled) parts.push('Wholesale / Bulk Pricing');
      const target = parts.length ? parts.join(' and ') : 'active pricing modules';
      return `Pause ${target} on your live store immediately? A rollback snapshot will be saved so you can Undo anytime.`;
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const fmtMoney = deps.fmtMoney;
      const sym = (meta && meta.currency_symbol) || '$';
      const prod = (meta && meta.sample_product) || {};
      const a = (pv && pv.after) || {};
      const b = (pv && pv.before) || {};
      const viewAs = previewCtx.pricingRoleView || 'role';
      const isGuestView = viewAs === 'guest';
      const roleName = (meta && meta.role_label) || draft.role || 'Customer';
      const tiers = isGuestView ? (b.tiers || []) : (a.tiers || []);
      const baseNum = Number((isGuestView ? b.base_price : a.base_price) || prod.price_num || 76);
      const afterRoleVal = isGuestView ? baseNum : a.after_role;
      const autoBadge = (state.autoEnabled.role_enabled || state.autoEnabled.wholesale_enabled)
        ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>'
        : '';
      const stacksNote = !isGuestView && a.stacks_both
        ? `<div class="wcj-v10-delta-slot is-changed">${esc(roleName)} customers buying ${esc(draft.wholesale_min_qty || 1)}+ get BOTH role and bulk discounts stacked.</div>`
        : `<div class="wcj-v10-delta-slot">${isGuestView ? 'Viewing baseline Before (live store).' : 'Catalog price applies per role; bulk tiers apply in Cart.'}</div>`;

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Store &amp; Cart Price Ledger</h2>
            <div class="wcj-v10-preview-meta">
              <span>${esc(prod.name || 'Sample Product')} &bull; Base ${esc(fmtMoney(baseNum, sym))}</span>
              ${autoBadge}
            </div>
          </div>
          <div class="wcj-v10-tabs">
            <button type="button" class="wcj-v10-tab ${viewAs === 'role' ? 'is-active' : ''}" data-pricing-view="role">As ${esc(roleName)} (After)</button>
            <button type="button" class="wcj-v10-tab ${viewAs === 'guest' ? 'is-active' : ''}" data-pricing-view="guest">Before (Live)</button>
          </div>
        </div>
        <div class="wcj-v10-ba-box is-after" style="margin-bottom:12px;">
          <div class="wcj-v10-ba-tag-row">
            <span class="wcj-v10-ba-tag">${isGuestView ? 'BEFORE (Live Store)' : `AFTER (${esc(roleName)} Draft)`}</span>
            <span class="wcj-v10-badge ${a.role_enabled || a.wholesale_enabled ? 'is-on' : ''}">${isGuestView ? 'Live' : 'Draft'}</span>
          </div>
          <div class="wcj-v10-ledger-row">
            <span>Product Catalog Price:</span>
            <strong>${afterRoleVal === null ? 'Hidden (Empty Price)' : esc(fmtMoney(afterRoleVal, sym))}</strong>
          </div>
          <table class="wcj-v10-sheet-table" style="margin-top:8px;">
            <thead>
              <tr style="background:#0073aa;">
                <th>Cart Qty</th>
                <th>Cart Unit Price</th>
                <th style="text-align:right;">Line Total</th>
              </tr>
            </thead>
            <tbody>
              ${tiers.map((t) => `
                <tr>
                  <td><strong>Qty ${esc(t.qty)}</strong></td>
                  <td>${t.unit_price === null ? 'Hidden' : esc(fmtMoney(t.unit_price, sym))}</td>
                  <td style="text-align:right;"><strong>${t.line_total === null ? '&mdash;' : esc(fmtMoney(t.line_total, sym))}</strong></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
          ${stacksNote}
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
