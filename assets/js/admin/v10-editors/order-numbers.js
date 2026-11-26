(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function crc32Unsigned(str) {
    let c;
    let crc = 0 ^ (-1);
    const s = String(str);
    for (let i = 0; i < s.length; i++) {
      c = s.charCodeAt(i);
      crc = (crc >>> 8) ^ CRC_TABLE[(crc ^ c) & 0xFF];
    }
    return ((crc ^ (-1)) >>> 0).toString(10);
  }

  const CRC_TABLE = (() => {
    const t = [];
    for (let n = 0; n < 256; n++) {
      let c = n;
      for (let k = 0; k < 8; k++) {
        c = ((c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
      }
      t[n] = c;
    }
    return t;
  })();

  function formatPhpDate(fmt, tokens) {
    const f = String(fmt || '');
    if (!f) return '';
    if (tokens && typeof tokens === 'object') {
      let out = '';
      for (let i = 0; i < f.length; i++) {
        if (f[i] === '\\' && i + 1 < f.length) {
          out += f[++i];
        } else if (Object.prototype.hasOwnProperty.call(tokens, f[i])) {
          out += tokens[f[i]];
        } else {
          out += f[i];
        }
      }
      return out;
    }
    const d = new Date();
    const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthsLong = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const pad2 = (n) => String(n).padStart(2, '0');
    const yr = d.getFullYear();
    const mo = d.getMonth();
    const day = d.getDate();
    const map = {
      Y: String(yr),
      y: String(yr).slice(-2),
      m: pad2(mo + 1),
      n: String(mo + 1),
      M: monthsShort[mo],
      F: monthsLong[mo],
      d: pad2(day),
      j: String(day),
      H: pad2(d.getHours()),
      i: pad2(d.getMinutes()),
    };
    let out = '';
    for (let i = 0; i < f.length; i++) {
      if (f[i] === '\\' && i + 1 < f.length) {
        out += f[++i];
      } else if (Object.prototype.hasOwnProperty.call(map, f[i])) {
        out += map[f[i]];
      } else {
        out += f[i];
      }
    }
    return out;
  }

  function renderPrefixShortcodes(rawPrefix, meta) {
    const tokens = meta && meta.date_tokens;
    const defFmt = (meta && meta.date_format) || 'Y-m-d';
    let s = String(rawPrefix || '').replace(/\[wcj_(?:order|current)_date([^\]]*)\]/gi, (_, attrs) => {
      const m = String(attrs || '').match(/(?:date_)?format\s*=\s*["']([^"']+)["']/i);
      return formatPhpDate(m ? m[1] : defFmt, tokens);
    });
    s = s.replace(/\[[^\]]+\]/g, '');
    return s;
  }

  function shortcodeDateFormats(rawPrefix, defFmt) {
    let out = '';
    String(rawPrefix || '').replace(/\[wcj_(?:order|current)_date([^\]]*)\]/gi, (_, attrs) => {
      const m = String(attrs || '').match(/(?:date_)?format\s*=\s*["']([^"']+)["']/i);
      out += m ? m[1] : (defFmt || 'Y-m-d');
      return '';
    });
    return out;
  }

  function dateFormatCoversPeriod(fmt, period) {
    const clean = String(fmt || '').replace(/\\./g, '');
    const hasYear = /[Yyo]/.test(clean);
    const hasMonth = /[mnMF]/.test(clean);
    const hasDay = /[dj]/.test(clean);
    const hasYday = clean.indexOf('z') !== -1;
    if (period === 'yearly') return hasYear;
    if (period === 'monthly') return hasYear && hasMonth;
    if (period === 'daily') return hasYear && ((hasMonth && hasDay) || hasYday);
    return true;
  }

  function formatLocalNum(cfg, num, isPro, sampleElite, meta) {
    const raw = cfg.mode === 'hash_crc32' ? crc32Unsigned(String(num)) : String(num);
    const prefix = renderPrefixShortcodes(cfg.prefix, meta);
    const tokens = meta && meta.date_tokens;
    const useWidth = isPro ? Math.min(16, Math.max(0, Number(cfg.min_width || 0))) : (sampleElite ? 4 : 0);
    const useDateP = isPro ? formatPhpDate(cfg.date_prefix, tokens) : (sampleElite ? `${formatPhpDate('Y', tokens)}-` : '');
    const useDateS = isPro ? formatPhpDate(cfg.date_suffix, tokens) : '';
    const useSuffix = isPro ? String(cfg.suffix || '') : (sampleElite ? '-PRO' : '');
    const padded = useWidth > 0 ? raw.padStart(useWidth, '0') : raw;
    return prefix + useDateP + padded + useSuffix + useDateS;
  }

  window.wcjV10Editors.order_numbers = {
    title: 'Order Numbers',

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

    isLiveOn(live) {
      return !!(live && live.enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && autoEnabled.enabled);
    },

    getToggleTarget(key) {
      if (['mode', 'prefix', 'counter', 'min_width', 'date_prefix', 'suffix', 'date_suffix', 'counter_reset', 'min_order_id', 'order_tracking'].includes(key)) {
        return ['enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const next = Math.max(1, Number((meta && meta.current_counter) || 1));
      const year = new Date().getUTCFullYear();
      const presets = [
        {
          id: 'ord_seq',
          label: `ORD-${next} (Sequential)`,
          patch: {
            enabled: true,
            mode: 'yes',
            prefix: 'ORD-',
            counter: String(next),
          },
        },
        {
          id: 'ord_shop_year',
          label: `SHOP-${year}-${next}`,
          patch: {
            enabled: true,
            mode: 'yes',
            prefix: `SHOP-${year}-`,
            counter: String(next),
          },
        },
      ];
      if (meta && meta.is_pro) {
        presets.push({
          id: 'ord_padded',
          label: `INV-000${next} (Zero-Padded)`,
          patch: {
            enabled: true,
            mode: 'yes',
            prefix: 'INV-',
            min_width: '5',
            counter: String(next),
          },
        });
      }
      return presets;
    },

    getValidationError(draft, meta) {
      if (!draft || !draft.enabled) {
        return '';
      }
      const curCounter = Number((meta && meta.current_counter) || 1);
      if (draft.mode === 'yes' && Number(draft.counter || 1) < curCounter) {
        return `Next Counter Value must be at least ${curCounter} (current live counter) to prevent duplicate order numbers.`;
      }
      if (draft.counter_reset && draft.counter_reset !== 'no') {
        const isPro = !!(meta && meta.is_pro);
        let combined = shortcodeDateFormats(draft.prefix, meta && meta.date_format);
        if (isPro) {
          combined += String(draft.date_prefix || '') + String(draft.date_suffix || '');
        }
        if (!dateFormatCoversPeriod(combined, draft.counter_reset)) {
          return `A ${draft.counter_reset} counter reset needs matching year/month/day date tokens (e.g. Y-m-d for daily, Y-m- for monthly, Y for yearly) in Date Format or [wcj_order_date date_format="Y-m-d"].`;
        }
      }
      return '';
    },

    transformDraftForSubmit(draft, live, state) {
      const out = Object.assign({}, draft);
      const unlocked = !!(state && state.eliteSample && state.eliteSample.unlock_counter);
      if (!unlocked || String(out.counter) === String((live && live.counter) || '')) {
        delete out.counter;
        if (live && live.counter !== undefined) {
          out.counter_base = String(live.counter);
        }
      }
      return out;
    },

    getStoredOnOrdersNote() {
      return 'Custom order numbers stay saved in order meta on already-placed orders, though WooCommerce displays default order IDs while this module is off.';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const sampleActive = !!(deps && deps.eliteSample && deps.eliteSample.order_format);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('order_numbers')}
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-on-mode">Numbering Method</label>
            <select id="wcj-on-mode" data-field="mode">
              <option value="yes" ${draft.mode === 'yes' ? 'selected' : ''}>Sequential Counter</option>
              <option value="no" ${draft.mode === 'no' ? 'selected' : ''}>WooCommerce Order ID + Prefix</option>
              <option value="hash_crc32" ${draft.mode === 'hash_crc32' ? 'selected' : ''}>Pseudo-Random (CRC32 Hash)</option>
            </select>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-on-counter">Next Counter Value</label>
            <div style="display:flex;gap:6px;align-items:center;">
              <input id="wcj-on-counter" type="number" min="1" data-field="counter" value="${esc(draft.counter)}" ${deps && deps.eliteSample && deps.eliteSample.unlock_counter ? '' : 'readonly'} />
              <button type="button" class="wcj-v10-btn" data-action="unlock-counter" style="white-space:nowrap;">${deps && deps.eliteSample && deps.eliteSample.unlock_counter ? 'Lock' : 'Change'}</button>
            </div>
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-on-prefix">Order Number Prefix (retroactive on past orders)</label>
          <input id="wcj-on-prefix" type="text" data-field="prefix" value="${esc(draft.prefix)}" placeholder="e.g. ORD-2026-" />
        </div>
        ${isPro
          ? `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-on-width">Min Number Width (Zero Pad)</label>
                <input id="wcj-on-width" type="number" min="0" max="16" data-field="min_width" value="${esc(draft.min_width)}" placeholder="0" />
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-on-suffix">Order Number Suffix</label>
                <input id="wcj-on-suffix" type="text" data-field="suffix" value="${esc(draft.suffix)}" placeholder="e.g. -US" />
              </div>
            </div>
            <div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-on-dp">Date Prefix Format (UTC)</label>
                <input id="wcj-on-dp" type="text" data-field="date_prefix" value="${esc(draft.date_prefix)}" placeholder="e.g. Y-m-" />
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-on-ds">Date Suffix Format (UTC)</label>
                <input id="wcj-on-ds" type="text" data-field="date_suffix" value="${esc(draft.date_suffix)}" placeholder="" />
              </div>
            </div>`
          : `<div class="wcj-v10-elite-sample-box">
              <strong>Elite adds zero-padded width, date tokens &amp; custom suffixes</strong>
              <p>Sequential numbering and custom prefixes work in Booster Free. Preview Elite padded format before upgrading:</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleActive ? 'Hide Elite format sample' : 'Show &ldquo;2026-0001-PRO&rdquo; sample'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=order_numbers')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-on-reset">Reset Counter Period</label>
                <select id="wcj-on-reset" data-field="counter_reset">
                  <option value="no" ${draft.counter_reset === 'no' ? 'selected' : ''}>Never</option>
                  <option value="daily" ${draft.counter_reset === 'daily' ? 'selected' : ''}>Daily (UTC)</option>
                  <option value="monthly" ${draft.counter_reset === 'monthly' ? 'selected' : ''}>Monthly (UTC)</option>
                  <option value="yearly" ${draft.counter_reset === 'yearly' ? 'selected' : ''}>Yearly (UTC)</option>
                </select>
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-on-minid">Start After Order ID (0 = all new)</label>
                <input id="wcj-on-minid" type="number" min="0" data-field="min_order_id" value="${esc(draft.min_order_id)}" />
              </div>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="order_tracking" ${draft.order_tracking ? 'checked' : ''} />
                <span>Support custom order numbers on WooCommerce Order Tracking page</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    handleClick(target, state, actions) {
      const unlockBtn = target.closest('[data-action="unlock-counter"]');
      if (unlockBtn) {
        state.eliteSample.unlock_counter = !state.eliteSample.unlock_counter;
        const inp = document.getElementById('wcj-on-counter');
        if (inp) {
          inp.readOnly = !state.eliteSample.unlock_counter;
          if (!inp.readOnly) {
            inp.focus();
          }
        }
        unlockBtn.textContent = state.eliteSample.unlock_counter ? 'Lock' : 'Change';
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.order_format = !state.eliteSample.order_format;
        sampleBtn.innerHTML = state.eliteSample.order_format ? 'Hide Elite format sample' : 'Show &ldquo;2026-0001-PRO&rdquo; sample';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const isPro = !!(meta && meta.is_pro);
      const lastId = Number((meta && meta.last_order_id) || 1041);
      const nextRaw = draft.mode === 'yes' ? Math.max(1, Number(draft.counter || 1)) : (lastId + 1);
      const sampleElite = !isPro && !!(state.eliteSample && state.eliteSample.order_format);

      if (!draft.enabled && !sampleElite) {
        return {
          enabled: false,
          last_order_id: lastId,
          next_order: '#' + (lastId + 1),
          following_order: '#' + (lastId + 2),
          reset_label: draft.counter_reset || 'no',
          min_order_id: Number(draft.min_order_id || 0),
          order_tracking: !!draft.order_tracking,
        };
      }
      return {
        enabled: !!draft.enabled,
        last_order_id: lastId,
        next_order: '#' + formatLocalNum(draft, nextRaw, isPro, sampleElite, meta),
        following_order: '#' + formatLocalNum(draft, nextRaw + 1, isPro, sampleElite, meta),
        reset_label: draft.counter_reset || 'no',
        min_order_id: Number(draft.min_order_id || 0),
        order_tracking: !!draft.order_tracking,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const sampleElite = !!(state.eliteSample && state.eliteSample.order_format);
      const isOff = !draft.enabled && !sampleElite;
      const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';
      const changed = b.next_order !== a.next_order;
      const ruleMeta = (row) => {
        const parts = [];
        if (row.reset_label && row.reset_label !== 'no') parts.push(`Reset: ${row.reset_label}`);
        if (Number(row.min_order_id || 0) > 0) parts.push(`After ID #${row.min_order_id}`);
        parts.push(row.order_tracking ? 'Tracking lookup: On' : 'Tracking lookup: Off');
        return parts.join(' \u2022 ');
      };

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Order Sequence</h2>
            <div class="wcj-v10-preview-meta">
              <span>Live store vs draft &bull; Store timezone date tokens</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-ba-grid">
          <div class="wcj-v10-ba-box">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">BEFORE (Live Store)</span>
            </div>
            <div class="wcj-v10-ba-content">
              <div class="wcj-v10-line"><span>Last order (unchanged)</span><span>#${esc(b.last_order_id || 1041)}</span></div>
              <div class="wcj-v10-line"><span>Next order</span><strong>${esc(b.next_order || '#1042')}</strong></div>
              <div class="wcj-v10-line"><span>Order after that</span><span>${esc(b.following_order || '#1043')}</span></div>
              <div style="font-size:11px;color:#50575e;margin-top:6px;">${esc(ruleMeta(b))}</div>
              <div class="wcj-v10-delta-slot">${b.enabled ? 'Custom numbering live.' : 'Using default WooCommerce IDs.'}</div>
            </div>
          </div>
          <div class="wcj-v10-ba-box is-after ${isOff ? 'is-dimmed' : ''}">
            <div class="wcj-v10-ba-tag-row">
              <span class="wcj-v10-ba-tag">AFTER (Your Draft)</span>
              <span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${sampleElite ? 'Elite sample' : (isOff ? 'Off in draft' : 'Active in draft')}</span>
            </div>
            <div class="wcj-v10-ba-content">
              <div class="wcj-v10-line"><span>Last order (unchanged)</span><span>#${esc(a.last_order_id || 1041)}</span></div>
              <div class="wcj-v10-line ${changed ? 'is-changed' : ''}"><span>Next order</span><strong>${esc(a.next_order || '#1042')}</strong></div>
              <div class="wcj-v10-line"><span>Order after that</span><span>${esc(a.following_order || '#1043')}</span></div>
              <div style="font-size:11px;color:#50575e;margin-top:6px;">${esc(ruleMeta(a))}</div>
              <div class="wcj-v10-delta-slot ${changed ? 'is-changed' : ''}">
                ${isOff ? 'Module Off — default WooCommerce IDs.' : (sampleElite ? 'Zero-save Elite preview (upgrade for padding &amp; date tokens).' : 'New orders store custom number permanently.')}
              </div>
            </div>
          </div>
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
