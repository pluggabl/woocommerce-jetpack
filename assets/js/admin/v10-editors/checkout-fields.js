(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function renderCheckoutMockup(box, heading, isAfter, draft, meta, esc) {
    const isDimmed = isAfter && (!draft.enabled || !draft.field_enabled);
    const secNames = {
      billing: 'Billing details',
      shipping: 'Shipping details',
      order: 'Additional information',
      account: 'Account details',
    };
    const isBlocks = !!(meta && meta.is_blocks_checkout);
    const cfgSec = box.configured_section || box.section || 'billing';
    const secLabel = secNames[cfgSec] || 'Billing details';
    const secTitle = isBlocks
      ? (cfgSec !== 'order' ? `Additional information (Checkout Block \u2022 rule: ${secLabel})` : 'Additional information (Checkout Block)')
      : secLabel;
    const choices = Array.isArray(box.choices) ? box.choices : [];
    const unsupportedOnBlocks = isBlocks && !['text', 'select', 'checkbox', 'radio'].includes(box.type);
    const typeLabels = {
      text: 'Text',
      tel: 'Phone (Tel)',
      email: 'Email',
      number: 'Number',
      datepicker: 'Date Picker',
      textarea: 'Textarea',
      checkbox: 'Checkbox',
      select: 'Dropdown',
      radio: 'Radio',
    };
    const typeBadge = typeLabels[box.type] || `Classic: ${box.type || 'text'}`;

    let inputHtml = `<input type="text" readonly value="" placeholder="${esc(box.placeholder || '')}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    if (box.type === 'textarea') {
      inputHtml = `<textarea rows="2" readonly placeholder="${esc(box.placeholder || '')}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;"></textarea>`;
    } else if (box.type === 'tel') {
      inputHtml = `<input type="tel" readonly value="" placeholder="${esc(box.placeholder || '(555) 123-4567')}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    } else if (box.type === 'email') {
      inputHtml = `<input type="email" readonly value="" placeholder="${esc(box.placeholder || 'name@example.com')}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    } else if (box.type === 'number') {
      inputHtml = `<input type="number" readonly value="" placeholder="${esc(box.placeholder || '0')}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    } else if (box.type === 'datepicker') {
      inputHtml = `<input type="date" readonly value="" placeholder="YYYY-MM-DD" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    } else if (box.type === 'checkbox') {
      inputHtml = `<label style="display:flex;align-items:center;gap:6px;font-size:12px;"><input type="checkbox" disabled /> <span>${esc(box.label || 'Custom Field')}${box.required ? ' <span style="color:#d63638;">*</span>' : ''}</span></label>`;
    } else if (box.type === 'select') {
      inputHtml = `<select disabled style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;">${choices.length ? choices.map((c) => `<option>${esc(c)}</option>`).join('') : '<option>(Add choices below)</option>'}</select>`;
    } else if (box.type === 'radio') {
      const radioOpts = choices.length ? choices : ['(Add choices below)'];
      inputHtml = `<div style="display:flex;flex-direction:column;gap:4px;">${radioOpts.map((c) => `<label style="display:flex;align-items:center;gap:6px;font-size:12px;"><input type="radio" disabled /> <span>${esc(c)}</span></label>`).join('')}</div>`;
    } else if (box.type && box.type !== 'text') {
      inputHtml = `<input type="text" readonly value="" placeholder="${esc(box.placeholder || `[${typeBadge}]`)}" style="width:100%;padding:7px 9px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;" />`;
    }

    const visItems = [
      'Order Admin \u2713',
      `Admin Email ${box.email_to_admin !== false ? '\u2713' : 'Off'}`,
      `Customer Email ${box.email_to_customer !== false ? '\u2713' : 'Off'}`,
      `Thank You ${box.show_thankyou !== false ? '\u2713' : 'Off'}`,
    ];

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isDimmed ? 'is-dimmed' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(heading)}</span>
          ${isAfter ? `<span class="wcj-v10-badge ${isDimmed ? '' : 'is-on'}">${isDimmed ? 'Hidden in draft' : 'Shown at checkout'}</span>` : ''}
        </div>
        <div class="wcj-v10-ba-content" ${isAfter ? '' : 'inert'}>
          <div class="wcj-v10-sheet">
            <div style="font-size:12px;font-weight:700;color:#1d2327;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e4e7;">
              ${esc(secTitle)}
            </div>
            <div style="font-size:12px;color:#50575e;margin-bottom:8px;">Standard WooCommerce checkout fields&hellip;</div>
            ${box.active
              ? `<div style="padding:8px 10px;border-radius:8px;border:1px dashed ${unsupportedOnBlocks ? '#d63638' : '#0073aa'};background:#f6f9fc;">
                  ${box.type !== 'checkbox'
                    ? `<div style="display:flex;align-items:center;justify-content:space-between;gap:6px;font-size:12px;font-weight:600;margin-bottom:4px;">
                        <span>${esc(box.label || 'Custom Field')}${box.required ? ' <span style="color:#d63638;">*</span>' : ''}</span>
                        <span class="wcj-v10-badge">${esc(typeBadge)}</span>
                      </div>`
                    : `<div style="display:flex;justify-content:flex-end;margin-bottom:4px;"><span class="wcj-v10-badge">${esc(typeBadge)}</span></div>`}
                  ${unsupportedOnBlocks ? '<div style="margin-bottom:4px;"><span class="wcj-v10-badge" style="color:#8a2424;">Classic Checkout Only (hidden on Block Checkout)</span></div>' : ''}
                  ${inputHtml}
                  ${box.description ? `<div style="font-size:11px;color:#50575e;margin-top:4px;">${esc(box.description)}</div>` : ''}
                  <div style="font-size:11px;color:#50575e;margin-top:6px;">${esc(visItems.join(' \u2022 '))}</div>
                </div>`
              : '<div style="padding:10px;border:1px dashed #dcdcde;border-radius:8px;font-size:12px;color:#646970;">No custom field active in this slot.</div>'}
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.checkout_custom_fields = {
    title: 'Checkout Custom Fields',

    urlParams(draft) {
      return draft && Number(draft.field_index) > 1
        ? '&field_index=' + encodeURIComponent(draft.field_index)
        : '';
    },

    buildContextPayload(draft) {
      return draft && draft.field_index ? { field_index: Number(draft.field_index) } : {};
    },

    initContext(cfg) {
      return cfg.initialFieldIndex ? { field_index: Number(cfg.initialFieldIndex) } : {};
    },

    nonDiffKeys() {
      return ['field_index'];
    },

    isLiveOn(live) {
      return !!(live && live.enabled && live.field_enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && (autoEnabled.enabled || autoEnabled.field_enabled));
    },

    getToggleTarget(key) {
      if (['field_label', 'field_type', 'field_section', 'field_required', 'field_placeholder', 'select_options', 'field_description', 'field_priority', 'field_clear'].includes(key)) {
        return ['enabled', 'field_enabled'];
      }
      return [];
    },

    getPresets() {
      return [
        {
          id: 'cf_delivery',
          label: 'Delivery Instructions',
          patch: {
            enabled: true,
            field_enabled: true,
            field_label: 'Delivery Instructions',
            field_type: 'text',
            field_section: 'order',
            field_placeholder: 'Gate code or safe drop-off note',
            field_required: false,
          },
        },
        {
          id: 'cf_hear',
          label: 'How Did You Hear About Us?',
          patch: {
            enabled: true,
            field_enabled: true,
            field_label: 'How did you hear about us?',
            field_type: 'select',
            field_section: 'order',
            select_options: "Google Search\nFriend / Referral\nInstagram / Social\nOther",
            field_required: false,
          },
        },
        {
          id: 'cf_gift',
          label: 'Gift Order Checkbox',
          patch: {
            enabled: true,
            field_enabled: true,
            field_label: 'Include gift receipt (hide prices)',
            field_type: 'checkbox',
            field_section: 'order',
            field_required: false,
          },
        },
      ];
    },

    getValidationError(draft, meta) {
      if (!draft || !draft.enabled || !draft.field_enabled) {
        return '';
      }
      if (!String(draft.field_label || '').trim()) {
        return 'Enter a Field Label before publishing — shoppers need a visible label at checkout.';
      }
      if (['select', 'radio'].includes(draft.field_type)) {
        const lines = String(draft.select_options || '').split(/\r\n|\r|\n/).map((s) => s.trim()).filter(Boolean);
        if (!lines.length) {
          return 'Add at least one choice (one per line) for Select or Radio fields.';
        }
      }
      if (meta && meta.is_blocks_checkout && !['text', 'select', 'checkbox', 'radio'].includes(draft.field_type)) {
        return 'WooCommerce Block Checkout only supports Text, Select, Checkbox, and Radio fields. Switch field type so shoppers see it.';
      }
      return '';
    },

    getStoredOnOrdersNote() {
      return 'Submitted answers stay saved in WooCommerce order meta, though Booster stops printing them on Order Admin and emails while this module is turned off.';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const isBlocks = !!(meta && meta.is_blocks_checkout);
      const fieldsNav = (meta && meta.fields_nav) || [];
      const showChoices = ['select', 'radio'].includes(draft.field_type);
      const sampleSecond = !!(deps && deps.eliteSample && deps.eliteSample.second_field);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup ${!isPro ? '<span class="wcj-v10-badge">1 field (Free)</span>' : ''}</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('checkout_custom_fields')}
        ${isPro && fieldsNav.length > 1
          ? `<div class="wcj-v10-field">
              <label for="wcj-cf-idx">Editing Field</label>
              <select id="wcj-cf-idx" data-field="field_index">
                ${fieldsNav.map((f) => `<option value="${esc(f.index)}" ${Number(draft.field_index) === Number(f.index) ? 'selected' : ''}>#${esc(f.index)}: ${esc(f.label)}</option>`).join('')}
              </select>
            </div>`
          : ''}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="field_enabled" ${draft.field_enabled ? 'checked' : ''} />
            <span>Enable Field #${esc(draft.field_index || 1)} on Checkout</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-cf-label">Field Label</label>
          <input id="wcj-cf-label" type="text" data-field="field_label" value="${esc(draft.field_label)}" placeholder="e.g. Delivery Instructions" />
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-cf-type">Field Type</label>
            <select id="wcj-cf-type" data-field="field_type">
              ${!['text', 'select', 'checkbox', 'radio', 'textarea', 'datepicker', 'number', 'email', 'tel'].includes(draft.field_type)
                ? `<option value="${esc(draft.field_type)}" selected disabled>Classic-only: ${esc(draft.field_type)} (edit in Classic)</option>`
                : ''}
              <option value="text" ${draft.field_type === 'text' ? 'selected' : ''}>Text</option>
              <option value="select" ${draft.field_type === 'select' ? 'selected' : ''}>Dropdown (Select)</option>
              <option value="checkbox" ${draft.field_type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
              <option value="radio" ${draft.field_type === 'radio' ? 'selected' : ''}>Radio</option>
              <option value="textarea" ${draft.field_type === 'textarea' ? 'selected' : ''}>Textarea${isBlocks ? ' (Classic only)' : ''}</option>
              <option value="datepicker" ${draft.field_type === 'datepicker' ? 'selected' : ''}>Date Picker${isBlocks ? ' (Classic only)' : ''}</option>
              <option value="number" ${draft.field_type === 'number' ? 'selected' : ''}>Number${isBlocks ? ' (Classic only)' : ''}</option>
              <option value="email" ${draft.field_type === 'email' ? 'selected' : ''}>Email${isBlocks ? ' (Classic only)' : ''}</option>
              <option value="tel" ${draft.field_type === 'tel' ? 'selected' : ''}>Phone (Tel)${isBlocks ? ' (Classic only)' : ''}</option>
            </select>
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-cf-sec">Checkout Section</label>
            <select id="wcj-cf-sec" data-field="field_section">
              <option value="order" ${draft.field_section === 'order' ? 'selected' : ''}>Additional Information (Order Notes)</option>
              <option value="billing" ${draft.field_section === 'billing' ? 'selected' : ''}>Billing Details${isBlocks ? ' (Shown in Additional Info on Blocks)' : ''}</option>
              <option value="shipping" ${draft.field_section === 'shipping' ? 'selected' : ''}>Shipping Details${isBlocks ? ' (Shown in Additional Info on Blocks)' : ''}</option>
              <option value="account" ${draft.field_section === 'account' ? 'selected' : ''}>Account Details${isBlocks ? ' (Shown in Additional Info on Blocks)' : ''}</option>
            </select>
          </div>
        </div>
        ${showChoices
          ? `<div class="wcj-v10-field">
              <label for="wcj-cf-opts">Choices (one per line)</label>
              <textarea id="wcj-cf-opts" rows="3" data-field="select_options" placeholder="Option A&#10;Option B">${esc(draft.select_options)}</textarea>
            </div>`
          : `<div class="wcj-v10-field">
              <label for="wcj-cf-ph">Placeholder (Classic Checkout)</label>
              <input id="wcj-cf-ph" type="text" data-field="field_placeholder" value="${esc(draft.field_placeholder)}" placeholder="Optional input hint" />
            </div>`}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="field_required" ${draft.field_required ? 'checked' : ''} />
            <span>Required (shoppers must fill before placing order)</span>
          </label>
        </div>
        <div class="wcj-v10-calm-note wcj-v10-cf-vis-summary">
          Answers appear in: <strong>Order Admin &#10003;</strong> &bull; Admin Email ${draft.email_to_admin ? '&#10003;' : 'Off'} &bull; Customer Email ${draft.email_to_customer ? '&#10003;' : 'Off'} &bull; Thank You Page ${draft.show_thankyou ? '&#10003;' : 'Off'}
        </div>
        ${!isPro
          ? `<div class="wcj-v10-elite-sample-box" style="margin-top:12px;">
              <strong>Need more checkout fields?</strong>
              <p>Booster Free supports 1 complete custom checkout field. Elite unlocks unlimited checkout fields &amp; blocked date rules.</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleSecond ? 'Hide 2nd field preview' : '+ Preview a 2nd checkout field (Elite)'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=checkout_custom_fields')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`
          : ''}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label for="wcj-cf-desc">Helper Description (Classic Checkout)</label>
              <input id="wcj-cf-desc" type="text" data-field="field_description" value="${esc(draft.field_description)}" />
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="email_to_admin" ${draft.email_to_admin ? 'checked' : ''} />
                <span>Include answer in Admin New Order emails</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="email_to_customer" ${draft.email_to_customer ? 'checked' : ''} />
                <span>Include answer in Customer Order emails</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="show_thankyou" ${draft.show_thankyou ? 'checked' : ''} />
                <span>Show answer on Order Received (Thank You) page</span>
              </label>
            </div>
            ${!isBlocks ? `
              <div class="wcj-v10-row-2">
                <div class="wcj-v10-field" style="margin-bottom:0;">
                  <label for="wcj-cf-prio">Classic Position Priority</label>
                  <input id="wcj-cf-prio" type="number" data-field="field_priority" value="${esc(draft.field_priority)}" placeholder="Default" />
                </div>
                <div class="wcj-v10-field" style="margin-bottom:0;display:flex;align-items:flex-end;">
                  <label class="wcj-v10-check">
                    <input type="checkbox" data-field="field_clear" ${draft.field_clear ? 'checked' : ''} />
                    <span>Clear row after field</span>
                  </label>
                </div>
              </div>
            ` : ''}
          </div>
        </details>
      `;
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'field_index') {
        if (!actions.confirmDiscardIfDirty('Switch field and discard unsaved draft changes?')) {
          el.value = String(state.draft.field_index || 1);
          return true;
        }
        actions.openEditor('checkout_custom_fields', { field_index: Number(el.value) });
        return true;
      }
      return false;
    },

    shouldRerenderForm(key) {
      return key === 'field_type';
    },

    onFieldChange(key, value, state) {
      if (key === 'field_type') {
        const form = document.getElementById('wcj-v10-editor-form');
        const optsWrap = form && form.querySelector('#wcj-cf-opts');
        const needOpts = ['select', 'radio'].includes(value);
        if ((needOpts && !optsWrap) || (!needOpts && optsWrap)) {
          const ph = form && form.querySelector('#wcj-cf-ph');
          if (ph && needOpts) {
            state.draft.select_options = state.draft.select_options || "Option 1\nOption 2";
          }
        }
      }
      if (['email_to_admin', 'email_to_customer', 'show_thankyou'].includes(key)) {
        const sumEl = document.querySelector('#wcj-v10-editor-form .wcj-v10-cf-vis-summary');
        if (sumEl && state.draft) {
          sumEl.innerHTML = `Answers appear in: <strong>Order Admin &#10003;</strong> &bull; Admin Email ${state.draft.email_to_admin ? '&#10003;' : 'Off'} &bull; Customer Email ${state.draft.email_to_customer ? '&#10003;' : 'Off'} &bull; Thank You Page ${state.draft.show_thankyou ? '&#10003;' : 'Off'}`;
        }
      }
    },

    handleClick(target, state, actions) {
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.second_field = !state.eliteSample.second_field;
        sampleBtn.innerHTML = state.eliteSample.second_field ? 'Hide 2nd field preview' : '+ Preview a 2nd checkout field (Elite)';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta) {
      const isBlocks = !!(meta && meta.is_blocks_checkout);
      const choices = String(draft.select_options || '').split(/\r\n|\r|\n/).map((s) => s.trim()).filter(Boolean);
      return {
        active: !!(draft.enabled && draft.field_enabled),
        label: String(draft.field_label || '').trim() || 'Custom Checkout Field',
        type: draft.field_type || 'text',
        section: isBlocks ? 'order' : (draft.field_section || 'billing'),
        configured_section: draft.field_section || 'billing',
        required: !!draft.field_required,
        placeholder: draft.field_placeholder || '',
        choices,
        description: draft.field_description || '',
        email_to_admin: !!draft.email_to_admin,
        email_to_customer: !!draft.email_to_customer,
        show_thankyou: !!draft.show_thankyou,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const sampleSecond = !!(state.eliteSample && state.eliteSample.second_field);
      const autoBadge = (state.autoEnabled.enabled || state.autoEnabled.field_enabled) ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Checkout Field</h2>
            <div class="wcj-v10-preview-meta">
              <span>${meta && meta.is_blocks_checkout ? 'Checkout Block detected' : 'Classic Checkout detected'} &bull; Saved onto WooCommerce orders</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-ba-grid">
          ${renderCheckoutMockup(b, 'BEFORE (Live Store)', false, draft, meta, esc)}
          ${renderCheckoutMockup(a, 'AFTER (Your Draft)', true, draft, meta, esc)}
        </div>
        ${sampleSecond
          ? `<div class="wcj-v10-elite-sample-box" style="margin-top:12px;">
              <strong>Sample Elite 2nd Field (Preview Only):</strong> <em>Company PO Number</em> &mdash; Unlimited fields are unlocked in Booster Elite.
            </div>`
          : ''}
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
