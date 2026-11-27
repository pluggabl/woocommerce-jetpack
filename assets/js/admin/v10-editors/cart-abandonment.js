(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function sanitizeEmailPreviewHtml(rawHtml, esc) {
    const allowedTags = new Set(['p', 'br', 'strong', 'b', 'em', 'i', 'a']);
    const stripped = String(rawHtml || '')
      .replace(/<script\b[\s\S]*?<\/script>/gi, '')
      .replace(/<style\b[\s\S]*?<\/style>/gi, '');
    const tokens = stripped.split(/(<[^>]+>)/g);
    return tokens.map((tok) => {
      if (!tok) return '';
      if (tok[0] !== '<' || tok[tok.length - 1] !== '>') {
        return esc(tok);
      }
      const m = tok.match(/^<\s*(\/)?\s*([a-z0-9]+)([\s\S]*?)>$/i);
      if (!m) {
        return esc(tok);
      }
      const isClose = !!m[1];
      const tag = m[2].toLowerCase();
      if (!allowedTags.has(tag)) {
        return '';
      }
      if (isClose) {
        return tag === 'br' ? '' : `</${tag}>`;
      }
      if (tag === 'br') {
        return '<br />';
      }
      if (tag === 'a') {
        const hrefMatch = m[3].match(/href\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))/i);
        const rawHref = hrefMatch ? (hrefMatch[1] || hrefMatch[2] || hrefMatch[3] || '') : '';
        const cleanHref = /^https?:\/\//i.test(rawHref) || rawHref.indexOf('/') === 0 ? rawHref : '#';
        return `<a href="${esc(cleanHref)}" target="_blank" rel="noopener">`;
      }
      return `<${tag}>`;
    }).join('');
  }

  function renderCaColumn(box, heading, isAfter, draft, isElite, showSampleSeq, esc) {
    const isOff = isAfter && (!draft.enabled || !draft.template_enabled);
    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isOff ? 'is-dimmed' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(heading)}</span>
          ${isAfter ? `<span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${isOff ? 'Paused in draft' : esc(box.delay_text || 'Scheduled')}</span>` : ''}
        </div>
        <div class="wcj-v10-ba-content" ${isAfter ? '' : 'inert'}>
          <div class="wcj-v10-timeline">
            <div class="wcj-v10-timeline-node">
              <span>Cart captured at checkout (email + phone)</span>
              <span class="wcj-v10-badge">T + 0</span>
            </div>
            <div class="wcj-v10-timeline-node ${box.active ? 'is-active' : ''}">
              <span>Email #${esc(draft.template_index || 1)}: ${esc(box.active ? 'Scheduled (same UTC day)' : 'Off')}</span>
              <span class="wcj-v10-badge ${box.active ? 'is-on' : ''}">${esc(box.delay_text || 'Off')}</span>
            </div>
            ${!isElite && isAfter && showSampleSeq ? `
              <div class="wcj-v10-timeline-node is-active">
                <span>Email #2 (Elite sample): Follow-up reminder</span>
                <span class="wcj-v10-badge is-flagship">T + 4 hr</span>
              </div>
              <div class="wcj-v10-timeline-node is-active">
                <span>Email #3 (Elite sample): 10% OFF coupon</span>
                <span class="wcj-v10-badge is-flagship">T + 8 hr</span>
              </div>
            ` : ''}
          </div>
          <div class="wcj-v10-email-card">
            <div class="wcj-v10-email-head">
              <div><strong>From:</strong> ${esc(box.from_line || '')}</div>
              <div><strong>Subject:</strong> ${esc(box.subject || '(No subject)')}</div>
              ${box.has_coupon ? `<div style="margin-top:4px;"><span class="wcj-v10-badge is-on">Auto Coupon: ${esc(box.coupon_badge)}</span></div>` : ''}
            </div>
            <div class="wcj-v10-email-body">${box.body_html ? sanitizeEmailPreviewHtml(box.body_html, esc) : '<em>Empty email body</em>'}</div>
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.cart_abandonment = {
    title: 'Cart Abandonment',

    urlParams(draft) {
      return draft && Number(draft.template_index) > 1
        ? '&template_index=' + encodeURIComponent(draft.template_index)
        : '';
    },

    buildContextPayload(draft) {
      return draft && draft.template_index ? { template_index: Number(draft.template_index) } : {};
    },

    initContext(cfg) {
      return cfg.initialTemplateIndex ? { template_index: Number(cfg.initialTemplateIndex) } : {};
    },

    nonDiffKeys() {
      return ['template_index'];
    },

    isLiveOn(live) {
      return !!(live && live.enabled && live.template_enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && (autoEnabled.enabled || autoEnabled.template_enabled));
    },

    getToggleTarget() {
      return [];
    },

    getExtraDiagnostics(draft) {
      const items = [];
      if (draft && draft.body) {
        const lower = String(draft.body).toLowerCase();
        if (lower.indexOf('unsubscribe') === -1 && lower.indexOf('opt out') === -1 && lower.indexOf('reply') === -1 && lower.indexOf('%admin_email%') === -1) {
          items.push({
            level: 'info',
            text: 'Tip: Mention how shoppers can opt out (for example "Reply to %admin_email% to opt out") to reduce spam complaints and support GDPR expectations.',
          });
        }
      }
      return items;
    },

    getPresets(meta) {
      const isElite = !!(meta && meta.is_elite);
      const presets = [
        {
          id: 'friendly_1h',
          label: 'Gentle Reminder (1 Hour)',
          patch: {
            enabled: true,
            template_enabled: true,
            trigger_time: '1',
            trigger_time_type: 'hour',
            subject: 'Did something hold you back at checkout?',
            body: 'Hi %customer_name%,<p>We saved the items in your cart in case you still want them.</p><p><a href="%checkout_link%">Complete your order</a> or reply to %admin_email% for help.</p>',
          },
        },
        {
          id: 'fast_30m',
          label: 'Quick Check-In (30 Min)',
          patch: {
            enabled: true,
            template_enabled: true,
            trigger_time: '30',
            trigger_time_type: 'minute',
            subject: 'Your cart is still waiting for you',
            body: 'Hi %customer_name%,<p>Need help completing your order? Your cart is reserved below:</p><p>%checkout_link%</p>',
          },
        },
      ];
      if (isElite) {
        presets.push({
          id: 'coupon_2h',
          label: '10% Recovery Offer (2 Hours)',
          patch: {
            enabled: true,
            template_enabled: true,
            trigger_time: '2',
            trigger_time_type: 'hour',
            subject: 'Take 10% off to complete your order',
            discount_type: 'percent',
            discount_amount: '10',
            auto_apply: true,
            body: 'Hi %customer_name%,<p>Use code <strong>%coupon_code%</strong> (applied automatically below) to save 10%:</p><p>%checkout_link%</p>',
          },
        });
      }
      return presets;
    },

    getValidationError(draft, meta) {
      const isElite = !!(meta && meta.is_elite);
      if (isElite && draft.from_email && String(draft.from_email).indexOf('@') === -1) {
        return 'Enter a valid Sender Email Address before publishing.';
      }
      if (!draft || !draft.enabled || !draft.template_enabled) {
        return '';
      }
      if (!String(draft.subject || '').trim() || !String(draft.body || '').replace(/<[^>]*>/g, '').trim()) {
        return 'Reminder email needs both a Subject Line and Email Body before going live.';
      }
      if (Number(draft.trigger_time || 0) < 1) {
        return 'Send delay must be at least 1 minute.';
      }
      if (draft.trigger_time_type === 'day') {
        return '"Day" delays never send because Booster checks today\'s carts (UTC) only. Switch to Minutes or Hours.';
      }
      if (isElite && ['percent', 'fixed_cart'].includes(draft.discount_type)) {
        const amt = Number(draft.discount_amount || 0);
        if (amt <= 0) {
          return 'Enter a Coupon Amount greater than 0 for the selected discount type.';
        }
        if (draft.discount_type === 'percent' && amt > 100) {
          return 'Percentage coupon discount cannot exceed 100%.';
        }
      }
      return '';
    },

    getSendsEmailsNote(draft, meta) {
      const pending = Number((meta && meta.pending_today) || 0);
      return pending > 0
        ? `Sends real recovery emails via WP-Cron (${pending} unrecovered cart(s) captured today could be emailed soon). Emails already sent cannot be recalled.`
        : 'Sends real recovery emails to customers via WP-Cron every 15 minutes. Emails already sent cannot be recalled by Undo or Turn Off Now.';
    },

    getEmergencyOffConfirm() {
      return 'Stops all future abandoned-cart reminder emails immediately. Emails already sent to customers cannot be recalled. Continue?';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isElite = !!(meta && meta.is_elite);
      const isPro = !!(meta && meta.is_pro);
      const nav = (meta && meta.templates_nav) || [];
      const showSeqSample = !!(deps && deps.eliteSample && deps.eliteSample.ca_sequence);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';
      const showCouponFields = isElite && ['percent', 'fixed_cart'].includes(draft.discount_type);

      const mergeTags = ['%customer_name%', '%checkout_link%', '%admin_email%'];
      if (isElite && draft.discount_type !== 'no') {
        mergeTags.push('%coupon_code%');
      }

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup ${!isElite ? '<span class="wcj-v10-badge">1 Email</span>' : ''}</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('cart_abandonment')}
        <div class="wcj-v10-calm-note" style="margin-bottom:12px;">
          Tracks carts on Classic Checkout once a shopper types billing email &amp; phone, and automatically cancels reminders when the order is placed.
        </div>
        ${isElite && nav.length > 1
          ? `<div class="wcj-v10-field">
              <label for="wcj-ca-idx">Editing Email Step</label>
              <select id="wcj-ca-idx" data-field="template_index">
                ${nav.map((t) => `<option value="${esc(t.index)}" ${Number(draft.template_index) === Number(t.index) ? 'selected' : ''}>#${esc(t.index)}: ${esc(t.label)}</option>`).join('')}
              </select>
            </div>`
          : ''}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="template_enabled" ${draft.template_enabled ? 'checked' : ''} />
            <span>Enable Reminder Email #${esc(draft.template_index || 1)}</span>
          </label>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-ca-time">Send After</label>
            <input id="wcj-ca-time" type="number" min="1" data-field="trigger_time" value="${esc(draft.trigger_time)}" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-ca-unit">Time Unit (UTC same-day)</label>
            <select id="wcj-ca-unit" data-field="trigger_time_type">
              <option value="minute" ${draft.trigger_time_type === 'minute' ? 'selected' : ''}>Minutes</option>
              <option value="hour" ${draft.trigger_time_type === 'hour' ? 'selected' : ''}>Hours</option>
              ${draft.trigger_time_type === 'day' ? '<option value="day" selected>Day (broken in cron — switch)</option>' : ''}
            </select>
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-ca-subj">Email Subject Line</label>
          <input id="wcj-ca-subj" type="text" data-field="subject" value="${esc(draft.subject)}" />
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-ca-body">Email Message Body</label>
          <textarea id="wcj-ca-body" rows="5" data-field="body">${esc(draft.body)}</textarea>
          <div class="wcj-v10-chips" style="margin-top:6px;">
            <span>Insert tag:</span>
            ${mergeTags.map((tag) => `<button type="button" class="wcj-v10-chip" data-ca-tag="${esc(tag)}">${esc(tag)}</button>`).join('')}
          </div>
        </div>
        ${isElite
          ? `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-ca-disc">Recovery Coupon</label>
                <select id="wcj-ca-disc" data-field="discount_type">
                  <option value="no" ${draft.discount_type === 'no' ? 'selected' : ''}>No Coupon</option>
                  <option value="percent" ${draft.discount_type === 'percent' ? 'selected' : ''}>Percentage Discount (%)</option>
                  <option value="fixed_cart" ${draft.discount_type === 'fixed_cart' ? 'selected' : ''}>Fixed Cart Discount</option>
                </select>
              </div>
              ${showCouponFields
                ? `<div class="wcj-v10-field">
                    <label for="wcj-ca-amt">Coupon Amount</label>
                    <input id="wcj-ca-amt" type="number" min="1" step="0.01" data-field="discount_amount" value="${esc(draft.discount_amount)}" />
                  </div>`
                : '<div></div>'}
            </div>
            ${showCouponFields ? `
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="auto_apply" ${draft.auto_apply ? 'checked' : ''} />
                  <span>Auto-apply generated coupon when customer clicks recovery link</span>
                </label>
              </div>
            ` : ''}
            <details class="wcj-v10-more-rules">
              <summary>Sender Details &amp; Multi-Step Sequence</summary>
              <div class="wcj-v10-more-rules-body">
                <div class="wcj-v10-row-2">
                  <div class="wcj-v10-field">
                    <label for="wcj-ca-fname">From Name</label>
                    <input id="wcj-ca-fname" type="text" data-field="from_name" value="${esc(draft.from_name)}" />
                  </div>
                  <div class="wcj-v10-field">
                    <label for="wcj-ca-fmail">From Email</label>
                    <input id="wcj-ca-fmail" type="text" data-field="from_email" value="${esc(draft.from_email)}" />
                  </div>
                </div>
                <div class="wcj-v10-field" style="margin-bottom:0;">
                  <label for="wcj-ca-tot">Sequence Steps (save to add tabs)</label>
                  <input id="wcj-ca-tot" type="number" min="1" max="5" data-field="total_templates" value="${esc(draft.total_templates)}" />
                </div>
              </div>
            </details>`
          : `<div class="wcj-v10-elite-sample-box">
              <strong>${isPro ? 'Available in Booster Elite: Multi-Step Sequences, Recovery Coupons &amp; Custom Sender' : 'Elite adds multi-step drip sequences, recovery coupons &amp; custom From sender'}</strong>
              <p>${isPro ? 'Single-email recovery works in Plus. Preview a 3-step Elite sequence with an automated 10% coupon:' : 'Email #1 recovery runs in Booster Free. Preview a 3-step sequence &amp; automated coupon before upgrading:'}</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${showSeqSample ? 'Hide Multi-Step Sequence Sample' : 'Preview 3-Step Coupon Sequence'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_' + (isPro ? 'plus' : 'free') + '&utm_medium=editor&utm_campaign=cart_abandonment')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`}
      `;
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'template_index') {
        if (!actions.confirmDiscardIfDirty('Switch email step and discard unsaved draft changes?')) {
          el.value = String(state.draft.template_index || 1);
          return true;
        }
        actions.openEditor('cart_abandonment', { template_index: Number(el.value) });
        return true;
      }
      return false;
    },

    shouldRerenderForm(key) {
      return key === 'discount_type';
    },

    handleClick(target, state, actions) {
      const rawBtn = target.closest('[data-ca-raw]');
      if (rawBtn) {
        state.previewContext.caShowRaw = rawBtn.dataset.caRaw === '1';
        actions.applyLocalPreviewImmediate();
        actions.schedulePreviewUpdate();
        return;
      }
      const tagBtn = target.closest('[data-ca-tag]');
      if (tagBtn && state.draft) {
        const tag = tagBtn.dataset.caTag || '';
        const area = document.getElementById('wcj-ca-body');
        if (area) {
          const val = String(area.value || '');
          const pos = typeof area.selectionStart === 'number' ? area.selectionStart : val.length;
          area.value = val.slice(0, pos) + tag + val.slice(pos);
          state.draft.body = area.value;
          actions.autoEnableForField('body', document.getElementById('wcj-v10-editor-form'));
          actions.applyLocalPreviewImmediate();
          actions.schedulePreviewUpdate();
        }
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.ca_sequence = !state.eliteSample.ca_sequence;
        sampleBtn.textContent = state.eliteSample.ca_sequence ? 'Hide Multi-Step Sequence Sample' : 'Preview 3-Step Coupon Sequence';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx) {
      const isElite = !!(meta && meta.is_elite);
      const showRaw = !!(previewCtx && previewCtx.caShowRaw);
      const chkUrl = (meta && meta.checkout_url) || '/checkout/';
      const admMail = (meta && meta.admin_email) || 'admin@example.com';
      const delayNum = Math.max(1, parseInt(draft.trigger_time, 10) || 1);
      const unit = String(draft.trigger_time_type || 'hour');
      const unitLbl = unit === 'minute' ? 'min' : (unit === 'hour' ? 'hr' : 'day (never fires)');
      const discAmtNum = Number.isFinite(parseFloat(draft.discount_amount)) ? String(parseFloat(draft.discount_amount)) : String(draft.discount_amount || '').trim();
      const couponStr = (isElite && draft.discount_type !== 'no' && Number(draft.discount_amount || 0) > 0) ? 'CA-SAMPLE10' : '';

      let rendered = String(draft.body || '');
      if (!showRaw) {
        const sep = chkUrl.indexOf('?') === -1 ? '?' : '&';
        const restoreLink = chkUrl + sep + 'wcj_restore_ac_token=SAMPLE_TOKEN';
        rendered = rendered
          .replace(/%customer_name%/g, 'Alex Morgan')
          .replace(/%checkout_link%/g, restoreLink)
          .replace(/%coupon_code%/g, couponStr)
          .replace(/%admin_email%/g, admMail);
      }

      return {
        active: !!(draft.enabled && draft.template_enabled),
        delay_text: `T + ${delayNum} ${unitLbl}`,
        from_line: `${String(draft.from_name || '').trim()} <${String(draft.from_email || '').trim()}>`,
        subject: String(draft.subject || ''),
        body_html: rendered,
        has_coupon: couponStr !== '',
        coupon_badge: draft.discount_type === 'percent'
          ? `${discAmtNum}% OFF (ca-xxxxxxxx)`
          : (draft.discount_type === 'fixed_cart' ? `${discAmtNum} OFF (ca-xxxxxxxx)` : ''),
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const isElite = !!(meta && meta.is_elite);
      const showRaw = !!(previewCtx && previewCtx.caShowRaw);
      const showSampleSeq = !isElite && !!(state.eliteSample && state.eliteSample.ca_sequence);
      const autoBadge = (state.autoEnabled.enabled || state.autoEnabled.template_enabled) ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Recovery Timeline &amp; Email Preview</h2>
            <div class="wcj-v10-preview-meta">
              <span>WP-Cron interval: ~15 min (same UTC day) &bull; Today pending: ${Number((meta && meta.pending_today) || 0)}</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-chips" style="margin-bottom:12px;">
          <span>Email Body Mode:</span>
          <button type="button" class="wcj-v10-chip ${!showRaw ? 'is-active' : ''}" data-ca-raw="0">Filled Sample Tags</button>
          <button type="button" class="wcj-v10-chip ${showRaw ? 'is-active' : ''}" data-ca-raw="1">Raw Merge Tags</button>
        </div>
        <div class="wcj-v10-ba-grid">
          ${renderCaColumn(b, 'BEFORE (Live Store)', false, draft, isElite, false, esc)}
          ${renderCaColumn(a, 'AFTER (Your Draft)', true, draft, isElite, showSampleSeq, esc)}
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
