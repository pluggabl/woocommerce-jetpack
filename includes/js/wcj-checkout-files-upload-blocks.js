/* Native Checkout SlotFill. No Classic PHP-session or public upload URL. */
(function () {
	'use strict';
	var wc = window.wc || {}, wp = window.wp || {};
	if (!wc.blocksCheckout || !wc.wcBlocksData || !wp.element || !wp.plugins || !wp.data) { return; }
	var api = wc.blocksCheckout, el = wp.element.createElement, useState = wp.element.useState,
		useEffect = wp.element.useEffect, t = wp.i18n.__, ns = 'booster-checkout-upload';
	if (!api.ExperimentalOrderMeta || !api.extensionCartUpdate) { return; }
	function ValidationFeedback() {
		var message = wp.data.useSelect(function (select) {
			var current = select(wc.wcBlocksData.validationStore).getValidationError('wcj-private-upload-confirmation');
			return current && !current.hidden ? current.message : '';
		});
		return message ? el('div', {className: 'wc-block-components-validation-error', role: 'alert'},
			el('p', {id: 'validate-error-wcj-private-upload-confirmation'}, message)) : null;
	}
	function Uploads(props) {
		var info = (props.extensions || {})[ns], state = useState(''), error = state[0], setError = state[1],
			certainState = useState(false), certain = certainState[0], setCertain = certainState[1],
			revokedState = useState(false), revoked = revokedState[0], setRevoked = revokedState[1],
			pending = useState(false), busy = pending[0], setBusy = pending[1],
			uncertain = useState(false), locked = uncertain[0], setLocked = uncertain[1],
			dispatch = wp.data.useDispatch(wc.wcBlocksData.checkoutStore),
			validation = wp.data.useDispatch(wc.wcBlocksData.validationStore),
			processing = wp.data.useSelect(function (select) { return select(wc.wcBlocksData.checkoutStore).isProcessing(); });
		var fields = info && info.fields || [], files = info && info.files || [],
			orphaned = files.filter(function (file) { return !fields.some(function (field) { return field.id === file.slot; }); });
		useEffect(function () {
			if (info && info.attempt && dispatch.setExtensionData) {
				dispatch.setExtensionData(ns, {revision: info.revision, attempt: info.attempt});
			} else if (info && info.state === 'revoked' && dispatch.setExtensionData) {
				dispatch.setExtensionData(ns, {}, true);
			}
		}, [info && info.revision, info && info.attempt, info && info.state]);
		useEffect(function () {
			var id = 'wcj-private-upload-confirmation';
			var requiredMissing = info && (info.fields || []).some(function (field) {
				return field.required && (!info.available || !(info.files || []).some(function (file) { return file.slot === field.id; }));
			});
			if (busy || (error && !certain) || requiredMissing || orphaned.length || (info && info.state !== 'staged' && info.state !== 'revoked' && (info.state !== 'cart-changed' || files.length || info.order_id) && (fields.length || files.length || info.order_id))) {
				validation.setValidationErrors({'wcj-private-upload-confirmation': {message: error || t('Confirm checkout files before placing the order.', 'woocommerce-jetpack'), hidden: false}});
			} else { validation.clearValidationError(id); }
			return function () { validation.clearValidationError(id); };
		}, [busy, error, certain, info && info.state, info && info.available, info && info.files, info && info.fields, info && info.order_id]);
		if (!info || (!fields.length && !files.length && info.state !== 'cart-changed' && info.state !== 'claimed-unknown') || props.context !== 'woocommerce/checkout') { return null; }
		function send(data) {
			setBusy(true); setError(''); setCertain(false);
			return api.extensionCartUpdate({namespace: ns, data: data}).catch(function (failure) {
				if (failure && (failure.code === 'wcj_upload_capacity' || failure.code === 'wcj_upload_revoked')) {
					setCertain(true);
					if (failure.code === 'wcj_upload_revoked') { setRevoked(true); if (dispatch.setExtensionData) { dispatch.setExtensionData(ns, {}, true); } }
					setError(t(failure.code === 'wcj_upload_revoked' ? 'These provisional files are no longer retained. Choose and upload the files again if needed.' : 'The store cannot accept uploads right now. Contact the store.', 'woocommerce-jetpack'));
					return false;
				}
				setLocked(true);
				setError(t('Upload outcome is uncertain. Reload the cart to reconcile its current files before another action. Do not repeat an uncertain checkout.', 'woocommerce-jetpack'));
				return false;
			}).finally(function () { setBusy(false); });
		}
		function upload(field, input) {
			var file = input.files && input.files[0];
			if (!file || busy || locked || processing) { return; }
			if (file.size > info.max_size) { setError(t('This file exceeds the store upload limit.', 'woocommerce-jetpack')); return; }
			setBusy(true);
			var reader = new FileReader();
			reader.onerror = function () { setBusy(false); setError(t('The file could not be read. Choose the file again.', 'woocommerce-jetpack')); };
			reader.onload = function () {
				var content = String(reader.result), comma = content.indexOf(',');
				if (comma < 0) { reader.onerror(); return; }
				function transfer(proof) {
					if (!proof || proof.state !== 'staged' || !/^[a-f0-9]{32}$/.test(proof.attempt) || !Number.isInteger(proof.revision) || proof.revision < 0) {
						setBusy(false); setLocked(true); setError(t('The new upload attempt could not be confirmed. Reload the cart before another action.', 'woocommerce-jetpack')); return;
					}
					return send({operation: 'upload', revision: proof.revision, attempt: proof.attempt, slot: field.id, name: file.name, content: content.slice(comma + 1)});
				}
				if (!revoked && info.attempt && info.state === 'staged') { transfer(info); } else {
					send({operation: 'begin', revision: 0, slot: 0}).then(function (cart) {
						if (cart !== false) { transfer(cart && cart.extensions && cart.extensions[ns]); }
					});
				}
			};
			reader.readAsDataURL(file);
		}
		return el('section', {'aria-label': t('Checkout files', 'woocommerce-jetpack')},
			(!info.available || info.upload_available === false) && el('p', {role: 'alert'}, info.error),
			error && el('p', {id: 'validate-error-wcj-private-upload-confirmation', role: 'alert'}, error),
			!error && el(api.ValidationInputError || ValidationFeedback, {propertyName: 'wcj-private-upload-confirmation', elementId: 'wcj-private-upload-confirmation'}),
			info.state === 'revoked' && el('p', {role: 'status'}, t('Provisional files are no longer retained. Choose and upload the files again if needed.', 'woocommerce-jetpack')),
			orphaned.length > 0 && el('p', {role: 'status'}, t('Retained files no longer have an available upload field. Clear all staged files explicitly before continuing.', 'woocommerce-jetpack')),
			orphaned.map(function (file) { return el('p', {key: file.slot}, file.name); }),
			(info.state === 'cart-changed' || (info.state === 'staged' && orphaned.length > 0)) && el('button', {type: 'button', disabled: busy || locked || processing || !info.available, onClick: function () { send({operation: 'reset', revision: info.revision, attempt: info.attempt, slot: 0}); }}, t('Clear all staged files', 'woocommerce-jetpack')),
			info.state === 'claimed-unknown' && el('p', {role: 'status'}, t('These files are claimed by an existing order. They are preserved while its outcome is reconciled and cannot be replaced or rebound.', 'woocommerce-jetpack')),
			info.state === 'claimed-unknown' && el('button', {type: 'button', disabled: busy || locked || processing, onClick: function () { if (window.confirm(t('Start files for a separate new purchase? Existing order files stay retained. This does not retry or cancel the previous order.', 'woocommerce-jetpack'))) { send({operation: 'new-purchase', revision: info.revision, slot: 0, attempt: info.attempt, acknowledged_order: info.order_id}); } }}, t('Start files for a separate purchase', 'woocommerce-jetpack')),
			fields.map(function (field) {
				var file = (info.files || []).filter(function (value) { return value.slot === field.id; })[0],
					disabled = busy || processing || locked || !info.available || info.upload_available === false || (info.state !== 'staged' && info.state !== 'revoked'), id = 'wcj-native-upload-' + field.id, missing = field.required && (!info.available || !file);
				return el('div', {key: field.id, className: missing ? 'has-error' : undefined},
					el('label', {htmlFor: id}, field.label, field.required ? ' *' : ''),
					el('input', {id: id, type: 'file', accept: field.accept || undefined, disabled: disabled,
						'aria-required': !!field.required, 'aria-invalid': !!missing,
						'aria-errormessage': missing ? 'validate-error-wcj-private-upload-confirmation' : undefined, onChange: function (event) { upload(field, event.target); }}),
					file && el('p', {}, file.name, ' ', el('button', {type: 'button', disabled: busy || processing || locked || !info.available || info.state !== 'staged',
						onClick: function () { send({operation: 'remove', revision: info.revision, attempt: info.attempt, slot: field.id}); }}, t('Remove file', 'woocommerce-jetpack'))));
			}), busy && el('p', {role: 'status'}, t('Confirming private upload...', 'woocommerce-jetpack')));
	}
	wp.plugins.registerPlugin('booster-checkout-upload', {render: function () {
		return el(api.ExperimentalOrderMeta, {}, el(Uploads, {}));
	}, scope: 'woocommerce-checkout'});
}());
