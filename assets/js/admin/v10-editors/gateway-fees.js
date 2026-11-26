(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  window.wcjV10Editors.payment_gateways_fees = {
    title: 'Payment Gateways Fees & Discounts',

    urlParams(draft) {
      return draft && draft.gateway
        ? '&gateway=' + encodeURIComponent(draft.gateway)
        : '';
    },

    initContext(cfg) {
      return cfg.initialGateway ? { gateway: cfg.initialGateway } : {};
    },

    isLiveOn(live) {
      return !!(live && live.enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && autoEnabled.enabled);
    },

    getToggleTarget(key) {
      if (['label', 'fee_type', 'amount', 'min_cart', 'max_cart', 'exclude_shipping', 'is_taxable', 'round'].includes(key)) {
        return ['enabled'];
      }
      return [];
    },

    getPresets() {
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
    },

    getValidationError(draft) {
      if (draft && draft.enabled && Number(draft.amount || 0) !== 0 && !String(draft.label || '').trim()) {
        return 'Enter a Checkout Line Label before publishing — WooCommerce requires a label to apply a fee.';
      }
      return '';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const gateways = (meta && meta.gateways) || [];
      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('payment_gateways_fees')}
        <div class="wcj-v10-field">
          <label for="wcj-f-gateway">Payment Method</label>
          <select id="wcj-f-gateway" data-field="gateway">
            ${gateways.map((g) => `<option value="${esc(g.id)}" ${draft.gateway === g.id ? 'selected' : ''}>${esc(g.title)}${g.enabled ? '' : ' (Disabled in Woo)'}</option>`).join('')}
          </select>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-f-type">Fee or Discount Type</label>
            <select id="wcj-f-type" data-field="fee_type">
              <option value="fixed" ${draft.fee_type === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
              <option value="percent" ${draft.fee_type === 'percent' ? 'selected' : ''}>Percentage (%)</option>
            </select>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-f-amount">Value (negative = discount)</label>
            <input id="wcj-f-amount" type="number" step="0.01" data-field="amount" value="${esc(draft.amount)}" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-f-label">Checkout Line Label (required to apply)</label>
          <input id="wcj-f-label" type="text" data-field="label" value="${esc(draft.label)}" placeholder="e.g. Payment Processing Fee" />
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-f-min">Min Cart Total</label>
            <input id="wcj-f-min" type="number" step="0.01" min="0" data-field="min_cart" value="${esc(draft.min_cart)}" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-f-max">Max Cart Total (0 = no max)</label>
            <input id="wcj-f-max" type="number" step="0.01" min="0" data-field="max_cart" value="${esc(draft.max_cart)}" />
          </div>
        </div>
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="exclude_shipping" ${draft.exclude_shipping ? 'checked' : ''} />
                <span>Exclude shipping when calculating % fee &amp; min/max thresholds</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="round" ${draft.round ? 'checked' : ''} />
                <span>Round fee to nearest whole unit</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="is_taxable" ${draft.is_taxable ? 'checked' : ''} />
                <span>Make this fee taxable at checkout</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'gateway') {
        if (!actions.confirmDiscardIfDirty('Switch gateway and discard unsaved draft changes?')) {
          el.value = state.draft.gateway;
          return true;
        }
        actions.openEditor('payment_gateways_fees', { gateway: el.value });
        return true;
      }
      return false;
    },

    computeLocalAfter(draft, meta, previewCtx) {
      const subtotal = Math.max(0, Number(previewCtx.subtotal) || 0);
      const shipping = Math.max(0, Number(previewCtx.shipping) || 0);
      const calcBase = draft.exclude_shipping ? subtotal : (subtotal + shipping);
      const orderBase = subtotal + shipping;
      const decimals = Number.isFinite(Number(meta.decimals)) ? Number(meta.decimals) : 2;
      const factor = Math.pow(10, decimals);
      const roundNum = (n) => Math.round(n * factor) / factor;

      let feeAmount = 0;
      let reason = 'applied';
      const label = String(draft.label || '').trim();
      const needsLabel = !label;

      if (!draft.enabled) {
        reason = 'module_disabled';
      } else {
        const min = Number(draft.min_cart) || 0;
        const max = Number(draft.max_cart) || 0;
        if (calcBase < min || (max > 0 && calcBase > max)) {
          reason = 'outside_cart_range';
        } else {
          const val = Number(draft.amount) || 0;
          feeAmount = draft.fee_type === 'percent'
            ? roundNum(calcBase * (val / 100))
            : roundNum(val);
          if (draft.round) {
            feeAmount = Math.round(feeAmount);
          }
          if (needsLabel && val !== 0) {
            reason = 'needs_label';
          } else if (needsLabel) {
            reason = 'no_label';
          }
        }
      }

      return {
        module_enabled: !!draft.enabled,
        gateway: draft.gateway,
        subtotal: roundNum(subtotal),
        shipping: roundNum(shipping),
        fee_label: label || (reason === 'needs_label' ? 'Gateway Fee (needs label)' : 'Gateway Fee'),
        fee_amount: feeAmount,
        total: roundNum(orderBase + feeAmount),
        exclude_shipping: !!draft.exclude_shipping,
        is_taxable: !!draft.is_taxable,
        round: !!draft.round,
        reason,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const fmtMoney = deps.fmtMoney;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const sym = (meta && meta.currency_symbol) || '$';
      const feeChanged = Number(b.fee_amount) !== Number(a.fee_amount) || b.fee_label !== a.fee_label;
      const delta = Number(a.total || 0) - Number(b.total || 0);
      const isOff = !draft.enabled;
      const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      let slotClass = 'wcj-v10-delta-slot';
      let slotText = 'No change to cart total yet.';
      if (isOff) {
        slotText = 'Module Off — no checkout fee.';
      } else if (a.reason === 'needs_label') {
        slotClass += ' is-warn';
        slotText = 'Add a Checkout Line Label to publish.';
      } else if (a.reason === 'outside_cart_range') {
        slotText = 'Subtotal is outside min/max range.';
      } else if (delta !== 0) {
        slotClass += ' is-changed';
        slotText = `Total changes by ${delta > 0 ? '+' : ''}${fmtMoney(delta, sym)} on this cart.`;
      }

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Cart Simulator</h2>
            <div class="wcj-v10-preview-meta">
              <span>Live store vs draft${a.exclude_shipping ? ' &bull; Shipping excluded' : ''}</span>
              ${autoBadge}
            </div>
          </div>
          <div class="wcj-v10-chips" style="margin-top:0;">
            ${[25, 75, 250].map((v) => `<button type="button" class="wcj-v10-chip ${Number(previewCtx.subtotal) === v ? 'is-active' : ''}" data-sample-sub="${v}">${esc(sym + v)}</button>`).join('')}
          </div>
        </div>
        <div class="wcj-v10-ba-grid">
          <div class="wcj-v10-ba-box">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">BEFORE (Live Store)</span>
            </div>
            <div class="wcj-v10-ba-content">
              <div class="wcj-v10-line"><span>Items Subtotal</span><span>${esc(fmtMoney(b.subtotal, sym))}</span></div>
              <div class="wcj-v10-line"><span>Shipping</span><span>${esc(fmtMoney(b.shipping, sym))}</span></div>
              <div class="wcj-v10-line"><span>${esc(b.fee_label || 'Gateway Fee')}</span><span>${Number(b.fee_amount) !== 0 ? esc(fmtMoney(b.fee_amount, sym)) : 'None'}</span></div>
              <div class="wcj-v10-total"><span>Total</span><span>${esc(fmtMoney(b.total, sym))}</span></div>
              <div class="wcj-v10-delta-slot">${b.module_enabled ? 'Current live checkout fee.' : 'Currently Off on live store.'}</div>
            </div>
          </div>
          <div class="wcj-v10-ba-box is-after ${isOff ? 'is-dimmed' : ''}">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">AFTER (Your Draft)</span>
              <span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${isOff ? 'Off in draft' : 'Active in draft'}</span>
            </div>
            <div class="wcj-v10-ba-content">
              <div class="wcj-v10-line"><span>Items Subtotal</span><span>${esc(fmtMoney(a.subtotal, sym))}</span></div>
              <div class="wcj-v10-line"><span>Shipping</span><span>${esc(fmtMoney(a.shipping, sym))}</span></div>
              <div class="wcj-v10-line ${feeChanged ? 'is-changed' : ''}">
                <span>${esc(a.fee_label || 'Gateway Fee')}</span>
                <span>${Number(a.fee_amount) !== 0 ? esc(fmtMoney(a.fee_amount, sym)) : 'None'}</span>
              </div>
              <div class="wcj-v10-total"><span>Total</span><span>${esc(fmtMoney(a.total, sym))}</span></div>
              <div class="${slotClass}">${esc(slotText)}</div>
            </div>
          </div>
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
