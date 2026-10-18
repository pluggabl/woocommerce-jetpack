/* Static text input profile only. Existing Woo gateways own availability and payment processing. */
( function () {
	'use strict';
	const wc = window.wc;
	const wp = window.wp;
	if ( ! wc || ! wp || ! wc.wcBlocksRegistry || ! wc.wcSettings || ! wp.element || ! wc.sanitize ) {
		return;
	}
	const { createElement, RawHTML, useState, useRef, useEffect } = wp.element;
	const { decodeEntities } = wp.htmlEntities;
	const { __ } = wp.i18n;
	const methods = wc.wcSettings.getSetting( 'paymentMethodData', {} );
	Object.keys( methods ).forEach( function ( id ) {
		const data = methods[ id ];
		if ( ! /^jetpack_custom_gateway(_\d+)?$/.test( id ) || ! data || data.profile !== 1 || ! Array.isArray( data.fields ) || ! Array.isArray( data.segments ) || data.segments.length !== data.fields.length + 1 || typeof data.hash !== 'string' ) {
			return;
		}
		const title = decodeEntities( data.title );
		function markup( values, onChange, preview ) {
			const children = [];
			data.segments.forEach( function ( segment, index ) {
				children.push( createElement( RawHTML, { key: 'segment-' + index }, wc.sanitize.sanitizeHTML( segment ) ) );
				const field = data.fields[ index ];
				if ( ! field ) {
					return;
				}
				const inputId = id + '-wcj-field-' + index;
				children.push( createElement( 'div', { key: 'field-' + index },
					createElement( 'label', { htmlFor: inputId }, field.label || field.name ),
					createElement( 'input', {
						id: inputId,
						type: 'text',
						className: field.className,
						placeholder: field.placeholder,
						value: values[ index ],
						maxLength: 512,
						required: field.required,
						'aria-required': field.required,
						disabled: preview,
						onChange: preview ? undefined : function ( event ) { onChange( index, event.target.value ); }
					} )
				) );
			} );
			return createElement( 'div', {}, children );
		}
		function Content( props ) {
			const [ values, setValues ] = useState( function () { return data.fields.map( function ( field ) { return field.default; } ); } );
			const valuesRef = useRef( values );
			const onPaymentSetup = props.eventRegistration.onPaymentSetup;
			const responseTypes = props.emitResponse.responseTypes;
			valuesRef.current = values;
			useEffect( function () {
				return onPaymentSetup( function () {
					const current = valuesRef.current;
					if ( data.fields.some( function ( field, index ) { return field.required && current[ index ].trim() === ''; } ) ) {
						return { type: responseTypes.ERROR, message: __( 'Please complete the required payment fields.', 'woocommerce-jetpack' ) };
					}
					return {
						type: responseTypes.SUCCESS,
						meta: { paymentMethodData: { wcj_custom_gateway_fields: JSON.stringify( [ 1, id, data.hash, data.fields.map( function ( field, index ) { return [ field.name, current[ index ] ]; } ) ] ) } }
					};
				} );
			}, [ onPaymentSetup, responseTypes ] );
			return markup( values, function ( index, value ) {
				const next = valuesRef.current.slice();
				next[ index ] = value;
				valuesRef.current = next;
				setValues( next );
			}, false );
		}
		function Preview() {
			return markup( data.fields.map( function ( field ) { return field.default; } ), null, true );
		}
		function Label( props ) {
			return createElement( props.components.PaymentMethodLabel, { text: title } );
		}
		wc.wcBlocksRegistry.registerPaymentMethod( {
			name: id,
			label: createElement( Label ),
			ariaLabel: title,
			content: createElement( Content ),
			edit: createElement( Preview ),
			canMakePayment: function () { return true; },
			supports: { features: [ 'products' ] }
		} );
	} );
}() );
