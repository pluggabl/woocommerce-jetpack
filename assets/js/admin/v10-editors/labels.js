(function () {
  'use strict';

  window.wcjV10Editors = window.wcjV10Editors || {};

  function renderProductCard(card, label, isAfter, compare, esc) {
    const btnDiff = compare && card.add_to_cart_text !== compare.add_to_cart_text;
    const priceDiff = compare && (
      card.price_prefix !== compare.price_prefix
      || card.price_suffix !== compare.price_suffix
      || card.price_instead !== compare.price_instead
    );
    const badgeDiff = compare && (
      card.sale_badge_text !== compare.sale_badge_text
      || !!card.sale_flash_hide_everywhere !== !!compare.sale_flash_hide_everywhere
    );
    const badgeText = card.sale_badge_text || 'Sale!';
    const showOnSale = card.sample_on_sale !== false && !!card.sample_sale;
    const showBadge = showOnSale && !card.sale_flash_hide_everywhere;
    const hasInstead = !!String(card.price_instead || '').trim();

    return `
      <div class="wcj-v10-ba-box ${isAfter ? 'is-after' : ''}">
        <div class="wcj-v10-ba-tag-row">
          <span class="wcj-v10-ba-tag">${esc(label)}</span>
        </div>
        <div class="wcj-v10-pcard">
          <div class="wcj-v10-pimg">
            ${showBadge ? `<span class="wcj-v10-sale-pill ${badgeDiff ? 'wcj-v10-highlight' : ''}">${esc(badgeText)}</span>` : ''}
            ${card.sample_image
              ? `<img src="${esc(card.sample_image)}" alt="${esc(card.sample_name || 'Product')}" />`
              : '<span>PRODUCT IMAGE</span>'}
          </div>
          <div class="wcj-v10-pbody">
            <div style="font-weight:600;">${esc(card.sample_name || 'Sample Storefront Product')}</div>
            <div class="wcj-v10-pprice ${priceDiff ? 'wcj-v10-highlight' : ''}" style="border-radius:6px;padding:3px;">
              ${hasInstead
                ? `<strong>${esc(card.price_instead)}</strong>`
                : `<span>${esc(card.price_prefix || '')}</span>
                   ${showOnSale
                     ? `<span style="text-decoration:line-through;color:#8c8f94;">${esc(card.sample_regular || '$95.00')}</span>
                        <strong>${esc(card.sample_sale || '$76.00')}</strong>`
                     : `<strong>${esc(card.sample_regular || '$95.00')}</strong>`}
                   <span>${esc(card.price_suffix || '')}</span>`}
            </div>
            <div class="wcj-v10-pbtn ${btnDiff ? 'wcj-v10-highlight' : ''}">${esc(card.add_to_cart_text || 'Add to cart')}</div>
          </div>
        </div>
      </div>
    `;
  }

  window.wcjV10Editors.button_price_labels = {
    title: 'Button, Price & Sale Labels',

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
      return ['sale_flash_html'];
    },

    isLiveOn(live) {
      return !!(live && (live.add_to_cart_enabled || live.more_button_enabled || live.price_labels_enabled || live.sale_flash_enabled));
    },

    isAutoEnabled(autoEnabled) {
      return Object.keys(autoEnabled || {}).some((k) => autoEnabled[k]);
    },

    getToggleTarget(key) {
      if (['add_to_cart_single', 'add_to_cart_archive', 'apply_to_variable'].includes(key)) {
        return ['add_to_cart_enabled'];
      }
      if (['price_prefix', 'price_suffix', 'price_instead'].includes(key)) {
        return ['price_labels_enabled'];
      }
      if (['sale_badge_text', 'sale_flash_hide_everywhere'].includes(key)) {
        return ['sale_flash_enabled'];
      }
      if (['place_order_text', 'place_order_override'].includes(key)) {
        return ['more_button_enabled'];
      }
      return [];
    },

    getPresets() {
      return [
        {
          id: 'lbl_buy',
          label: 'Buy Now CTA',
          patch: {
            add_to_cart_enabled: true,
            add_to_cart_single: 'Buy Now',
            add_to_cart_archive: 'Quick Buy',
          },
        },
        {
          id: 'lbl_vat',
          label: 'EU VAT Suffix',
          patch: {
            price_labels_enabled: true,
            price_suffix: ' incl. VAT',
          },
        },
        {
          id: 'lbl_sale',
          label: 'Save 20% Badge',
          patch: {
            sale_flash_enabled: true,
            sale_badge_text: 'Save 20%',
          },
        },
      ];
    },

    getValidationError() {
      return '';
    },

    getPostPublishTip(meta) {
      if (meta && meta.price_prefix_locked) {
        return 'Live on your store! Booster Elite also adds price prefixes (like "From") and per-category badges.';
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
            <a href="${esc(cs.add_to_cart)}">Add to Cart Button Labels</a>
            <a href="${esc(cs.price_labels)}">Custom Price Labels</a>
            <a href="${esc(cs.sale_flash)}">Sale Flash Badge</a>
            <a href="${esc(cs.more_button_labels)}">Place Order Button Label</a>
          </div>
        </details>
      `;
    },

    renderForm(draft, meta, deps) {
      const esc = deps.esc;
      const prefixLocked = !!(meta && meta.price_prefix_locked);
      const sampleActive = !!(deps && deps.eliteSample && deps.eliteSample.price_prefix);
      const upgradeUrl = (deps && deps.upgradeUrl) || 'https://booster.io/buy-booster/';

      return `
        <div class="wcj-v10-section-head">
          <h2>Simple Setup</h2>
        </div>
        ${deps.renderPresetsHtml('button_price_labels')}
        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="add_to_cart_enabled" ${draft.add_to_cart_enabled ? 'checked' : ''} />
            <span>Enable Add to Cart Button Labels</span>
          </label>
        </div>
        <div class="wcj-v10-row-2">
          <div class="wcj-v10-field">
            <label for="wcj-l-single">Single Product Button</label>
            <input id="wcj-l-single" type="text" data-field="add_to_cart_single" value="${esc(draft.add_to_cart_single)}" />
          </div>
          <div class="wcj-v10-field">
            <label for="wcj-l-archive">Shop / Archive Button</label>
            <input id="wcj-l-archive" type="text" data-field="add_to_cart_archive" value="${esc(draft.add_to_cart_archive)}" />
          </div>
        </div>

        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="price_labels_enabled" ${draft.price_labels_enabled ? 'checked' : ''} />
            <span>Enable Price Labels</span>
          </label>
        </div>
        ${prefixLocked
          ? `<div class="wcj-v10-field">
              <label for="wcj-l-suffix">After Price</label>
              <input id="wcj-l-suffix" type="text" data-field="price_suffix" value="${esc(draft.price_suffix)}" placeholder=" incl. VAT" />
            </div>
            <div class="wcj-v10-elite-sample-box">
              <strong>Elite adds Price Prefix (&ldquo;From &rdquo;) &amp; Per-Category Labels</strong>
              <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:6px;">
                <button type="button" class="wcj-v10-btn" data-action="toggle-elite-sample">
                  ${sampleActive ? 'Hide sample prefix preview' : 'Show &ldquo;From &rdquo; on my product'}
                </button>
                <a class="wcj-v10-nav-link" href="${esc(upgradeUrl + '?utm_source=booster_free&utm_medium=editor&utm_campaign=price_prefix')}" target="_blank" rel="noopener">Explore Elite &#8599;</a>
              </div>
            </div>`
          : `<div class="wcj-v10-row-2">
              <div class="wcj-v10-field">
                <label for="wcj-l-prefix">Before Price</label>
                <input id="wcj-l-prefix" type="text" data-field="price_prefix" value="${esc(draft.price_prefix)}" placeholder="From " />
              </div>
              <div class="wcj-v10-field">
                <label for="wcj-l-suffix">After Price</label>
                <input id="wcj-l-suffix" type="text" data-field="price_suffix" value="${esc(draft.price_suffix)}" placeholder=" incl. VAT" />
              </div>
            </div>`}

        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="sale_flash_enabled" ${draft.sale_flash_enabled ? 'checked' : ''} />
            <span>Enable Custom Sale Badge</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-l-sale">Sale Badge Text</label>
          <input id="wcj-l-sale" type="text" data-field="sale_badge_text" value="${esc(draft.sale_badge_text)}" placeholder="Sale!" />
        </div>

        <div class="wcj-v10-field">
          <label class="wcj-v10-check">
            <input type="checkbox" data-field="more_button_enabled" ${draft.more_button_enabled ? 'checked' : ''} />
            <span>Enable Checkout Place Order Label</span>
          </label>
        </div>
        <div class="wcj-v10-field">
          <label for="wcj-l-place">Checkout Place Order Button Text</label>
          <input id="wcj-l-place" type="text" data-field="place_order_text" value="${esc(draft.place_order_text)}" placeholder="Place order" />
        </div>

        <details class="wcj-v10-more-rules">
          <summary>More Rules &amp; Options</summary>
          <div class="wcj-v10-more-rules-body">
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="apply_to_variable" ${draft.apply_to_variable ? 'checked' : ''} />
                <span>Also apply Add to Cart button labels to Variable, Grouped &amp; External products</span>
              </label>
            </div>
            <div class="wcj-v10-field">
              <label for="wcj-l-instead">Replace Entire Price Text (optional)</label>
              <input id="wcj-l-instead" type="text" data-field="price_instead" value="${esc(draft.price_instead)}" placeholder="e.g. Call for price" />
            </div>
            <div class="wcj-v10-field">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="sale_flash_hide_everywhere" ${draft.sale_flash_hide_everywhere ? 'checked' : ''} />
                <span>Hide Sale Badge everywhere on storefront</span>
              </label>
            </div>
            <div class="wcj-v10-field" style="margin-bottom:0;">
              <label class="wcj-v10-check">
                <input type="checkbox" data-field="place_order_override" ${draft.place_order_override ? 'checked' : ''} />
                <span>Force override payment gateway Place Order button text</span>
              </label>
            </div>
          </div>
        </details>
      `;
    },

    handleClick(target, state, actions) {
      const sampleBtn = target.closest('[data-action="toggle-elite-sample"]');
      if (sampleBtn) {
        state.eliteSample.price_prefix = !state.eliteSample.price_prefix;
        sampleBtn.innerHTML = state.eliteSample.price_prefix ? 'Hide sample prefix preview' : 'Show &ldquo;From &rdquo; on my product';
        actions.applyLocalPreviewImmediate();
      }
    },

    computeLocalAfter(draft, meta, previewCtx, state) {
      const sym = meta.currency_symbol || '$';
      const prod = meta.sample_product || {};
      const before = (state.preview && state.preview.before) || {};
      const hideSale = !!draft.sale_flash_enabled && !!draft.sale_flash_hide_everywhere;
      const samplePrefix = (meta.price_prefix_locked && state.eliteSample && state.eliteSample.price_prefix) ? 'From ' : '';

      return {
        add_to_cart_enabled: !!draft.add_to_cart_enabled,
        apply_to_variable: !!draft.apply_to_variable,
        more_button_enabled: !!draft.more_button_enabled,
        place_order_override: !!draft.place_order_override,
        price_labels_enabled: !!draft.price_labels_enabled,
        sale_flash_enabled: !!draft.sale_flash_enabled,
        sale_flash_hide_everywhere: hideSale,
        add_to_cart_text: (draft.add_to_cart_enabled && String(draft.add_to_cart_single || '').trim()) ? draft.add_to_cart_single : 'Add to cart',
        archive_text: (draft.add_to_cart_enabled && String(draft.add_to_cart_archive || '').trim()) ? draft.add_to_cart_archive : 'Add to cart',
        place_order_text: (draft.more_button_enabled && String(draft.place_order_text || '').trim()) ? draft.place_order_text : 'Place order',
        price_prefix: samplePrefix || (draft.price_labels_enabled ? (draft.price_prefix || '') : ''),
        price_suffix: draft.price_labels_enabled ? (draft.price_suffix || '') : '',
        price_instead: draft.price_labels_enabled ? String(draft.price_instead || '').trim() : '',
        sample_name: prod.name || before.sample_name || 'Sample Storefront Product',
        sample_image: prod.image_url || before.sample_image || '',
        sample_regular: prod.regular_price || before.sample_regular || (sym + '95.00'),
        sample_sale: prod.sale_price !== undefined ? prod.sale_price : (before.sample_sale || (sym + '76.00')),
        sample_on_sale: prod.on_sale !== undefined ? !!prod.on_sale : (before.sample_on_sale !== undefined ? !!before.sample_on_sale : true),
        sale_badge_text: (draft.sale_flash_enabled && String(draft.sale_badge_text || '').trim()) ? draft.sale_badge_text : 'Sale!',
      };
    },

    renderPreview(pv, draft, meta, previewCtx, state, deps) {
      const esc = deps.esc;
      const b = (pv && pv.before) || {};
      const a = (pv && pv.after) || {};
      const autoBadge = this.isAutoEnabled(state.autoEnabled) ? '<span class="wcj-v10-auto-pill">Auto-enabled in draft</span>' : '';

      return `
        <div class="wcj-v10-section-head">
          <div>
            <h2>Live Before &amp; After Storefront Card</h2>
            <div class="wcj-v10-preview-meta">
              <span>Live store vs draft &bull; Approximate preview for your theme</span>
              ${autoBadge}
            </div>
          </div>
        </div>
        <div class="wcj-v10-ba-grid">
          ${renderProductCard(b, 'BEFORE (Live Store)', false, null, esc)}
          ${renderProductCard(a, 'AFTER (Your Draft)', true, b, esc)}
        </div>
        <div class="wcj-v10-extra-preview">
          <div class="wcj-v10-extra-row">
            <strong>Shop / Archive Button:</strong>
            <span>${esc(b.archive_text || 'Add to cart')} &rarr; <strong>${esc(a.archive_text || 'Add to cart')}</strong> <span class="wcj-v10-badge">${a.apply_to_variable ? 'All product types' : 'Simple only'}</span></span>
          </div>
          <div class="wcj-v10-extra-row">
            <strong>Checkout Place Order Button:</strong>
            <span>${esc(b.place_order_text || 'Place order')} &rarr; <strong>${esc(a.place_order_text || 'Place order')}</strong>${a.place_order_override ? ' <span class="wcj-v10-badge">Force gateway override</span>' : ''}</span>
          </div>
        </div>
        ${deps.renderDiagnosticsHtml()}
      `;
    },
  };
})();
