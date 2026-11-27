(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function renderSheet(doc, label, isAfter, draft, meta, esc) {
    const color = doc.header_text_color || '#cccccc';
    const sym = (meta && meta.currency_symbol) || '$';
    const isOff = isAfter && !draft.enabled;

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isOff ? 'is-dimmed' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(label)}</span>
          ${isAfter ? `<span class="wcj-v10-badge ${isOff ? '' : 'is-on'}">${isOff ? 'Off in draft' : 'Active in draft'}</span>` : ''}
        </div>
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
            <div style="color:#50575e;margin-bottom:6px;font-size:11px;">
              <div>${esc(doc.email_status || '')}</div>
              <div>${esc([
                doc.customer_my_account ? 'My Account download' : '',
                doc.thankyou_page ? 'Thank You link' : '',
                doc.skip_zero_total ? 'Skip $0 orders' : '',
              ].filter(Boolean).join(' \u2022 ') || 'Admin-only download')}</div>
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
            <div class="wcj-v10-sheet-footer">${doc.footer_text ? esc(doc.footer_text) : '<span style="color:#8c8f94;font-style:italic;">No document footer</span>'}</div>
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.pdf_invoicing = {
    title: 'PDF Invoicing & Packing Slips',

    urlParams(draft) {
      return draft && draft.doc_type && draft.doc_type !== 'invoice'
        ? '&doc_type=' + encodeURIComponent(draft.doc_type)
        : '';
    },

    buildContextPayload(draft) {
      return draft && draft.doc_type ? { doc_type: draft.doc_type } : {};
    },

    initContext(cfg) {
      return cfg.initialDocType ? { doc_type: cfg.initialDocType } : {};
    },

    nonDiffKeys() {
      return ['doc_type'];
    },

    isLiveOn(live) {
      return !!(live && live.enabled);
    },

    isAutoEnabled(autoEnabled) {
      return !!(autoEnabled && autoEnabled.enabled);
    },

    getToggleTarget(key) {
      if (['header_image', 'header_title', 'header_text', 'header_text_color', 'numbering_prefix', 'numbering_suffix', 'footer_text', 'create_on_completed', 'create_on_processing', 'attach_completed', 'customer_my_account', 'thankyou_page', 'skip_zero_total'].includes(key)) {
        return ['enabled'];
      }
      return [];
    },

    getPresets(meta) {
      const defaults = (meta && meta.store_defaults) || {};
      const storeAddr = defaults.address || 'Acme Commerce Co.\n100 Market St\nSan Francisco, CA';
      const locked = !!(meta && meta.doc_type_auto_locked);
      const list = [
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
      ];
      if (!locked) {
        list.push({
          id: 'inv_auto',
          label: 'Auto-Email on Completed',
          patch: {
            enabled: true,
            header_enabled: true,
            create_on_completed: true,
            attach_completed: true,
          },
        });
      }
      return list;
    },

    getValidationError() {
      return '';
    },

    getExtraDiagnostics(draft) {
      if (draft && draft.attach_completed && !draft.create_on_completed && !draft.create_on_processing) {
        return [
          {
            level: 'warning',
            text: 'Attach PDF to Completed Order emails is checked, but Auto-create PDF on Completed/Processing orders is off.',
          },
        ];
      }
      return [];
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
            <a href="${esc(cs.pdf_invoicing)}">General &amp; Document Triggers</a>
            <a href="${esc(cs.pdf_invoicing_numbering)}">Numbering &amp; Sequences</a>
            <a href="${esc(cs.pdf_invoicing_header)}">Header Options</a>
            <a href="${esc(cs.pdf_invoicing_footer)}">Footer Options</a>
            <a href="${esc(cs.pdf_invoicing_templates)}">HTML Body Templates</a>
            <a href="${esc(cs.pdf_invoicing_styling)}">Fonts &amp; Styling</a>
            <a href="${esc(cs.pdf_invoicing_emails)}">Email Attachments</a>
            <a href="${esc(cs.pdf_invoicing_display)}">Orders Column &amp; Customer Links</a>
          </div>
        </details>
      `;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const swatches = ['#cccccc', '#0073aa', '#00507c', '#2f5d50', '#1a1a2e', '#ff5722'];
      const docTypes = (meta && meta.doc_types) || [{ id: 'invoice', title: 'Invoice' }];
      const autoLocked = !!(meta && meta.doc_type_auto_locked);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active (all document types)</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('pdf_invoicing')}
        <div class="wcj-v10-field">
          <label for="wcj-i-doctype">Document Type</label>
          <select id="wcj-i-doctype" data-field="doc_type">
            ${docTypes.map((dt) => `<option value="${esc(dt.id)}" ${draft.doc_type === dt.id ? 'selected' : ''}>${esc(dt.title)}</option>`).join('')}
          </select>
        </div>
        <div class="wcj-v10-field">
          <label>Brand Color</label>
          <div class="wcj-v10-swatches">
            ${swatches.map((hex) => `<button type="button" class="wcj-v10-swatch ${draft.header_text_color === hex ? 'is-selected' : ''}" data-inv-swatch="${hex}" style="background:${hex}" title="${hex}"></button>`).join('')}
            <input type="color" data-field="header_text_color" value="${esc(draft.header_text_color || '#cccccc')}" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-i-img">Logo URL</label>
          <div style="display:flex;gap:8px;">
            <input id="wcj-i-img" type="text" data-field="header_image" value="${esc(draft.header_image)}" placeholder="https://..." />
            ${window.wp && wp.media ? '<button type="button" class="wcj-v10-btn" data-action="pick-logo">Choose</button>' : ''}
          </div>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-i-title">Document Title</label>
            <input id="wcj-i-title" type="text" data-field="header_title" value="${esc(draft.header_title)}" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-i-prefix">Number Prefix</label>
            <input id="wcj-i-prefix" type="text" data-field="numbering_prefix" value="${esc(draft.numbering_prefix)}" placeholder="INV-2026-" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-i-text">Business Name &amp; Address Header</label>
          <textarea id="wcj-i-text" rows="3" data-field="header_text">${esc(draft.header_text)}</textarea>
        </div>
        ${autoLocked
          ? `<div class="wcj-v10-elite-sample-box">
              <strong>Elite adds automated multi-document workflows</strong>
              <p>Booster Free generates standard <strong>Invoices</strong> automatically. Upgrade to Elite when you want auto-created Packing Slips, Proforma Invoices, or Credit Notes emailed with orders.</p>
              <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=pdf_invoicing')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
            </div>`
          : `<div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="create_on_completed" ${draft.create_on_completed ? 'checked' : ''} />
                <span>Auto-create PDF when Order is Completed</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="attach_completed" ${draft.attach_completed ? 'checked' : ''} />
                <span>Attach PDF to Completed Order emails</span>
              </label>
            </div>`}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label for="wcj-i-suffix">Number Suffix</label>
              <input id="wcj-i-suffix" type="text" data-field="numbering_suffix" value="${esc(draft.numbering_suffix)}" placeholder="-US" />
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-i-footer">Document Footer Text</label>
              <textarea id="wcj-i-footer" rows="2" data-field="footer_text" placeholder="Thank you for your business!">${esc(draft.footer_text)}</textarea>
            </div>
            ${!autoLocked ? `
              <div class="wcj-v10-field">
                <label class="wcj-v10-check">
                  <input type="checkbox" data-field="create_on_processing" ${draft.create_on_processing ? 'checked' : ''} />
                  <span>Also auto-create when Order is Processing</span>
                </label>
              </div>
            ` : ''}
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="customer_my_account" ${draft.customer_my_account ? 'checked' : ''} />
                <span>Show customer download button in My Account &rarr; Orders</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="thankyou_page" ${draft.thankyou_page ? 'checked' : ''} />
                <span>Show download link on Order Received (Thank You) page</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="skip_zero_total" ${draft.skip_zero_total ? 'checked' : ''} />
                <span>Skip PDF generation on $0 / free orders</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    onFieldSwitch(key, el, state, actions) {
      if (key === 'doc_type') {
        if (!actions.confirmDiscardIfDirty('Switch document type and discard unsaved draft changes?')) {
          el.value = state.draft.doc_type;
          return true;
        }
        actions.openEditor('pdf_invoicing', { doc_type: el.value });
        return true;
      }
      return false;
    },

    onFieldChange(key, value, state) {
      if (key === 'header_text_color') {
        state.draft.header_line_color = value;
        state.draft.header_enabled = true;
      }
      if (['header_image', 'header_title', 'header_text'].includes(key)) {
        state.draft.header_enabled = true;
      }
    },

    handleClick(target, state, actions) {
      const form = document.getElementById('wcj-v10-editor-form');
      const swatchBtn = target.closest('[data-inv-swatch]');
      if (swatchBtn && form && state.draft) {
        const hex = swatchBtn.dataset.invSwatch;
        state.draft.header_text_color = hex;
        state.draft.header_line_color = hex;
        state.draft.header_enabled = true;
        actions.autoEnableForField('header_text_color', form);
        const colorInput = form.querySelector('input[data-field="header_text_color"]');
        if (colorInput) colorInput.value = hex;
        form.querySelectorAll('[data-inv-swatch]').forEach((s) => s.classList.toggle('is-selected', s === swatchBtn));
        actions.onDraftMutated();
        return;
      }

      const pickLogo = target.closest('[data-action="pick-logo"]');
      if (pickLogo && form && window.wp && wp.media && state.draft) {
        const frame = wp.media({ title: 'Select Document Logo', multiple: false, library: { type: 'image' } });
        frame.on('select', () => {
          const att = frame.state().get('selection').first().toJSON();
          if (att && att.url) {
            state.draft.header_image = att.url;
            state.draft.header_enabled = true;
            actions.autoEnableForField('header_image', form);
            const inp = form.querySelector('input[data-field="header_image"]');
            if (inp) inp.value = att.url;
            actions.onDraftMutated();
          }
        });
        frame.open();
        return;
      }

      const pvTab = target.closest('[data-inv-pv]');
      if (pvTab) {
        state.previewContext.invoicePreviewMode = pvTab.dataset.invPv;
        actions.renderPreviewPaneOnly();
      }
    },

    computeLocalAfter(draft) {
      const triggers = [];
      if (draft.create_on_processing) triggers.push('Processing');
      if (draft.create_on_completed) triggers.push('Completed');
      const triggerStr = triggers.length ? 'Auto-creates on ' + triggers.join(' / ') : 'Manual creation';
      const emailStr = draft.attach_completed ? 'Attaches to Completed Email' : 'No email attachment';

      return {
        enabled: !!draft.enabled,
        doc_type: draft.doc_type || 'invoice',
        header_enabled: draft.header_enabled !== false,
        header_image: draft.header_image || '',
        header_title: String(draft.header_title || '').trim() || 'Invoice',
        header_text: String(draft.header_text || '').trim() || 'Company Name',
        header_text_color: draft.header_text_color || '#cccccc',
        header_line_color: draft.header_line_color || draft.header_text_color || '#cccccc',
        invoice_number: (draft.numbering_prefix || '') + '1042' + (draft.numbering_suffix || ''),
        footer_text: String(draft.footer_text || '').trim(),
        create_on_completed: !!draft.create_on_completed,
        customer_my_account: !!draft.customer_my_account,
        thankyou_page: !!draft.thankyou_page,
        skip_zero_total: !!draft.skip_zero_total,
        email_status: triggerStr + ' • ' + emailStr,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const mode = previewCtx.invoicePreviewMode || 'split';
      const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Document Preview</h2>
            <div class="wcj-v10-preview-meta">
              <span>Live store vs draft &bull; Schematic layout</span>
              ${autoBadge}
            </div>
          </div>
          <div class="wcj-v10-tabs">
            <button type="button" class="wcj-v10-tab ${mode === 'split' ? 'is-active' : ''}" data-inv-pv="split">Side-by-Side</button>
            <button type="button" class="wcj-v10-tab ${mode === 'single' ? 'is-active' : ''}" data-inv-pv="single">After Only</button>
          </div>
        </div>
        <div class="${mode === 'split' ? 'wcj-v10-ba-grid' : ''}">
          ${mode === 'split' ? renderSheet((pv && pv.before) || {}, 'BEFORE (Live Store)', false, draft, meta, esc) : ''}
          ${renderSheet((pv && pv.after) || {}, 'AFTER (Your Draft)', true, draft, meta, esc)}
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
