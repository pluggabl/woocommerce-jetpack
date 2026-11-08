/* Native-backed product question. The sample is illustrative and never writes customer data. */
(function () {
	'use strict';
	const config = window.wcjProductQuestion;
	if (!config) return;
	const $ = (id) => document.getElementById('question-' + id);
	const __ = (s) => wp.i18n.__(s, 'woocommerce-jetpack');
	let state = null, busy = false, stale = false, pauseReason = '';
	const message = (text) => { $('message').textContent = text; };
	const value = () => ({title: $('title').value, type: $('type').value, placeholder: $('hint').value, required: $('required').checked, maxlength: Number($('maxlength').value), module: $('module').checked, local: $('local').checked, ack: $('ack').checked});
	const shape = (d) => JSON.stringify([d.title, d.type, d.placeholder, !!d.required, Number(d.maxlength)]);
	const dirty = () => !!state && (shape(value()) !== shape(state.draft) || $('module').checked || $('local').checked);
	async function api(op, data = {}) {
		const body = new URLSearchParams({action: 'wcj_product_question_' + op, nonce: config.nonces[op], ...data});
		const response = await fetch(config.url, {method: 'POST', credentials: 'same-origin', body});
		const json = await response.json();
		if (!json.success) { const error = new Error(json.data && json.data.message ? json.data.message : __('The request was not completed. Reload current before retrying.')); error.kind = json.data && json.data.kind ? json.data.kind : 'uncertain'; throw error; }
		return json.data;
	}
	function sample() {
		$('sample-label').textContent = $('title').value || __('Your question');
		let field = $('sample');
		if ((field.tagName === 'TEXTAREA') !== ($('type').value === 'textarea')) {
			field = document.createElement($('type').value === 'textarea' ? 'textarea' : 'input');
			field.id = 'question-sample';
			if (field.tagName === 'INPUT') field.type = 'text';
			$('sample-host').replaceChildren(field);
		}
		field.placeholder = $('hint').value;
		if (Number($('maxlength').value) > 0) field.maxLength = Number($('maxlength').value); else field.removeAttribute('maxlength');
	}
	function refresh() {
		$('product').disabled = busy;
		if (!state) return;
		const changed = dirty(), activation = $('module').checked || $('local').checked;
		$('ack-row').hidden = !activation;
		$('draft').hidden = !changed;
		$('save').hidden = !changed;
		$('discard').hidden = !changed;
		$('save').disabled = busy || stale || !!state.reason || !changed || !$('title').value.trim() || (activation && !$('ack').checked);
		$('save').textContent = activation ? __('Save and turn on') : __('Save question');
		const blocked = busy ? __('Please wait for the current request to finish.') : stale ? pauseReason : changed && !$('title').value.trim() ? __('Enter a question before saving.') : changed && activation && !$('ack').checked ? __('Review the wider activation effects above, then select the acknowledgement before saving.') : '';
		$('save-reason').textContent = blocked;
		$('save-reason').hidden = !blocked;
		$('undo').disabled = busy || stale || changed;
		$('receipt').hidden = !state.undo || changed;
		$('product').disabled = busy;
		$('reload').disabled = busy;
		$('discard').disabled = busy;
		for (const id of ['title', 'type', 'hint', 'required', 'maxlength', 'module', 'local', 'ack']) $(id).disabled = busy;
		sample();
	}
	function paint(next) {
		state = next; stale = false; pauseReason = '';
		$('card').hidden = false;
		$('product-title').textContent = next.title;
		$('state').textContent = [__('Product Input Fields:') + ' ' + (next.module ? __('On') : __('Off')), __('Per-product questions:') + ' ' + (next.local ? __('On') : __('Off')), next.fields.length + ' ' + __('existing field(s)'), next.reason ? __('Full editor needed') : (next.edit ? __('Editing the question created here') : __('Next native field slot:') + ' ' + next.slot)].join(' | ');
		$('reason').textContent = next.reason;
		$('display').textContent = __('Global questions:') + ' ' + (next.global ? __('On') : __('Off')) + '. ' + __('Module-wide position:') + ' ' + next.display.position + '. ' + __('Cart display setting:') + ' ' + next.display.cart;
		$('form').hidden = !!next.reason;
		$('fields').replaceChildren();
		$('changes').replaceChildren();
		for (const text of next.changes) { const li = document.createElement('li'); li.textContent = text; $('changes').append(li); }
		for (const f of next.fields) { const li = document.createElement('li'); li.textContent = f.slot + ': ' + (f.title || __('No label')) + ' (' + f.type + ', ' + (f.enabled ? __('enabled') : __('disabled')) + ')'; $('fields').append(li); }
		if (!next.fields.length) { const li = document.createElement('li'); li.textContent = __('No aggregate fields listed. Legacy/custom states, if present, are explained above.'); $('fields').append(li); }
		$('title').value = next.draft.title; $('type').value = next.draft.type; $('hint').value = next.draft.placeholder; $('required').checked = next.draft.required; $('maxlength').value = next.draft.maxlength;
		for (const id of ['module', 'local', 'ack']) $(id).checked = false;
		$('module-row').hidden = next.module; $('module-reach').hidden = next.module;
		$('local-row').hidden = next.local; $('local-reach').hidden = next.local;
		$('activation').hidden = next.module && next.local;
		$('native').href = next.native; $('shopper').href = next.shopper;
		$('next').textContent = !next.module || !next.local ? __('Saved configuration will not show this per-product question until both the module and per-product questions are on. Check the wider reach before enabling them.') : __('Open this product in a private window, type a test message, add to cart and check that cart line. Do not place an order. Theme, Blocks and cart display may differ.');
		refresh();
	}
	$('search').addEventListener('submit', async (event) => {
		event.preventDefault(); if (busy) return;
		if (dirty() && !window.confirm(__('Discard this unsaved draft before searching for another product?'))) return;
		busy = true; refresh();
		try {
			const rows = await api('search', {q: $('query').value});
			state = null; stale = false; $('card').hidden = true;
			$('product').replaceChildren(new Option(__('Choose a product'), ''));
			for (const row of rows) $('product').add(new Option(row.title + ' (#' + row.id + ')', row.id));
			message(rows.length ? __('Choose a product from the results.') : __('No editable products found.'));
		} catch (error) { message(error.message); } finally { busy = false; $('product').disabled = false; refresh(); }
	});
	$('product').addEventListener('change', async () => {
		if (busy) return;
		const id = $('product').value;
		if (dirty() && !window.confirm(__('Discard the unsaved draft before changing product?'))) { $('product').value = state.product; return; }
		if (!id) { state = null; $('card').hidden = true; return; }
		busy = true; refresh();
		try { paint(await api('load', {product: id})); message(''); } catch (error) { message(error.message); if (state) $('product').value = state.product; } finally { busy = false; refresh(); }
	});
	$('form').addEventListener('input', (event) => { if (event.target.id !== 'question-ack' && event.target.id !== 'question-sample') $('ack').checked = false; refresh(); });
	$('form').addEventListener('submit', async (event) => {
		event.preventDefault(); if (!state || $('save').disabled || busy) return;
		const draft = value(); busy = true; refresh();
		try { paint(await api('save', {product: state.product, token: state.token, draft: JSON.stringify(draft)})); message(__('Saved and read back.')); }
		catch (error) { stale = error.kind !== 'validation'; pauseReason = stale ? error.message + ' ' + __('Save is paused and your draft is kept. Copy any draft details you need before choosing Discard draft. That loads saved values so you can compare and re-enter your intended change.') : ''; $('ack').checked = false; message(error.message); }
		finally { busy = false; refresh(); }
	});
	$('undo').addEventListener('click', async () => {
		if (!state || busy || dirty() || stale) return;
		busy = true; refresh();
		try { paint(await api('undo', {product: state.product, token: state.token})); message(__('Undone and read back. Only the matching change was restored.')); }
		catch (error) { stale = true; pauseReason = error.message + ' ' + __('Reload current to check the saved settings before making another change.'); message(error.message); }
		finally { busy = false; refresh(); }
	});
	$('reload').addEventListener('click', async () => {
		if (!state || busy) return;
		busy = true; refresh();
		try {
			const next = await api('load', {product: state.product});
			if (dirty()) { stale = true; pauseReason = __('Save is paused: this draft was kept when current settings were checked, not updated to those settings. Copy any draft details you need before choosing Discard draft. That loads saved values so you can compare and re-enter your intended change.'); $('ack').checked = false; message(__('Current settings checked. Your draft is kept, not rebased. Discard draft to load the current values before starting another change.')); }
			else { paint(next); message(__('Current saved settings loaded.')); }
		} catch (error) { message(error.message); } finally { busy = false; refresh(); }
	});
	$('discard').addEventListener('click', async () => {
		if (!state || busy || !window.confirm(__('Discard this draft and load current saved settings?'))) return;
		busy = true; refresh();
		try { paint(await api('load', {product: state.product})); message(__('Draft discarded.')); } catch (error) { message(error.message); } finally { busy = false; refresh(); }
	});
	window.addEventListener('beforeunload', (event) => { if (dirty() || busy) { event.preventDefault(); event.returnValue = ''; } });
})();
