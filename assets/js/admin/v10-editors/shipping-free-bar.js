(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function formatMoneySimple(amt, sym, decimals) {
    const val = Number.isFinite(Number(amt)) ? Number(amt) : 0;
    return (sym || '$') + val.toFixed(Number.isFinite(Number(decimals)) ? Number(decimals) : 2);
  }

  function renderRatesList(rates, sym, decimals, esc) {
    const visible = (Array.isArray(rates) ? rates : []).filter((r) => r.visible);
    if (!visible.length) {
      return '<div style="font-size:12px;color:#646970;">No shipping methods visible for this state.</div>';
    }
    return visible.map((r, idx) => `
      <div class="wcj-v10-line" style="font-size:12px;padding:3px 0;">
        <span>${idx === 0 ? '&#9679; ' : '&#9675; '}${esc(r.title)}</span>
        <strong>${Number(r.cost) === 0 ? 'Free' : esc(formatMoneySimple(r.cost, sym, decimals))}</strong>
      </div>
    `).join('');
  }

  function renderBarBox(bar, isAfter, esc) {
    if (!bar || !bar.visible) {
      return isAfter
        ? '<div style="padding:8px 10px;border:1px dashed #dcdcde;border-radius:8px;font-size:12px;color:#646970;margin-bottom:8px;">Free Shipping banner hidden for this placement.</div>'
        : '';
    }
    const safeHex = (c, fb) => (/^#[0-9a-f]{3,8}$/i.test(String(c || '').trim()) ? String(c).trim() : fb);
    const fillCol = safeHex(bar.prog_color, '#6495ED');
    const bgCol = safeHex(bar.bar_bg, '#eef6fb');
    return `
      <div style="padding:9px 11px;border-radius:8px;background:${esc(bgCol)};border:1px solid #b6ddf2;margin-bottom:8px;">
        <div style="font-size:12px;font-weight:600;color:#1d2327;">${esc(bar.text)}</div>
        ${bar.is_progress ? `
          <div class="wcj-v10-progress-track">
            <div class="wcj-v10-progress-fill" style="width:${Number(bar.pct || 0)}%;background:${esc(fillCol)};"></div>
          </div>
          <div style="font-size:11px;color:#50575e;">${Number(bar.pct || 0)}% toward Free Shipping</div>
        ` : ''}
      </div>
    `;
  }

  window.wcjV10Editors.shipping_free_bar = {
    title: 'Shipping Options & Free Shipping Bar',

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
      return ['ignored_methods', 'bar_pages', 'progress_enable_for'];
    },

    isLiveOn(live) {
      return !!(live && (live.so_enabled || live.ltfs_enabled));
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && (autoEnabled.so_enabled || autoEnabled.ltfs_enabled || autoEnabled.hide_if_free || autoEnabled.cart_enabled));
    },

    getToggleTarget(key) {
      if (['hide_if_free', 'hide_type', 'most_expensive_enabled'].includes(key)) {
        return ['so_enabled'];
      }
      if (['cart_enabled', 'cart_content', 'cart_position', 'reached_content', 'mini_cart_enabled', 'checkout_enabled', 'bar_enabled', 'bar_position', 'bar_message', 'bar_bg_color', 'progress_cart'].includes(key)) {
        return ['ltfs_enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const isPro = !!(meta && meta.is_pro);
      const isElite = !!(meta && meta.is_elite);
      const presets = [
        {
          id: 'clean_free',
          label: 'Hide Paid Rates + Cart Bar',
          patch: {
            so_enabled: true,
            hide_if_free: true,
            hide_type: 'hide_all',
            ltfs_enabled: true,
            cart_enabled: true,
            cart_content: '%left_to_free% left to free shipping',
            reached_content: 'You have Free delivery',
          },
        },
        {
          id: 'most_expensive',
          label: 'Highest Paid Rate Only',
          patch: {
            so_enabled: true,
            most_expensive_enabled: true,
          },
        },
      ];
      if (isPro) {
        presets[0].patch.mini_cart_enabled = true;
        presets[0].patch.checkout_enabled = true;
        presets.push({
          id: 'keep_pickup',
          label: 'Free + Keep Local Pickup',
          patch: {
            so_enabled: true,
            hide_if_free: true,
            hide_type: 'except_local_pickup',
            ltfs_enabled: true,
            cart_enabled: true,
          },
        });
      }
      if (isElite) {
        presets.push({
          id: 'elite_sticky_bar',
          label: 'Top Sticky Bar + Visual Meter',
          patch: {
            so_enabled: true,
            hide_if_free: true,
            ltfs_enabled: true,
            cart_enabled: true,
            progress_cart: true,
            bar_enabled: true,
            bar_position: 'top',
            bar_message: 'Add %left_to_free% more for Free Shipping!',
          },
        });
      }
      return presets;
    },

    renderClassicMenu(ed, deps) {
      const meta = ed.meta || {};
      return `
        <details class="wcj-v10-dropdown">
          <summary class="wcj-v10-nav-link" style="cursor:pointer;">All Classic Options &#9662;</summary>
          <div class="wcj-v10-dropdown-menu">
            <a href="${deps.esc(ed.classic_url)}">Shipping Options Classic &rarr;</a>
            <a href="${deps.esc(meta.ltfs_classic_url || ed.classic_url)}">Left to Free Shipping Classic &rarr;</a>
          </div>
        </details>
      `;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const isElite = !!(meta && meta.is_elite);
      const sym = (meta && meta.currency_symbol) || '$';
      const dec = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const minAmt = Number((meta && meta.min_amount) || 75);
      const samplePro = !!(deps && deps.eliteSample && deps.eliteSample.shipping_pro);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';
      const zonesUrl = (meta && meta.shipping_zones_url) || '#';

      return `
        <div class="wcj-v10-section-head">
          <h2>Shipping Rate Rules</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="so_enabled" ${draft.so_enabled ? 'checked' : ''} />
            <span>Shipping Options Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('shipping_free_bar')}
        <div class="wcj-v10-calm-note" style="margin-bottom:14px;">
          ${meta && meta.has_free_method
            ? `Threshold detected: <strong>${esc(formatMoneySimple(minAmt, sym, dec))}</strong> (Zone: <em>${esc(meta.zone_name || 'Default')}</em>)`
            : `No active Free Shipping minimum found &mdash; simulating <strong>${esc(formatMoneySimple(minAmt, sym, dec))}</strong>.`}
          <a href="${esc(zonesUrl)}" target="_blank" rel="noopener" style="margin-left:6px;">Open Shipping Zones &#8599;</a>
        </div>
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="hide_if_free" ${draft.hide_if_free ? 'checked' : ''} />
            <span>Hide paid shipping rates when Free Shipping unlocks</span>
          </label>
        </div>
        ${isPro
          ? `<div class="wcj-v10-field">
              <label for="wcj-sf-hidetype">When Free Shipping Is Available</label>
              <select id="wcj-sf-hidetype" data-field="hide_type">
                <option value="hide_all" ${draft.hide_type === 'hide_all' ? 'selected' : ''}>Hide all other paid rates</option>
                <option value="except_local_pickup" ${draft.hide_type === 'except_local_pickup' ? 'selected' : ''}>Hide paid rates except Local Pickup</option>
                <option value="flat_rate_only" ${draft.hide_type === 'flat_rate_only' ? 'selected' : ''}>Hide Flat Rate methods only</option>
              </select>
            </div>`
          : ''}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="most_expensive_enabled" ${draft.most_expensive_enabled ? 'checked' : ''} />
            <span>Show only the single most expensive paid rate (keeps Free Shipping)</span>
          </label>
        </div>

        <div class="wcj-v10-section-head" style="margin-top:18px;">
          <h2>Free Shipping Bar &amp; Progress</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="ltfs_enabled" ${draft.ltfs_enabled ? 'checked' : ''} />
            <span>Progress Bar Active</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="cart_enabled" ${draft.cart_enabled ? 'checked' : ''} />
            <span>Show remaining-to-free message on Classic Cart page</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-sf-cartmsg">Progress Message (%left_to_free%, %free_shipping_min_amount%, %cart_total%)</label>
          <input id="wcj-sf-cartmsg" type="text" data-field="cart_content" value="${esc(draft.cart_content)}" />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-sf-reached">Free Shipping Reached Message</label>
          <input id="wcj-sf-reached" type="text" data-field="reached_content" value="${esc(draft.reached_content)}" />
        </div>
        ${isPro
          ? `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="mini_cart_enabled" ${draft.mini_cart_enabled ? 'checked' : ''} />
                  <span>Show in Mini-Cart</span>
                </label>
              </div>
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="checkout_enabled" ${draft.checkout_enabled ? 'checked' : ''} />
                  <span>Show on Checkout</span>
                </label>
              </div>
            </div>`
          : ''}
        ${isElite
          ? `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="progress_cart" ${draft.progress_cart ? 'checked' : ''} />
                  <span>Show Visual Progress Meter on Cart</span>
                </label>
              </div>
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="bar_enabled" ${draft.bar_enabled ? 'checked' : ''} />
                  <span>Enable Sticky Storefront Bar</span>
                </label>
              </div>
            </div>
            <div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-sf-barpos">Sticky Bar Position</label>
                <select id="wcj-sf-barpos" data-field="bar_position">
                  <option value="top" ${draft.bar_position === 'top' ? 'selected' : ''}>Top of page</option>
                  <option value="bottom" ${draft.bar_position === 'bottom' ? 'selected' : ''}>Bottom of page</option>
                </select>
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-sf-barbg">Sticky Bar Color</label>
                <input id="wcj-sf-barbg" type="text" data-field="bar_bg_color" value="${esc(draft.bar_bg_color)}" placeholder="#3d9cd2" />
              </div>
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-sf-barmsg">Sticky Bar Copy</label>
              <input id="wcj-sf-barmsg" type="text" data-field="bar_message" value="${esc(draft.bar_message)}" placeholder="Add %left_to_free% more for Free Shipping!" />
            </div>`
          : `<div class="wcj-v10-elite-sample-box">
              <strong>${isPro ? 'Available in Booster Elite: Sticky Storefront Bar &amp; Visual Progress Meter' : 'Plus &amp; Elite add Mini-Cart/Checkout placements, Local Pickup keep, and Sticky Bar'}</strong>
              <p>${isPro ? 'Your Plus plan includes Cart, Mini-Cart, Checkout messages, and Local Pickup exceptions. Preview Elite visual progress bar &amp; sticky bar below:' : 'Cart progress messages and full paid-rate hiding work in Booster Free. Preview paid bar &amp; meter before upgrading:'}</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${samplePro ? 'Hide Elite Bar Sample' : 'Preview Visual Meter &amp; Sticky Bar'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_' + (isPro ? 'plus' : 'free') + '&utm_medium=editor&utm_campaign=shipping_free_bar')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label for="wcj-sf-cartpos">Classic Cart Hook Position</label>
              <select id="wcj-sf-cartpos" data-field="cart_position">
                <option value="woocommerce_after_cart_totals" ${draft.cart_position === 'woocommerce_after_cart_totals' ? 'selected' : ''}>After cart totals (AJAX-safe default)</option>
                <option value="woocommerce_before_cart_totals" ${draft.cart_position === 'woocommerce_before_cart_totals' ? 'selected' : ''}>Before cart totals</option>
                <option value="woocommerce_cart_totals_before_shipping" ${draft.cart_position === 'woocommerce_cart_totals_before_shipping' ? 'selected' : ''}>Cart totals: Before shipping</option>
                <option value="woocommerce_cart_totals_after_shipping" ${draft.cart_position === 'woocommerce_cart_totals_after_shipping' ? 'selected' : ''}>Cart totals: After shipping</option>
                <option value="woocommerce_proceed_to_checkout" ${draft.cart_position === 'woocommerce_proceed_to_checkout' ? 'selected' : ''}>Above Proceed to Checkout</option>
              </select>
            </div>
          </div>
        </details>
      `;
    },

    handleClick(target, state, actions) {
      const scenBtn = target.closest('[data-ship-scenario]');
      if (scenBtn) {
        state.previewContext.shippingScenario = scenBtn.dataset.shipScenario;
        actions.applyLocalPreviewImmediate();
        actions.schedulePreviewUpdate();
        return;
      }
      const tabBtn = target.closest('[data-ship-tab]');
      if (tabBtn) {
        state.previewContext.shippingTab = tabBtn.dataset.shipTab;
        actions.applyLocalPreviewImmediate();
        actions.schedulePreviewUpdate();
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.shipping_pro = !state.eliteSample.shipping_pro;
        sampleBtn.textContent = state.eliteSample.shipping_pro ? 'Hide Elite Bar Sample' : 'Preview Visual Meter & Sticky Bar';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const isPro = !!(meta && meta.is_pro);
      const isElite = !!(meta && meta.is_elite);
      const minAmt = Math.max(1, Number((meta && meta.min_amount) || 75));
      const decimals = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const sym = (meta && meta.currency_symbol) || '$';
      const scenario = (previewCtx && previewCtx.shippingScenario) || 'below';
      const tab = (previewCtx && previewCtx.shippingTab) || 'cart';
      const methods = (meta && Array.isArray(meta.zone_methods)) ? meta.zone_methods : [];
      const forceSample = !isElite && !!(state && state.eliteSample && state.eliteSample.shipping_pro);

      let rawSub = Number((minAmt * 0.6).toFixed(decimals));
      let disc = 0;
      if (scenario === 'reached') {
        rawSub = Number(minAmt.toFixed(decimals));
      } else if (scenario === 'coupon') {
        rawSub = Number((minAmt * 1.05).toFixed(decimals));
        disc = Number((rawSub * 0.10).toFixed(decimals));
      }
      const cartTotal = Number((rawSub - disc).toFixed(decimals));
      const freeUnlocked = cartTotal >= minAmt;
      const leftToFree = Math.max(0, Number((minAmt - cartTotal).toFixed(decimals)));
      const pct = minAmt > 0 ? Math.min(100, Math.max(0, Math.round((cartTotal / minAmt) * 100))) : 100;

      const rates = methods.map((m) => ({
        key: m.key,
        method_id: m.method_id,
        title: m.title,
        cost: Number(m.cost),
        visible: m.method_id !== 'free_shipping' || freeUnlocked,
      }));
      if (draft.so_enabled) {
        if (draft.most_expensive_enabled) {
          const ignored = Array.isArray(draft.ignored_methods) ? draft.ignored_methods : ['free_shipping'];
          let maxIdx = -1;
          let maxCost = -1;
          rates.forEach((r, idx) => {
            if (!r.visible || ignored.includes(r.method_id)) return;
            if (r.cost > maxCost) {
              maxCost = r.cost;
              maxIdx = idx;
            }
          });
          rates.forEach((r, idx) => {
            if (!r.visible) return;
            if (!ignored.includes(r.method_id) && idx !== maxIdx) {
              r.visible = false;
            }
          });
        }
        if (draft.hide_if_free && freeUnlocked) {
          const htype = isPro ? String(draft.hide_type || 'hide_all') : 'hide_all';
          rates.forEach((r) => {
            if (!r.visible || r.method_id === 'free_shipping') return;
            if (htype === 'except_local_pickup' && String(r.key).includes('local_pickup')) return;
            if (htype === 'flat_rate_only' && !String(r.key).includes('flat_rate')) return;
            r.visible = false;
          });
        }
      }

      const ltfsOn = !!draft.ltfs_enabled || forceSample;
      let activeForTab = false;
      let tpl = String(draft.cart_content || '%left_to_free% left to free shipping');
      let isProgress = false;

      if (tab === 'mini_cart') {
        activeForTab = ltfsOn && ((isPro && !!draft.mini_cart_enabled) || forceSample);
        tpl = isPro && draft.mini_cart_content ? String(draft.mini_cart_content) : '%left_to_free% left to free shipping';
      } else if (tab === 'checkout') {
        activeForTab = ltfsOn && ((isPro && !!draft.checkout_enabled) || forceSample);
        tpl = isPro && draft.checkout_content ? String(draft.checkout_content) : '%left_to_free% left to free shipping';
      } else if (tab === 'sticky_bar') {
        activeForTab = ltfsOn && ((isElite && !!draft.bar_enabled) || forceSample);
        tpl = draft.bar_message ? String(draft.bar_message) : 'Add %left_to_free% more for Free Shipping!';
      } else {
        activeForTab = ltfsOn && (!!draft.cart_enabled || forceSample);
        isProgress = (isElite && !!draft.progress_cart) || (!isElite && forceSample);
      }

      const stripHtml = (s) => String(s || '')
        .replace(/<script\b[\s\S]*?<\/script>/gi, '')
        .replace(/<style\b[\s\S]*?<\/style>/gi, '')
        .replace(/<[^>]*>/g, '')
        .trim();
      let bar = {
        visible: false,
        text: '',
        pct,
        is_progress: false,
        bar_pos: draft.bar_position || 'top',
        bar_bg: draft.bar_bg_color || '#3d9cd2',
        prog_color: draft.progress_color || '#6495ED',
      };
      if (activeForTab) {
        let msg = '';
        if (freeUnlocked) {
          msg = stripHtml(draft.reached_content || '').trim() || 'You have Free delivery';
        } else {
          msg = stripHtml(tpl)
            .replace(/%left_to_free%/g, formatMoneySimple(leftToFree, sym, decimals))
            .replace(/%free_shipping_min_amount%/g, formatMoneySimple(minAmt, sym, decimals))
            .replace(/%cart_total%/g, formatMoneySimple(cartTotal, sym, decimals));
        }
        bar = {
          visible: true,
          text: msg,
          pct,
          is_progress: isProgress,
          bar_pos: draft.bar_position || 'top',
          bar_bg: draft.bar_bg_color || '#3d9cd2',
          prog_color: draft.progress_color || '#6495ED',
        };
      }

      return {
        cart_total: cartTotal,
        min_amount: minAmt,
        free_unlocked: freeUnlocked,
        rates,
        bar,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const sym = (meta && meta.currency_symbol) || '$';
      const dec = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
      const isPro = !!(meta && meta.is_pro);
      const isElite = !!(meta && meta.is_elite);
      const scenario = (previewCtx && previewCtx.shippingScenario) || 'below';
      const tab = (previewCtx && previewCtx.shippingTab) || 'cart';
      const forceSample = !isElite && !!(state.eliteSample && state.eliteSample.shipping_pro);
      const isOff = !draft.so_enabled && !draft.ltfs_enabled && !forceSample;
      const autoBadge = (state.autoEnabled.so_enabled || state.autoEnabled.ltfs_enabled) ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      const scenarioChips = [
        { id: 'below', label: 'Below threshold' },
        { id: 'reached', label: 'Just reached' },
        { id: 'coupon', label: 'With 10% coupon' },
      ];
      const placementTabs = [
        { id: 'cart', label: 'Cart' },
        { id: 'mini_cart', label: isPro ? 'Mini-Cart' : 'Mini-Cart (Plus/Elite)' },
        { id: 'checkout', label: isPro ? 'Checkout' : 'Checkout (Plus/Elite)' },
        { id: 'sticky_bar', label: isElite ? 'Sticky Bar' : 'Sticky Bar (Elite)' },
      ];

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Shipping &amp; Bar Preview</h2>
            <div class="wcj-v10-preview-meta">
              <span>Cart: ${esc(formatMoneySimple(a.cart_total || 0, sym, dec))} / Threshold: ${esc(formatMoneySimple(a.min_amount || 75, sym, dec))}</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-chips" style="margin-bottom:8px;">
          <span>Scenario:</span>
          ${scenarioChips.map((s) => `<button type="button" class="wcj-v10-chip ${scenario === s.id ? 'is-active' : ''}" data-ship-scenario="${s.id}">${esc(s.label)}</button>`).join('')}
        </div>
        <div class="wcj-v10-chips" style="margin-bottom:12px;">
          <span>Placement:</span>
          ${placementTabs.map((t) => `<button type="button" class="wcj-v10-chip ${tab === t.id ? 'is-active' : ''}" data-ship-tab="${t.id}">${esc(t.label)}</button>`).join('')}
        </div>
        <div class="wcj-v10-ba-grid">
          <div class="wcj-v10-ba-box">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">BEFORE (Live Store)</span>
            </div>
            <div class="wcj-v10-ba-content" inert>
              ${renderBarBox(b.bar, false, esc)}
              <div class="wcj-v10-sheet">
                <div style="font-size:11px;font-weight:700;color:#50575e;margin-bottom:6px;text-transform:uppercase;">Available Shipping Rates</div>
                ${renderRatesList(b.rates, sym, dec, esc)}
              </div>
            </div>
          </div>
          <div class="wcj-v10-ba-box is-after ${isOff ? 'is-dimmed' : ''}">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">AFTER (Your Draft)</span>
              <span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${forceSample ? 'Sample preview' : (isOff ? 'Both modules Off' : 'Live preview')}</span>
            </div>
            <div class="wcj-v10-ba-content">
              ${renderBarBox(a.bar, true, esc)}
              <div class="wcj-v10-sheet">
                <div style="font-size:11px;font-weight:700;color:#50575e;margin-bottom:6px;text-transform:uppercase;">Filtered Shipping Rates</div>
                ${renderRatesList(a.rates, sym, dec, esc)}
              </div>
              <div class="wcj-v10-delta-slot ${a.free_unlocked ? 'is-changed' : ''}">
                ${a.free_unlocked ? 'Free Shipping unlocked — rate filtering rules active.' : `${esc(formatMoneySimple(Math.max(0, Number(a.min_amount || 75) - Number(a.cart_total || 0)), sym, dec))} away from Free Shipping.`}
              </div>
            </div>
          </div>
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
