/* Optional configuration editor. No checkout preview, auto-rebase or activation. */
(function () {
	'use strict';
	const cfg = window.wcjGatewayAmount;
	const root = document.getElementById('wcj-amount-job');
	if (!cfg || !root) return;
	const el = id => document.getElementById('amount-' + id);
	let current = null, busy = false, parked = false, review = null, undoReview = false, desired = '', dirty = false;
	const decimal = value => {
		const m = /^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,6}))?$/.exec(value);
		if (!m) return null;
		const key = m[1].padStart(9, '0') + (m[2] || '').padEnd(6, '0');
		return key === '000000000000000' ? null : key;
	};
	const none = v => v === null || v === '0';
	const equal = (a, b) => (none(a) && none(b)) || (decimal(a || '') !== null && decimal(a || '') === decimal(b || ''));
	const narrower = (before, after) => (!none(after.min) && (none(before.min) || decimal(after.min) > decimal(before.min))) || (!none(after.max) && (none(before.max) || decimal(after.max) < decimal(before.max)));
	const label = v => v === null ? 'No limit (not stored)' : v === '0' ? 'No limit (0)' : v === '' ? 'Blank (native-only)' : v;
	const draft = () => Object.fromEntries(['min', 'max'].map(side => [side, el(side + '-mode').value === 'none' ? '0' : el(side + '-value').value]));
	const setDraft = d => ['min', 'max'].forEach(side => { el(side + '-mode').value = d[side] === '0' ? 'none' : 'amount'; el(side + '-value').value = d[side] === '0' ? '' : d[side]; });
	const message = (text, focus = false) => { el('message').textContent = text; if (focus) el('message').focus(); };
	const list = (id, values) => { el(id).replaceChildren(); values.forEach(value => { const li = document.createElement('li'); li.textContent = value; el(id).appendChild(li); }); };
	const invalidate = () => { review = null; undoReview = false; el('review').hidden = true; };
	function remember() {
		try {
			if (current && (dirty || parked)) sessionStorage.setItem(cfg.storage, JSON.stringify({ gateway: current.gateway, guard: current.guard, draft: draft() }));
			else sessionStorage.removeItem(cfg.storage);
		} catch (_) { /* Draft still stays in the open page if browser storage is disabled. */ }
	}
	function validation(d) {
		for (const side of ['min', 'max']) if (d[side] !== '0' && decimal(d[side]) === null) return 'Choose No limit or enter a positive amount with at most six decimals, no commas or exponents.';
		if (d.min !== '0' && d.max !== '0' && decimal(d.min) > decimal(d.max)) return 'Minimum must not exceed maximum.';
		return '';
	}
	function update() {
		const d = draft();
		dirty = !!current && (!equal(current.bounds.min, d.min) || !equal(current.bounds.max, d.max));
		const problem = validation(d);
		const blocked = !current || !!current.reason || parked || busy;
		['min', 'max'].forEach(side => { el(side + '-mode').disabled = blocked; el(side + '-value').disabled = blocked || el(side + '-mode').value === 'none'; });
		el('gateway').disabled = busy || !el('gateway').options.length;
		el('load').disabled = busy || !el('gateway').options.length;
		el('save').disabled = blocked || !dirty || !!problem || !!review;
		el('save').textContent = current && !problem && narrower(current.bounds, d) ? 'Review restriction' : 'Save limits';
		el('draft-status').textContent = parked ? 'Draft parked. Discard and load is required.' : current && current.reason ? 'Use full settings. Existing values are unchanged.' : problem && dirty ? problem : dirty ? 'Unsaved draft.' : 'No changes to save.';
		el('undo').disabled = blocked || dirty || !current.receipt || !current.receipt.undo || !!review;
		el('confirm').disabled = blocked || !review;
		el('cancel').disabled = busy;
		if (current && !problem && !current.reason && !parked) {
			const parts = [];
			if (d.min !== '0') parts.push('below ' + d.min);
			if (d.max !== '0') parts.push('above ' + d.max);
			el('effect').textContent = parts.length ? 'If the module and method are enabled, this amount rule can remove this method ' + parts.join(' or ') + ' in ' + current.currency + ', using the read-only basis above. At an exact threshold this rule does not remove it. Other rules can still remove it; no other available method can mean checkout is blocked.' : 'With both bounds set to No limit, neither amount bound removes this method. Other restrictions remain. This does not enable a module or method.';
		} else {
			el('effect').textContent = parked ? 'Draft consequence is not current while parked. Existing values are unchanged by this draft; discard and load current values before reviewing.' : 'No supported draft consequence is available. Existing values are unchanged; correct the draft or use full settings.';
		}
		remember();
	}
	async function request(op, more = {}) {
		const data = new URLSearchParams({ action: 'wcj_gateway_amount_' + op, op, nonce: cfg.nonces[op], gateway: current ? current.gateway : el('gateway').value, ...more });
		const response = await fetch(cfg.url, { method: 'POST', credentials: 'same-origin', body: data });
		let body;
		try { body = await response.json(); } catch (_) { throw { message: 'The outcome could not be read. Your draft is parked; discard and load current values.', kind: 'parked' }; }
		if (!response.ok || !body.success) throw body.data || { message: 'Request failed. Keep your draft and load current values.', kind: 'parked' };
		return body.data;
	}
	function show(fresh) {
		current = fresh; parked = false; desired = fresh.gateway; el('gateway').value = fresh.gateway; invalidate(); setDraft(fresh.draft);
		el('status').textContent = fresh.status;
		el('current-min').textContent = label(fresh.bounds.min); el('current-max').textContent = label(fresh.bounds.max);
		el('basis').textContent = fresh.basis; el('currency').textContent = fresh.currency + '. ' + fresh.conversion;
		el('custom').hidden = !fresh.custom; el('refusal').textContent = fresh.reason; el('refusal').hidden = !fresh.reason;
		el('woo').href = fresh.woo;
		el('receipt').hidden = !fresh.receipt;
		if (fresh.receipt) {
			el('receipt-state').textContent = fresh.receipt.status === 'undone' ? 'Undone and read back.' : 'Saved configuration and read back. Not a checkout availability result.';
			list('receipt-changes', fresh.receipt.changes); el('receipt-summary').textContent = fresh.receipt.summary; el('receipt-current').textContent = 'Current context: ' + fresh.receipt.current;
		}
		update();
	}
	function onError(error) {
		invalidate();
		if (!error || error.kind !== 'validation') parked = true;
		message(error && error.message ? error.message : 'The request could not be verified. Your draft is parked. Discard and load current values.', true);
	}
	async function load(discard = false) {
		if (busy || !el('gateway').value) return;
		let cached = null;
		if (!discard) {
			try { cached = JSON.parse(sessionStorage.getItem(cfg.storage)); } catch (_) { cached = null; }
			if (cached && typeof cached.gateway === 'string' && Array.from(el('gateway').options).some(o => o.value === cached.gateway)) el('gateway').value = cached.gateway;
		}
		busy = true; invalidate(); update();
		try {
			const fresh = await request('load', { gateway: desired || el('gateway').value });
			show(fresh);
			if (!discard && cached && cached.gateway === fresh.gateway && cached.draft && typeof cached.draft.min === 'string' && typeof cached.draft.max === 'string') {
				setDraft(cached.draft); current.guard = cached.guard; parked = true;
				message('An unsaved draft was restored and parked. Current configuration is shown, but this draft has not been rebased. Copy it if needed, then deliberately discard and load current values.');
			} else message(fresh.reason || 'Current configuration loaded. No settings changed.');
		} catch (e) { onError(e); }
		finally { busy = false; update(); }
	}
	function payload(undo) { return { guard: current.guard, draft: JSON.stringify(draft()), undo: undo ? 'yes' : 'no', review: JSON.stringify({}) }; }
	async function act(undo) {
		if (busy || parked || !current || current.reason || (undo ? dirty || !current.receipt || !current.receipt.undo : !dirty || validation(draft()))) return;
		const target = undo ? current.receipt.before : draft();
		busy = true; update();
		try {
			if (narrower(current.bounds, target)) {
				review = await request('review', payload(undo)); undoReview = undo;
				list('review-changes', review.changes); el('review-summary').textContent = review.summary; el('confirm').textContent = undo ? 'Confirm and undo amount change' : 'Confirm and save limits'; el('review').hidden = false; el('review-heading').focus();
				message('Review the exact restriction. Nothing has been saved yet.');
			} else {
				const fresh = await request(undo ? 'undo' : 'save', payload(undo)); show(fresh); message(undo ? 'Your matching amount change was undone and read back.' : 'Amount limits saved and read back. No module or method was enabled.', true);
			}
		} catch (e) { onError(e); }
		finally { busy = false; update(); }
	}
	el('form').addEventListener('submit', e => { e.preventDefault(); act(false); });
	['min', 'max'].forEach(side => ['mode', 'value'].forEach(part => el(side + '-' + part).addEventListener('input', () => { invalidate(); update(); })));
	el('gateway').addEventListener('change', () => {
		desired = el('gateway').value; invalidate();
		if (current && (dirty || parked)) { el('gateway').value = current.gateway; parked = true; message('Your current method draft is parked. Discard draft and load current values will switch to the selected method. Nothing was saved.', true); update(); }
		else load(true);
	});
	el('load').addEventListener('click', () => load(true));
	el('undo').addEventListener('click', () => act(true));
	el('cancel').addEventListener('click', () => { invalidate(); message('Review cancelled. Draft retained.'); update(); el('save').focus(); });
	el('confirm').addEventListener('click', async () => {
		if (!review || busy || parked || !current) return;
		const undo = undoReview, token = { expires: review.expires, token: review.token, confirm: 'yes' };
		busy = true; update();
		try { const fresh = await request(undo ? 'undo' : 'save', { ...payload(undo), review: JSON.stringify(token) }); show(fresh); message(undo ? 'Your matching amount change was undone and read back.' : 'Amount limits saved and read back. No module or method was enabled.', true); }
		catch (e) { onError(e); }
		finally { busy = false; update(); }
	});
	// A return from native settings invalidates review. A changed context never replaces the draft.
	window.addEventListener('focus', async () => {
		if (!current || busy || parked) return;
		invalidate(); update();
		const prior = current.guard, gateway = current.gateway;
		try {
			const fresh = await request('load', { gateway });
			if (current && current.gateway === gateway && current.guard === prior && fresh.guard !== prior) { parked = true; message('Native settings or another dependency changed. Your draft is parked. Discard and load current values before saving.', true); update(); }
		} catch (e) { onError(e); update(); }
	});
	window.addEventListener('beforeunload', e => { if (dirty || parked) { e.preventDefault(); e.returnValue = ''; } });
	if (!el('gateway').options.length) { message('No supported registered method is available. Use full settings; no configuration was changed.'); el('load').disabled = true; }
	else load();
}());
