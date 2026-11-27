(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function moneyStr(num, sym, dec) {
    return (sym || '$') + Number(num || 0).toFixed(Number.isFinite(Number(dec)) ? Number(dec) : 2);
  }

  function renderVatMockup(box, heading, isAfter, draft, meta, esc) {
    const isOff = isAfter && !draft.enabled;
    const sym = (meta && meta.currency_symbol) || '$';
    const dec = Number.isFinite(Number(meta && meta.decimals)) ? Number(meta.decimals) : 2;
    const badgeColor = box.tax_exempt ? '#135e29' : (!box.checkout_ok ? '#8a2424' : '#50575e');
    const badgeText = !box.active
      ? 'Module Off'
      : (box.tax_exempt ? '0% VAT Exempt' : (!box.checkout_ok ? 'Checkout Blocked' : 'Standard VAT Charged'));

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''} ${isOff ? 'is-dimmed' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(heading)}</span>
          ${isAfter ? `<span class="wcj-v10-badge ${box.tax_exempt ? 'is-on' : ''}">${esc(badgeText)}</span>` : ''}
        </div>
        <div class="wcj-v10-ba-content" ${isAfter ? '' : 'inert'}>
          <div class="wcj-v10-sheet">
            <div style="font-size:12px;font-weight:700;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e4e7;">
              Classic Checkout &bull; Billing Details
            </div>
            ${box.show_field
              ? `<div style="margin-bottom:10px;padding:8px 10px;border:1px dashed #0073aa;border-radius:8px;background:#f6f9fc;">
                  <div style="font-size:12px;font-weight:600;margin-bottom:4px;">
                    ${esc(box.label || 'EU VAT Number')}${box.required ? ' <span style="color:#d63638;">*</span>' : ''}
                  </div>
                  <input type="text" readonly value="${esc(box.sample_code || (box.vies_status === 'invalid' ? 'DE000000000' : 'DE123456789'))}" style="width:100%;padding:6px 8px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;font-size:12px;" />
                  ${box.notice ? `<div style="font-size:11px;margin-top:5px;color:${esc(badgeColor)};font-weight:600;">${esc(box.notice)}</div>` : ''}
                </div>`
              : `<div style="font-size:12px;color:#646970;margin-bottom:8px;padding:6px 8px;border:1px dashed #dcdcde;border-radius:6px;">
                  ${esc(box.notice || 'No EU VAT Number field shown on checkout.')}
                </div>`}
            <div class="wcj-v10-line"><span>Subtotal</span><span>${esc(moneyStr(box.subtotal || 100, sym, dec))}</span></div>
            <div class="wcj-v10-line"><span>EU VAT (20%)</span><span>${esc(moneyStr(box.vat_amount || 0, sym, dec))}</span></div>
            <div class="wcj-v10-line is-total"><span>Order Total</span><strong>${esc(moneyStr(box.total || 100, sym, dec))}</strong></div>
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.eu_vat_number = {
    title: 'EU VAT Number',

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

    getToggleTarget() {
      return [];
    },

    getPresets(meta) {
      const isPro = !!(meta && meta.is_pro);
      const b2b = {
        id: 'eu_b2b_std',
        label: 'Standard EU B2B Exemption',
        patch: {
          enabled: true,
          field_label: 'EU VAT Number',
          field_placeholder: 'e.g. DE123456789',
          validate: true,
          disable_for_valid: true,
          first_method: 'soap',
          add_progress_text: true,
          skip_countries: '',
        },
      };
      if (isPro) {
        b2b.patch.preserve_base = true;
        b2b.patch.eu_only = true;
      }
      return [
        b2b,
        {
          id: 'eu_collect_only',
          label: 'Collect VAT ID Only (Keep Tax)',
          patch: {
            enabled: true,
            field_label: 'EU VAT Number',
            validate: false,
            disable_for_valid: false,
            field_required: false,
          },
        },
      ];
    },

    getValidationError(draft) {
      if (!draft || !draft.enabled) {
        return '';
      }
      if (!String(draft.field_label || '').trim()) {
        return 'Enter a Checkout VAT Field Label (for example "EU VAT Number") before publishing.';
      }
      return '';
    },

    getExtraDiagnostics(draft, meta) {
      const items = [];
      if (draft && meta && meta.is_pro && draft.eu_only && !draft.validate && !draft.field_required) {
        items.push({
          level: 'warning',
          text: 'EU-Only field hiding requires Booster\'s frontend script, which only loads when Validate with VIES or Required is enabled.',
        });
      }
      return items;
    },

    getStoredOnOrdersNote() {
      return 'Validated VAT numbers and zero-rated tax amounts are stored permanently onto WooCommerce orders. Booster applies your configured rules — confirm tax policy with your accountant.';
    },

    renderClassicMenu(ed, deps) {
      return `<a class="wcj-v10-nav-link" href="${deps.esc(ed.classic_url)}">All Classic Options &rarr;</a>`;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const isPro = !!(meta && meta.is_pro);
      const baseCountry = (meta && meta.base_country) || 'DE';
      const sampleBase = !!(deps && deps.eliteSample && deps.eliteSample.preserve_base);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup</h2>
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="enabled" ${draft.enabled ? 'checked' : ''} />
            <span>Module Active</span>
          </label>
        </div>
        ${deps.renderPresetsHtml('eu_vat_number')}
        <div class="wcj-v10-calm-note" style="margin-bottom:14px;">
          Store Base Country: <strong>${esc(baseCountry)}</strong> &bull; Invoices:
          <button type="button" class="wcj-v10-btn" data-open-editor="pdf_invoicing" style="padding:3px 9px;font-size:11px;margin-left:4px;">Add VAT / Reverse Charge Note on PDFs &rarr;</button>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-vat-label">Checkout VAT Field Label</label>
            <input id="wcj-vat-label" type="text" data-field="field_label" value="${esc(draft.field_label)}" placeholder="EU VAT Number" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-vat-ph">Field Placeholder</label>
            <input id="wcj-vat-ph" type="text" data-field="field_placeholder" value="${esc(draft.field_placeholder)}" placeholder="e.g. DE123456789" />
          </div>
        </div>
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="validate" ${draft.validate ? 'checked' : ''} />
            <span>Validate EU VAT numbers live with the EU VIES service</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="disable_for_valid" ${draft.disable_for_valid ? 'checked' : ''} />
            <span>Exempt VAT (0% tax) when VAT number is valid</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="field_required" ${draft.field_required ? 'checked' : ''} />
            <span>Make VAT field required for EU billing countries</span>
          </label>
        </div>
        ${isPro
          ? `<div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="preserve_base" ${draft.preserve_base ? 'checked' : ''} />
                <span>Keep charging VAT for buyers in base country (${esc(baseCountry)})</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="eu_only" ${draft.eu_only ? 'checked' : ''} />
                <span>Show VAT field only when billing country is in the EU</span>
              </label>
            </div>`
          : `<div class="wcj-v10-elite-sample-box">
              <strong>Plus &amp; Elite add Base-Country VAT Preservation, EU-Only Visibility &amp; IP Check</strong>
              <p>Preview how domestic (${esc(baseCountry)}) VAT preservation behaves before upgrading:</p>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleBase ? 'Hide Base-Country Sample' : 'Preview Domestic VAT Kept'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=eu_vat_number')}" target="_blank" rel="noopener">Upgrade to Booster Plus &#8599;</a>
              </div>
            </div>`}
        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-vat-method">VIES Lookup Method</label>
                <select id="wcj-vat-method" data-field="first_method">
                  <option value="soap" ${draft.first_method === 'soap' ? 'selected' : ''}>SOAP (Recommended)</option>
                  <option value="curl" ${draft.first_method === 'curl' ? 'selected' : ''}>cURL REST API</option>
                  <option value="file_get_contents" ${draft.first_method === 'file_get_contents' ? 'selected' : ''}>Simple URL (file_get_contents)</option>
                </select>
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-vat-pos">Order &amp; Email Display Position</label>
                <select id="wcj-vat-pos" data-field="display_position">
                  <option value="after_order_table" ${draft.display_position === 'after_order_table' ? 'selected' : ''}>After order table</option>
                  <option value="in_billing_address" ${draft.display_position === 'in_billing_address' ? 'selected' : ''}>Inside formatted billing address</option>
                </select>
              </div>
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-vat-err">Invalid VAT Checkout Notice</label>
              <input id="wcj-vat-err" type="text" data-field="not_valid_message" value="${esc(draft.not_valid_message)}" />
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="add_progress_text" ${draft.add_progress_text ? 'checked' : ''} />
                <span>Show live VIES validation status text below input</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="disable_on_cart" ${draft.disable_on_cart ? 'checked' : ''} />
                <span>Also zero-rate VAT on Cart page totals once validated</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="apply_country_code" ${draft.apply_country_code ? 'checked' : ''} />
                <span>Auto-prefix billing country ISO code if shopper omits it</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    handleClick(target, state, actions) {
      const scenBtn = target.closest('[data-vat-scenario]');
      if (scenBtn) {
        state.previewContext.vatScenario = scenBtn.dataset.vatScenario;
        actions.applyLocalPreviewImmediate();
        actions.schedulePreviewUpdate();
        return;
      }
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.preserve_base = !state.eliteSample.preserve_base;
        sampleBtn.textContent = state.eliteSample.preserve_base ? 'Hide Base-Country Sample' : 'Preview Domestic VAT Kept';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const isPro = !!(meta && meta.is_pro);
      const baseCountry = String((meta && meta.base_country) || 'DE').toUpperCase();
      const scenario = (previewCtx && previewCtx.vatScenario) || 'eu_valid';
      const forceSample = !isPro && !!(state && state.eliteSample && state.eliteSample.preserve_base);
      const active = !!draft.enabled;
      const euOnly = isPro && !!draft.eu_only;
      const showFld = active && (!euOnly || scenario !== 'non_eu');
      const preserve = (isPro && !!draft.preserve_base) || forceSample;
      const validate = !!draft.validate;
      const exemptOn = !!draft.disable_for_valid;
      const subtotal = 100;
      const vatRate = 0.20;

      let viesStatus = 'not_checked';
      let taxExempt = false;
      let notice = '';
      let checkoutOk = true;
      const sampleEu = baseCountry === 'DE' ? 'AT' : 'DE';
      const baseLbl = baseCountry || 'DE';

      if (active && scenario !== 'non_eu') {
        if (scenario === 'eu_valid') {
          viesStatus = validate ? 'valid' : 'unvalidated';
          if (validate && exemptOn) {
            taxExempt = !(preserve && baseCountry === sampleEu);
          }
          notice = taxExempt
            ? 'VAT is valid. Reverse charge 0% VAT applied.'
            : (!validate ? 'Saved on order without live VIES tax exemption.' : 'Valid VAT number; base country VAT kept.');
        } else if (scenario === 'domestic') {
          viesStatus = validate ? 'valid' : 'unvalidated';
          if (validate && exemptOn) {
            taxExempt = !preserve;
          }
          notice = taxExempt
            ? `Warning: Domestic (${baseCountry || 'Base'}) buyer exempted to 0% VAT (base-country preservation is Off).`
            : `Valid VAT recorded; domestic ${baseCountry || 'Base'} VAT kept charged.`;
        } else if (scenario === 'invalid') {
          viesStatus = 'invalid';
          taxExempt = false;
          if (validate) {
            checkoutOk = false;
            notice = draft.not_valid_message || 'EU VAT Number is not valid.';
          } else {
            notice = 'Validation Off: checkout proceeds with standard VAT.';
          }
        } else if (scenario === 'vies_down') {
          viesStatus = 'unavailable';
          taxExempt = false;
          if (validate) {
            checkoutOk = false;
            notice = 'VIES unreachable: Checkout blocks EU order unless Validate is turned off or buyer clears VAT.';
          } else {
            notice = 'Validation Off: standard VAT charged despite VIES outage.';
          }
        }
      } else if (active && scenario === 'non_eu') {
        notice = euOnly
          ? 'VAT field hidden cleanly for non-EU shoppers.'
          : 'VAT field stays optional for non-EU billing addresses.';
      }

      const vatAmt = taxExempt ? 0 : Number((subtotal * vatRate).toFixed(2));
      const total = Number((subtotal + vatAmt).toFixed(2));

      return {
        active,
        show_field: showFld,
        label: String(draft.field_label || '').trim() || 'EU VAT Number',
        required: !!draft.field_required && scenario !== 'non_eu',
        vies_status: viesStatus,
        tax_exempt: taxExempt,
        checkout_ok: checkoutOk,
        sample_code: scenario === 'domestic'
          ? `${baseLbl}123456789`
          : (scenario === 'invalid' ? `${sampleEu}000000000` : (scenario === 'non_eu' ? '' : `${sampleEu}123456789`)),
        notice,
        subtotal,
        vat_amount: vatAmt,
        total,
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const baseCountry = (meta && meta.base_country) || 'DE';
      const sampleEu = baseCountry === 'DE' ? 'AT' : 'DE';
      const scenario = (previewCtx && previewCtx.vatScenario) || 'eu_valid';
      const autoBadge = state.autoEnabled.enabled ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';
      const scenarios = [
        { id: 'eu_valid', label: `EU B2B (${sampleEu}, valid)` },
        { id: 'domestic', label: `Domestic B2B (${baseCountry})` },
        { id: 'invalid', label: 'Invalid VAT' },
        { id: 'vies_down', label: 'VIES Outage' },
        { id: 'non_eu', label: 'Non-EU (US)' },
      ];

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After EU VAT Preview</h2>
            <div class="wcj-v10-preview-meta">
              <span>Deterministic simulation (live VIES not called) &bull; Disclaimer: confirm tax rules with accountant</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-chips" style="margin-bottom:12px;">
          <span>Buyer Scenario:</span>
          ${scenarios.map((s) => `<button type="button" class="wcj-v10-chip ${scenario === s.id ? 'is-active' : ''}" data-vat-scenario="${s.id}">${esc(s.label)}</button>`).join('')}
        </div>
        <div class="wcj-v10-ba-grid">
          ${renderVatMockup(b, 'BEFORE (Live Store)', false, draft, meta, esc)}
          ${renderVatMockup(a, 'AFTER (Your Draft)', true, draft, meta, esc)}
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
