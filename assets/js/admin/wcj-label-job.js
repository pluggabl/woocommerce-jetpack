(() => {
	'use strict';
	const root = document.getElementById('wcj-label-job');
	if (!root) return;
	const input = root.querySelector('#wcj-label-value'), clear = root.querySelector('#wcj-label-clear'), preview = root.querySelector('#wcj-label-preview');
	const forms = [...root.querySelectorAll('form[method="post"]')], local = new Map();
	const mark = form => JSON.stringify([...new FormData(form)].filter(([k]) => k === 'label_value' || k === 'confirm_gateway'));
	forms.forEach(form => local.set(form, mark(form)));
	function paint() {
		if (!input || !preview) return;
		if (root.dataset.task === 'price_labels') {
			const price = root.dataset.price;
			preview.textContent = price ? (root.dataset.before === 'yes' ? input.value + price : price + input.value) : (input.value || 'No text added');
		} else preview.textContent = input.value || root.dataset.default || 'Inherited product wording';
	}
	if (clear && input) {
		clear.hidden = false;
		clear.addEventListener('click', () => { input.value = ''; paint(); input.focus(); });
		input.addEventListener('input', paint); paint();
	}
	root.addEventListener('click', e => {
		if (e.target.closest('a[href="#wcj-label-gateway"]')) {
			const gateway = root.querySelector('#wcj-label-gateway');
			if (gateway) gateway.open = true;
		}
	});
	let leaving = false;
	window.addEventListener('beforeunload', e => {
		if (!leaving && forms.some(f => local.get(f) !== mark(f))) { e.preventDefault(); e.returnValue = ''; }
	});
	root.addEventListener('submit', e => {
		const other = forms.some(f => f !== e.target && local.get(f) !== mark(f));
		if (other && !window.confirm('Leave the other unsaved label change behind?')) { e.preventDefault(); return; }
		leaving = true;
	});
})();
