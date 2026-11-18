/* global wcjProductDate, wp */
(function () {
 'use strict';
 const root = document.getElementById('wcj-product-date');
 if (!root) return;
 const $ = id => document.getElementById('wcj-pd-' + id);
 const t = value => wp.i18n.__(value, 'woocommerce-jetpack');
 if (!window.wcjProductDate || !window.wp || !wp.i18n) {
  $('status').textContent = 'The task could not initialize. Reload this page or use full settings; no changes were made.';
  return;
 }
 let state = null, busy = false, stale = false, review = null, reviewTimer = null;
 const draft = () => ({enabled: $('enabled').value, date: $('date').value});
 const equal = (a, b) => a && b && a.enabled === b.enabled && a.date === b.date;
 const dirty = () => !!state && !equal(draft(), state.draft);
 const storageKey = id => 'wcj-product-date:' + wcjProductDate.user + ':' + id;
 const notify = message => { $('status').textContent = message; };
 function forget(id) { try { sessionStorage.removeItem(storageKey(id)); } catch (_) {} }
 function persist() {
  if (!state || busy) return;
  try {
   if (dirty() || stale) sessionStorage.setItem(storageKey(state.id), JSON.stringify({guard: state.guard, draft: draft(), stale}));
   else forget(state.id);
  } catch (_) { $('draft-note').textContent = t('This browser cannot retain the draft across reloads. Keep this tab open or copy the draft before leaving.'); }
 }
 function clearReview() {
  review = null; clearTimeout(reviewTimer); $('review').hidden = true; $('confirm').checked = false;
 }
 function sync() {
  $('search').disabled = busy; $('search-button').disabled = busy;
  $('products').disabled = busy; $('load').disabled = busy || !$('products').value;
  const blocked = busy || !state || state.locked || stale;
  $('fields').disabled = blocked; $('fields').inert = blocked;
  $('save').disabled = blocked || !dirty();
  $('save').textContent = state && state.consequential ? t('Review purchase consequence') : t('Save');
  $('reload').disabled = busy; $('discard').disabled = busy || !state || (!dirty() && !stale);
  $('undo').disabled = busy || !state || !state.undo || dirty() || stale;
  $('confirm').disabled = busy || !review || stale;
  $('confirm-save').disabled = busy || !review || stale || !$('confirm').checked;
  $('cancel-review').disabled = busy;
  $('copy-draft').hidden = !state || !stale;
  $('retained-draft').value = state && stale ? t('Product override: ') + draft().enabled + '\n' + t('Available from date: ') + draft().date : ''; 
  if (state) $('draft-note').textContent = stale ? t('This draft is parked because current settings changed or a request could not be verified. It cannot be saved. Copy the retained draft below if needed, then discard and load current values.') : (dirty() ? t('Unsaved draft in this tab. Current values above are still the saved configuration.') : t('No unsaved draft.'));
 }
 async function api(op, data = {}) {
  const body = new URLSearchParams({action: 'wcj_product_date_' + op, op, nonce: wcjProductDate.nonces[op], ...data});
  const response = await fetch(wcjProductDate.url, {method: 'POST', credentials: 'same-origin', body});
  let json;
  try { json = await response.json(); } catch (_) { const e = new Error(t('The response could not be verified. Keep a copy of your draft and load current values.')); e.kind = 'uncertain'; throw e; }
  if (!response.ok || !json.success) { const e = new Error(json.data && json.data.message || t('The request failed.')); e.kind = json.data && json.data.kind || 'uncertain'; throw e; }
  return json.data;
 }
 function failed(e) { if (state && e.kind !== 'validation') stale = true; clearReview(); notify(e.message); }
 async function run(fn) {
  if (busy) return;
  busy = true; sync(); root.setAttribute('aria-busy', 'true');
  try { await fn(); } catch (e) { failed(e); }
  finally { busy = false; root.removeAttribute('aria-busy'); sync(); persist(); }
 }
 function lines(id, values) { $(id).replaceChildren(); (values || []).forEach(value => { const li = document.createElement('li'); li.textContent = value; $(id).append(li); }); }
 function inactiveNote(next) {
  if (next.module !== 'yes' && next.local !== 'yes') return t("This product's Date override is not active for shoppers yet: the Date module and per-product gate are off. Turn them on in Full Date settings. Other purchase restrictions may still apply.");
  if (next.module !== 'yes') return t("This product's Date override is not active for shoppers yet: the Date module is off. Turn it on in Full Date settings. Other purchase restrictions may still apply.");
  if (next.local !== 'yes') return t("This product's Date override is not active for shoppers yet: the per-product gate is off. Turn it on in Full Date settings. Other purchase restrictions may still apply.");
  return '';
 }
 function paint(next, restore = true) {
  state = next; stale = false; clearReview();
  const bookmark = new URL(window.location.href); bookmark.searchParams.set('product', next.id); window.history.replaceState(null, '', bookmark.href);
  $('editor').hidden = false; $('title').textContent = next.title + ' (#' + next.id + ')';
  $('enabled').replaceChildren(new Option(t('Disabled'), 'no'), new Option(t('Enabled'), 'yes'));
  if (!['yes','no'].includes(next.draft.enabled)) $('enabled').add(new Option(next.draft.enabled, next.draft.enabled));
  $('enabled').value = next.draft.enabled;
  $('date').value = next.draft.date;
  $('current').textContent = t('Product override: ') + next.draft.enabled + '. ' + t('Direct date: ') + (next.draft.date || t('(empty)'));
  $('gates').textContent = t('Module: ') + next.module + '; ' + t('global per-product gate: ') + next.local + '; ' + t('global monthly gate: ') + next.global + '; ' + t('action: ') + (next.action === 'non_purchasable' ? t('Make product non-purchasable') : (next.action === 'blank' ? t('Only output message') : next.action)) + '. ' + (next.paid ? t('This installed edition has native unlimited enabled Date products.') : t('Free supports one enabled Date product, separately from Time.'));
  const inactive = inactiveNote(next);
  $('inactive').textContent = inactive; $('inactive').hidden = !inactive;
  $('record-inactive').textContent = inactive; $('record-inactive').hidden = !inactive || !next.saved_record;
  $('boundary').textContent = next.summary;
  $('format').textContent = t('Required date format: ') + next.format + '. ' + t('Store timezone: ') + next.timezone + '.';
  $('lock').textContent = next.locked ? t('This stored value, duplicate metadata or date format is not safely editable here. Use the full product editor or full Date settings; the raw values below are retained.') : '';
  $('native').href = next.native; $('shopper').href = next.shopper;
  $('raw').textContent = JSON.stringify(next.raw, null, 2);
  $('record').textContent = next.saved_record ? (next.saved_record.status === 'applied' ? t('Saved configuration') : next.saved_record.status) + ' | ' + next.saved_record.time + (next.undo ? t('. Matching Undo is available.') : t('. This record is not currently eligible for Undo.')) : t('No saved change by you is recorded for this product.');
  lines('record-changes', next.saved_record && next.saved_record.changes);
  if (![...$('products').options].some(o => Number(o.value) === next.id)) $('products').add(new Option(next.title + ' (#' + next.id + ')', next.id));
  $('products').value = String(next.id);
  if (restore) {
   try {
    const saved = JSON.parse(sessionStorage.getItem(storageKey(next.id)) || 'null');
    if (saved && saved.draft && typeof saved.draft.date === 'string' && ['yes','no'].includes(saved.draft.enabled)) {
     if (saved.guard === next.guard && !saved.stale && equal(saved.draft, next.draft)) forget(next.id);
     else {
      $('enabled').value = saved.draft.enabled; $('date').value = saved.draft.date;
      stale = saved.guard !== next.guard || !!saved.stale || next.locked;
     }
    }
   } catch (_) {}
  }
 }
 $('search-form').addEventListener('submit', event => {
  event.preventDefault();
  run(async () => {
   const result = await api('search', {q: $('search').value});
   const select = $('products'); select.replaceChildren(new Option(t('Choose a product'), ''));
   result.items.forEach(item => select.add(new Option(item.title + ' (#' + item.id + ')', item.id)));
   if (state) { if (![...select.options].some(o => Number(o.value) === state.id)) select.add(new Option(state.title + ' (#' + state.id + ')', state.id)); select.value = String(state.id); }
   notify(t('Search complete. Up to 25 matches are shown. Choose a product and load it; no values were changed.'));
  });
 });
 $('products').addEventListener('change', sync);
 $('load').addEventListener('click', () => {
  const id = Number($('products').value); if (!id || busy) return;
  if (state && (dirty() || stale) && !window.confirm(t('Discard this unsaved draft and load the selected product? Cancel stays with your draft.'))) { $('products').value = String(state.id); return; }
  const previous = state && state.id;
  run(async () => {
   const next = await api('load', {product:id});
   if (previous) forget(previous);
   paint(next); notify(t('Current product values loaded. Nothing was saved.')); $('title').focus();
  });
 });
 ['enabled','date'].forEach(id => $(id).addEventListener('input', () => { clearReview(); sync(); persist(); }));
 async function startReview(undo) {
  await run(async () => {
   const result = await api('review', {product:state.id, guard:state.guard, draft:JSON.stringify(draft()), undo:undo ? 'yes' : 'no'});
   review = result; $('review-text').textContent = result.summary; lines('review-changes', result.changes); $('review').hidden = false; $('confirm').checked = false;
   $('confirm-save').textContent = undo ? t('Confirm and undo') : t('Confirm and save');
   $('review-title').focus(); notify(t('Read the purchase consequence. Nothing has been saved.'));
   reviewTimer = setTimeout(() => { clearReview(); sync(); notify(t('The review expired. Review again before saving.')); }, Math.max(0, result.expires * 1000 - Date.now()));
  });
 }
 async function save(undo, accepted) {
  await run(async () => {
   const next = await api(undo ? 'undo' : 'save', {product:state.id, guard:state.guard, draft:JSON.stringify(draft()), review:JSON.stringify(accepted || {})});
   forget(state.id); paint(next, false); notify(undo ? t('Matching previous values restored. Other settings were not changed.') : t('Saved and read back from native product metadata. Check the product with the intended shopper context.'));
  });
 }
 $('save').addEventListener('click', () => { if (!state || busy || stale || state.locked || !dirty()) return; if (state.consequential) startReview(false); else save(false); });
 $('undo').addEventListener('click', () => { if (!state || busy || stale || dirty() || !state.undo) return; if (state.consequential) startReview(true); else save(true); });
 $('confirm').addEventListener('change', sync);
 $('confirm-save').addEventListener('click', () => { if (!review || busy || stale || !$('confirm').checked) return; const accepted = {...review, confirm:'yes'}; save(!!review.undo, accepted); });
 $('cancel-review').addEventListener('click', () => { clearReview(); sync(); });
 async function reload(discard) {
  if (!state || busy) return;
  if ((dirty() || stale) && !window.confirm(t('Discard this draft and load current saved values? Cancel keeps your draft.'))) return;
  const id = state.id;
  await run(async () => { const next = await api('load', {product:id}); forget(id); paint(next, false); notify(discard ? t('Draft discarded. Current values loaded; no native values were changed.') : t('Current values loaded; nothing was saved.')); });
 }
 $('reload').addEventListener('click', () => reload(false));
 $('discard').addEventListener('click', () => reload(true));
 window.addEventListener('beforeunload', event => { if (dirty() || stale || busy) { persist(); event.preventDefault(); event.returnValue = ''; } });
 sync();
 const initial = new URL(window.location.href).searchParams.get('product');
 if (initial && /^[1-9][0-9]*$/.test(initial)) run(async () => { const next = await api('load', {product:initial}); paint(next); notify(t('Current product values loaded. Nothing was saved.')); });
})();
