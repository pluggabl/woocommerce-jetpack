(() => {
	'use strict';
	const root = document.getElementById('wcj-catalog-job');
	if (!root) return;
	const form = root.querySelector('#wcj-catalog-edit'), input = root.querySelector('#wcj-catalog-value');
	if (!form || !input) return;
	const sampleHeading = root.querySelector('#wcj-catalog-preview-heading');
	if (sampleHeading) { sampleHeading.focus({ preventScroll: true }); sampleHeading.scrollIntoView({ block: 'start' }); }
	const note = root.querySelector('#wcj-catalog-draft'), discard = root.querySelector('#wcj-catalog-discard');
	if (root.dataset.error === 'yes') { note.hidden = false; note.textContent = 'This attempted edit is kept in the disabled form. Reload current native values using the link above. No fresh guard is granted to this draft automatically.'; return; }
	const sample = root.querySelector('#wcj-catalog-sample');
	const syncUndoSample = () => {
		if (sample) root.querySelectorAll('.wcj-catalog-undo input[name="sample_product"]').forEach(hidden => { hidden.value = sample.value; });
	};
	if (sample) sample.addEventListener('change', syncUndoSample);
	syncUndoSample();
	const key = 'wcj-catalog-draft:' + root.dataset.user + ':' + root.dataset.edition + ':' + root.dataset.module + ':' + root.dataset.field;
	const read = () => input.multiple ? [...input.selectedOptions].map(o => o.value) : input.value;
	const write = value => {
		if (input.multiple && Array.isArray(value)) [...input.options].forEach(o => { o.selected = value.map(String).includes(o.value); });
		else if (!input.multiple && (typeof value === 'string' || typeof value === 'number')) input.value = String(value);
	};
	let native;
	try { native = JSON.parse(root.dataset.native); } catch (_) { native = input.multiple ? [] : ''; }
	const textForComparison = value => {
		const text = String(value ?? '');
		return input.tagName === 'TEXTAREA' ? text.replace(/\r\n?/g, '\n') : text;
	};
	const normalized = value => JSON.stringify(Array.isArray(value) ? value.map(String) : textForComparison(value));
	const dirty = () => normalized(read()) !== normalized(native);
	let leaving = false, parked = false, storageAvailable = true;
	const confirmation = form.querySelector('#wcj-catalog-confirm');
	const confirmButton = form.querySelector('#wcj-catalog-confirm-save');
	const confirmHint = form.querySelector('#wcj-catalog-confirm-hint');
	const token = form.querySelector('input[name="preview_token"]');
	let previewValue;
	try { previewValue = normalized(JSON.parse(form.dataset.previewValue)); } catch (_) { previewValue = null; }
	const sampleValue = () => sample && sample.value ? sample.value : '0';
	const previewSample = form.dataset.previewSample;
	let previewValid = !!(confirmation && confirmButton && token && token.value && previewValue !== null && previewSample !== undefined);
	// A fresh response always needs a fresh acknowledgement, including browser-restored controls.
	if (confirmation) confirmation.checked = false;
	function paintConfirmation() {
		if (!confirmation || !confirmButton) return;
		const ready = previewValid && !parked && !input.disabled && confirmation.checked;
		confirmButton.disabled = !ready;
		confirmHint.textContent = !previewValid ? 'This choice or sample changed. Preview it again before confirming.' : (ready ? 'Ready to confirm this previewed choice.' : 'Read the sample and check the box to confirm.');
	}
	function invalidatePreview() {
		if (!confirmation) return;
		previewValid = false; confirmation.checked = false;
		if (token) token.value = '';
		paintConfirmation();
	}
	function checkPreviewContext() {
		if (normalized(read()) !== previewValue || sampleValue() !== previewSample) invalidatePreview();
		else paintConfirmation();
	}
	if (confirmation) confirmation.addEventListener('change', paintConfirmation);
	if (sample) sample.addEventListener('change', checkPreviewContext);
	window.addEventListener('pageshow', e => { if (e.persisted) invalidatePreview(); });
	function remove() { try { sessionStorage.removeItem(key); } catch (_) { storageAvailable = false; } }
	function paint() {
		discard.hidden = !dirty() && !parked;
		if (!parked) {
			note.hidden = !dirty();
			note.textContent = dirty() ? 'Unsaved edit in this tab. Preview does not save it. Full settings and guides open in new tabs.' : '';
			if (!storageAvailable && dirty()) note.textContent += ' Browser draft storage is unavailable; keep this tab open.';
		}
		paintConfirmation();
	}
	function persist() {
		if (parked) return;
		checkPreviewContext();
		try {
			if (dirty()) sessionStorage.setItem(key, JSON.stringify({ guard: root.dataset.guard, value: read() }));
			else sessionStorage.removeItem(key);
		} catch (_) { storageAvailable = false; }
		paint();
	}
	if (root.dataset.saved === 'yes') remove();
	else if (root.dataset.response !== 'yes') {
		try {
			const draft = JSON.parse(sessionStorage.getItem(key) || 'null');
			if (draft && draft.guard === root.dataset.guard) {
				write(draft.value);
				if (!dirty()) remove();
			} else if (draft) {
				parked = true;
				note.hidden = false;
				note.textContent = 'Native settings changed since this unsaved draft. Your kept draft is: ' + normalized(draft.value) + '. The editor shows the current saved value. Discard the parked draft before starting a fresh edit; it is not automatically rebased or saved.';
				input.disabled = true;
				form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(b => { b.disabled = true; });
			}
		} catch (_) { storageAvailable = false; }
	}
	discard.addEventListener('click', () => {
		invalidatePreview();
		remove(); parked = false; write(native); input.disabled = false;
		form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(b => { b.disabled = false; });
		paint(); input.focus();
	});
	input.addEventListener('input', persist); input.addEventListener('change', persist); checkPreviewContext(); paint();
	window.addEventListener('beforeunload', e => {
		if (!leaving && dirty()) { e.preventDefault(); e.returnValue = ''; }
	});
	root.addEventListener('submit', e => {
		if (e.target.classList.contains('wcj-catalog-undo')) syncUndoSample();
		const isOwn = e.target === form;
		if (isOwn && confirmation && (!e.submitter || e.submitter === confirmButton)) {
			checkPreviewContext();
			if (confirmButton.disabled) { e.preventDefault(); confirmation.focus(); return; }
		}
		if (!isOwn && dirty() && !window.confirm('Discard this unsaved edit and open the other setting or action? Choose Cancel to stay.')) { e.preventDefault(); return; }
		if (!isOwn && dirty()) remove();
		if (isOwn) persist();
		leaving = true;
	});
})();