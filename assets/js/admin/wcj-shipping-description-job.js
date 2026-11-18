(function () {
 'use strict';
 const root = document.getElementById('wcj-description-job'); if (!root) return;
 const cfg = window.wcjShippingDescription;
 const el = id => document.getElementById('description-' + id);
 let current = null, busy = false, parked = false, draft = null, departed = false;
 const storage = cfg.storage;
 function readDraft() { try { return JSON.parse(localStorage.getItem(storage)); } catch (_) { return null; } }
 function persist() { try { if (draft) localStorage.setItem(storage, JSON.stringify(draft)); else localStorage.removeItem(storage); } catch (_) {} }
 function message(text) { el('message').textContent = text; }
 function changed() { return current && el('text').value !== current.value; }
 // Mirrors the installed WP strict is_serialized predicate, without changing input.
 function serialized(value) {
  const v = value.replace(/^[ \n\r\t\v\0]+|[ \n\r\t\v\0]+$/g, '');
  if (v === 'N;') return true;
  if (v.length < 4 || v[1] !== ':' || !/[;}]$/.test(v)) return false;
  if (v[0] === 's' && v[v.length - 2] !== '"') return false;
  if (/^[saOE]$/.test(v[0])) return /^[saOE]:[0-9]+:/.test(v);
  return /^[bid]:[0-9.E+\-]+;$/.test(v);
 }
 function problem(value) {
  if (serialized(value) || /&(?:Aacute|Agrave|Atilde|Ccedil|Eacute|Egrave|Iacute|Igrave|Ntilde|Oacute|Ograve|Oslash|Otilde|Uacute|Ugrave|Yacute|aacute|agrave|atilde|brvbar|ccedil|curren|divide|eacute|egrave|frac12|frac14|frac34|iacute|igrave|iquest|middot|ntilde|oacute|ograve|oslash|otilde|plusmn|uacute|ugrave|yacute|AElig|Acirc|Aring|Ecirc|Icirc|Ocirc|THORN|Ucirc|acirc|acute|aelig|aring|cedil|ecirc|icirc|iexcl|laquo|micro|ocirc|pound|raquo|szlig|thorn|times|ucirc|Auml|COPY|Euml|Iuml|Ouml|QUOT|Uuml|auml|cent|copy|euml|iuml|macr|nbsp|ordf|ordm|ouml|para|quot|sect|sup1|sup2|sup3|uuml|yuml|AMP|ETH|REG|amp|deg|eth|not|reg|shy|uml|yen|GT|LT|gt|lt)/.test(value)) return 'Serialized values or HTML character references need full settings; no conversion is performed.';
  if (new TextEncoder().encode(value).length > 500) return 'Use at most 500 UTF-8 bytes; no text has been shortened.';
  if (/[<>\[\]\p{C}\p{Zl}\p{Zp}]/u.test(value) || /&(?:#[0-9]+;?|#x[0-9a-f]+;?|[a-z][a-z0-9]+;)/i.test(value) || (value !== '' && /^[\s\p{Z}]+$/u.test(value))) return 'Use literal single-line text, without markup, entities, shortcodes, control characters or whitespace-only input.';
  return '';
 }
 function update() {
  const value = el('text').value, invalid = problem(value);
  el('validation').textContent = invalid || (new TextEncoder().encode(value).length + ' / 500 UTF-8 bytes. ' + (changed() ? 'Draft, not saved.' : 'No wording change.'));
  el('save').disabled = busy || parked || !current || !!current.reason || !!invalid || !changed();
  el('target').disabled = busy || !current || parked;
  el('text').disabled = busy || !current || !!current.reason;
  el('undo').disabled = busy || parked || changed() || !current || !current.receipt || !current.receipt.undo;
  el('discard').disabled = busy;
  const supported = current && !current.reason && !invalid;
  const label = '[Shipping label, including its displayed price]';
  el('sample').textContent = supported ? (current.position === 'before' ? value + label : label + value) : 'No supported draft illustration. Existing values remain unchanged; use full settings.';
 }
 function remember() { if (current && (changed() || parked)) { draft = {target: current.target, text: el('text').value, guard: current.guard}; } else draft = null; persist(); }
 function park(text) { parked = true; remember(); message(text); update(); }
 async function request(op, data) {
  const body = new URLSearchParams(Object.assign({action: 'wcj_shipping_description_' + op, nonce: cfg.nonces[op]}, data));
  const response = await fetch(cfg.url, {method: 'POST', credentials: 'same-origin', body});
  const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.data && result.data.message || 'The outcome could not be verified.'); return result.data;
 }
 function stateText(state) { return state === null ? '(not stored)' : '“' + state.raw + '” [autoload: ' + state.autoload + ']'; }
 function display(value, keepDraft) {
  current = value;
  el('target').replaceChildren();
  Object.keys(value.inventory).forEach(key => { const opt = document.createElement('option'); opt.value = key; opt.textContent = value.inventory[key].label; el('target').append(opt); });
  el('target').value = value.target;
  el('scope').textContent = value.scope; el('status').textContent = value.status;
  el('context').textContent = 'Existing mode: ' + (value.mode === 'yes' ? 'instance' : 'method') + '. Effective position: ' + value.position + '; visibility: ' + value.visibility + '. Raw saved position: ' + value.raw_position + '; visibility: ' + value.raw_visibility + '. Globals are read-only.';
  el('matches').replaceChildren();
  if (value.item) { value.item.matches.forEach(row => { const li = document.createElement('li'); li.textContent = (row.zone_name || 'Zone ' + row.zone_id) + ': ' + row.method_id + ' #' + row.instance_id + ' (' + (row.is_enabled === '1' ? 'enabled' : 'off') + ')'; el('matches').append(li); }); el('woo').href = value.item.woo; }
  if (value.item && !value.item.matches.length) { const li = document.createElement('li'); li.textContent = 'No matching configured zone instance was found. Registered method-wide wording does not create or offer a rate.'; el('matches').append(li); }
  el('saved').textContent = value.exists ? 'Saved exact text: “' + value.value + '”' : 'No description option is stored.';
  el('text').value = keepDraft ? draft.text : value.value;
  el('receipt').hidden = !value.receipt;
  if (value.receipt) { el('receipt-summary').textContent = value.receipt.summary; el('changes').textContent = 'Before: ' + stateText(value.receipt.before) + '\nAfter: ' + stateText(value.receipt.after); el('receipt-current').textContent = value.receipt.current + (value.receipt.undo ? '' : ' Guarded Undo is not currently available.'); }
  el('next').textContent = value.next;
  message(value.reason || (keepDraft ? 'Your saved local draft is parked, not rebased. Discard and load current values before another change.' : 'Current saved values loaded. No settings changed.'));
  update();
 }
 async function load(target, keepDraft) {
  busy = true; update();
  try { const value = await request('load', {target}); parked = !!keepDraft; display(value, keepDraft); if (!keepDraft) { draft = null; persist(); } }
  catch (error) { park(error.message); }
  finally { busy = false; update(); }
 }
 el('text').addEventListener('input', () => { remember(); update(); });
 el('target').addEventListener('change', () => { const target = el('target').value; if (changed() || parked) { el('target').value = current.target; park('Your draft belongs to the current target. Discard and load current values before choosing another target.'); } else load(target, false); });
 el('discard').addEventListener('click', () => { const target = current ? current.target : (draft ? draft.target : ''); draft = null; persist(); parked = false; load(target, false); });
 el('form').addEventListener('submit', async event => {
  event.preventDefault(); if (el('save').disabled) return; remember(); busy = true; update();
  try { const value = await request('save', {target: current.target, guard: current.guard, text: el('text').value}); draft = null; persist(); parked = false; display(value, false); message('Description save completed. Read the saved receipt and activation state below.'); }
  catch (error) { park(error.message); } finally { busy = false; update(); }
 });
 el('undo').addEventListener('click', async () => {
  if (el('undo').disabled) return; busy = true; update();
  try { const value = await request('undo', {target: current.target, guard: current.guard}); draft = null; persist(); parked = false; display(value, false); message('Guarded Undo completed. Exact prior wording or absence restored.'); }
  catch (error) { park(error.message); } finally { busy = false; update(); }
 });
 root.querySelectorAll('a[target="_blank"]').forEach(link => link.addEventListener('click', () => { departed = true; if (current) park('Another editor or guide is open. Your draft is parked. Discard and load current values when you return.'); }));
 window.addEventListener('blur', () => { departed = true; remember(); });
 window.addEventListener('focus', () => { if (departed && current && !busy) { departed = false; park('You returned to the task. Current configuration may have changed. Discard and load current values before saving.'); } });
 window.addEventListener('beforeunload', event => { remember(); if (changed()) { event.preventDefault(); event.returnValue = ''; } });
 draft = readDraft(); if (!draft || typeof draft.target !== 'string' || typeof draft.text !== 'string') draft = null;
 load(draft ? draft.target : '', !!draft);
}());
