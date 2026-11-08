/* One existing discount, conditional arithmetic only. No product/cart or expression evaluation. */
(function () {
 'use strict';
 const cfg = window.wcjGlobalDiscountJob, root = document.getElementById('wcj-discount-job');
 if (!cfg || !root) return;
 const el = id => document.getElementById('discount-' + id);
 let current = null, busy = false, parked = false, review = null, undoReview = false, desired = '', dirty = false;
 const decimal = (value, example = false) => {
  if (typeof value !== 'string') return null;
  const m = (example ? /^(0|[1-9][0-9]{0,3})(?:\.([0-9]{1,4}))?$/ : /^(0|[1-9][0-9]?)(?:\.([0-9]{1,4}))?$/).exec(value);
  if (!m || m[0] !== value) return null;
  const n = Number(m[1]) * 10000 + Number((m[2] || '').padEnd(4, '0'));
  return n > 0 && n <= (example ? 99999999 : 999999) ? n : null;
 };
 const arithmetic = (amount, percent) => {
  const a = decimal(amount, true), p = decimal(percent);
  if (a === null || p === null) return null;
  const digits = String(a * (1000000 - p)).padStart(11, '0'), fraction = digits.slice(-10).replace(/0+$/, '');
  return digits.slice(0, -10) + (fraction ? '.' + fraction : '');
 };
 const message = (text, focus = false) => { el('message').textContent = text; if (focus) el('message').focus(); };
 const list = (id, lines) => { el(id).replaceChildren(); lines.forEach(line => { const li = document.createElement('li'); li.textContent = line; el(id).appendChild(li); }); };
 const invalidate = () => { review = null; undoReview = false; el('review').hidden = true; };
 const problem = () => decimal(el('percent').value) === null ? 'Enter a positive percentage below 100, with at most four decimals and no signs, spaces, commas or percent character. Blank or 0 does not disable the group.' : decimal(el('example').value, true) === null ? 'Enter a positive example amount up to 9999.9999 with at most four decimals.' : '';
 function remember() {
  try {
   if (current && (dirty || parked || el('example').value !== '80')) sessionStorage.setItem(cfg.storage, JSON.stringify({ group: current.group, guard: current.guard, percent: el('percent').value, example: el('example').value }));
   else sessionStorage.removeItem(cfg.storage);
  } catch (_) { /* The open-page draft remains if browser storage is unavailable. */ }
 }
 function update() {
  dirty = !!current && decimal(el('percent').value) !== decimal(current.percent);
  const bad = problem(), blocked = !current || !!current.reason || parked || busy;
  el('percent').disabled = blocked; el('example').disabled = blocked;
  el('group').disabled = busy || !el('group').options.length;
  el('load').disabled = busy;
  el('save').disabled = blocked || !dirty || !!bad || !!review;
  el('undo').disabled = blocked || dirty || !current.receipt || !current.receipt.undo || !!bad || !!review;
  el('confirm').disabled = blocked || !review; el('cancel').disabled = busy;
  el('draft-status').textContent = parked ? 'Draft parked. Deliberately discard and load current values before another change.' : current && current.reason ? 'Native settings only. Existing values are unchanged.' : bad || (dirty ? 'Unsaved discount draft.' : 'No percentage change to save.');
  if (!blocked && !bad) {
   el('stored').textContent = 'A changed ' + el('percent').value + '% discount is stored as -' + el('percent').value + '. Scope, order and activation stay unchanged.';
   el('effect').textContent = 'If group ' + current.group + ' is the first applicable group and receives your example ' + el('example').value + ', this discount alone gives ' + arithmetic(el('example').value, el('percent').value) + '. Unrounded arithmetic only, before tax and other adjustments. Not a product or checkout quote.';
  } else {
   el('stored').textContent = 'No change is ready to save. Native values have not been converted.';
   el('effect').textContent = parked ? 'This draft is parked; no current consequence is claimed. Discard and load current values deliberately.' : 'No supported draft illustration is available. Correct the input or use full settings; no price or setting changed.';
  }
  remember();
 }
 async function request(op, extra = {}) {
  const data = new URLSearchParams({ action: 'wcj_global_discount_job_' + op, nonce: cfg.nonces[op], group: current ? current.group : '', ...extra });
  const response = await fetch(cfg.url, { method: 'POST', credentials: 'same-origin', body: data });
  let body; try { body = await response.json(); } catch (_) { throw { message: 'The outcome could not be read. Your draft is parked; discard and load current values.' }; }
  if (!response.ok || !body.success) throw body.data || { message: 'The request was not accepted. Your draft is parked.' };
  return body.data;
 }
 function show(fresh) {
  current = fresh; parked = false; desired = fresh.group; invalidate(); el('percent').value = fresh.percent; el('example').value = '80';
  el('group').replaceChildren();
  fresh.inventory.forEach(item => { const option = document.createElement('option'); option.value = item.group; option.textContent = item.label + ' (enabled, percentage)'; el('group').appendChild(option); });
  el('group').value = fresh.group;
  el('current').textContent = fresh.raw === null ? 'No supported saved coefficient' : fresh.percent ? fresh.percent + '% discount; native stored value ' + fresh.raw : 'Native stored value: ' + (fresh.raw === '' ? 'blank' : fresh.raw);
  el('status').textContent = fresh.status; el('position').textContent = fresh.position; list('scope', fresh.scope); list('order', fresh.order);
  el('refusal').textContent = fresh.reason; el('refusal').hidden = !fresh.reason;
  el('native').href = fresh.native; el('other').replaceChildren();
  fresh.other_groups.forEach(item => { const li = document.createElement('li'); li.appendChild(document.createTextNode(item.label + ': ' + item.reason + ' ')); const link = document.createElement('a'); link.href = item.native; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Open full settings (new tab)'; li.appendChild(link); el('other').appendChild(li); });
  el('other-wrap').hidden = !fresh.other_groups.length;
  el('receipt').hidden = !fresh.receipt;
  if (fresh.receipt) {
   el('receipt-state').textContent = fresh.receipt.status === 'undone' ? 'Previous discount restored and read back.' : 'Selected discount saved and read back.';
   el('receipt-change').textContent = 'Native value ' + fresh.receipt.before + ' to ' + fresh.receipt.after + '.';
   el('receipt-summary').textContent = fresh.receipt.summary; el('receipt-current').textContent = 'Current context: ' + fresh.receipt.current;
  }
  update();
 }
 function onError(e) { invalidate(); parked = true; message(e && e.message ? e.message : 'The outcome could not be verified. Your draft is parked. Discard and load current values.', true); }
 async function load(discard = false) {
  if (busy) return;
  let cached = null;
  if (!discard) { try { cached = JSON.parse(sessionStorage.getItem(cfg.storage)); } catch (_) { cached = null; } }
  busy = true; invalidate(); update();
  try {
   const target = discard ? desired : cached && typeof cached.group === 'string' ? cached.group : '';
   const fresh = await request('load', { group: target || '' }); show(fresh);
   if (!discard && cached && cached.group === fresh.group && typeof cached.percent === 'string' && typeof cached.example === 'string') {
    el('percent').value = cached.percent; el('example').value = cached.example; current.guard = cached.guard; parked = true;
    message('An unsaved draft was restored and parked. Current configuration is shown without rebasing the draft. Copy it if needed, then deliberately discard and load current values.');
   } else message(fresh.reason || 'Current discount configuration loaded. No settings changed.');
  } catch (e) { onError(e); } finally { busy = false; update(); }
 }
 function payload(undo) { return { guard: current.guard, percent: undo ? '' : el('percent').value, example: el('example').value, undo: undo ? 'yes' : 'no', review: '{}' }; }
 async function act(undo) {
  if (busy || parked || !current || current.reason || problem() || (undo ? dirty || !current.receipt || !current.receipt.undo : !dirty)) return;
  busy = true; update();
  try {
   review = await request('review', payload(undo)); undoReview = undo;
   el('review-change').textContent = 'Native stored value ' + review.before + ' to ' + review.after + '.'; list('review-scope', review.scope); el('review-summary').textContent = review.summary;
   el('confirm').textContent = undo ? 'Confirm and restore previous discount' : 'Confirm and save reviewed discount'; el('review').hidden = false; el('review-heading').focus();
   message('Review this exact money-changing setting and its unchanged scope. Nothing has been saved yet.');
  } catch (e) { onError(e); } finally { busy = false; update(); }
 }
 el('form').addEventListener('submit', e => { e.preventDefault(); act(false); });
 ['percent', 'example'].forEach(id => el(id).addEventListener('input', () => { invalidate(); update(); }));
 el('group').addEventListener('change', () => {
  desired = el('group').value; invalidate();
  if (current && (dirty || parked || el('example').value !== '80')) { el('group').value = current.group; parked = true; message('Your draft is parked. Discard and load current values will switch to the chosen group. Nothing was saved.', true); update(); }
  else load(true);
 });
 el('load').addEventListener('click', () => load(true)); el('undo').addEventListener('click', () => act(true));
 el('cancel').addEventListener('click', () => { invalidate(); message('Review cancelled. Draft retained.'); update(); el('save').focus(); });
 el('confirm').addEventListener('click', async () => {
  if (!review || busy || parked || !current) return;
  const undo = undoReview, token = { expires: review.expires, token: review.token, confirm: 'yes' };
  busy = true; update();
  try { const fresh = await request(undo ? 'undo' : 'save', { ...payload(undo), review: JSON.stringify(token) }); show(fresh); message(undo ? 'Exact previous discount restored and read back. No other setting changed.' : 'Selected discount saved and read back. Scope, order and activation unchanged. This is not a product or checkout quote.', true); }
  catch (e) { onError(e); } finally { busy = false; update(); }
 });
 window.addEventListener('focus', async () => {
  if (!current || busy || parked) return;
  invalidate(); update(); const group = current.group, guard = current.guard;
  try { const fresh = await request('load', { group }); if (current && current.group === group && current.guard === guard && fresh.guard !== guard) { parked = true; message('Native settings or another dependency changed. Your draft is parked. Discard and load current values before saving.', true); update(); } }
  catch (e) { onError(e); update(); }
 });
 window.addEventListener('beforeunload', e => { if (dirty || parked) { e.preventDefault(); e.returnValue = ''; } });
 load();
}());
