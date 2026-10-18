<?php
/**
 * Exact quantities and fresh, read-only WooCommerce facts for Returns.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Fixed six-decimal quantities. Never rounds or calculates money. */
class WCJ_Returns_Quantity {
	/** Maximum scaled quantity, requiring 64-bit PHP. */
	const MAX = 1000000000000;

	/**
	 * Parse an exact nonnegative decimal into integer millionths.
	 *
	 * @param mixed $value Decimal value.
	 * @return int
	 * @throws RuntimeException Invalid or unsupported quantity.
	 */
	public static function parse( $value ) {
		if ( PHP_INT_SIZE < 8 || ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) ) {
			throw new RuntimeException( 'quantity_invalid' );
		}
		if ( is_float( $value ) ) {
			if ( ! is_finite( $value ) || $value < 0 || $value > 1000000 ) {
				throw new RuntimeException( 'quantity_invalid' );
			}
			// Native Woo quantities can be floats. Fixed-format only if converting
			// back gives the exact same native value; never round finer precision.
			$decimal = sprintf( '%.6F', 0.0 === $value ? 0.0 : $value );
			if ( (float) $decimal !== $value ) {
				throw new RuntimeException( 'quantity_invalid' );
			}
			$value = rtrim( rtrim( $decimal, '0' ), '.' );
		}
		$value = (string) $value;
		if ( ! preg_match( '/^(0|[1-9][0-9]{0,6})(?:\.([0-9]{1,6}))?$/D', $value, $match ) ) {
			throw new RuntimeException( 'quantity_invalid' );
		}
		$units = (int) $match[1] * 1000000 + (int) str_pad( $match[2] ?? '', 6, '0' );
		if ( $units > self::MAX ) {
			throw new RuntimeException( 'quantity_invalid' );
		}
		return $units;
	}

	/**
	 * Format integer millionths without floating point.
	 *
	 * @param int $units Scaled quantity.
	 * @return string
	 */
	public static function display( $units ) {
		$fraction = rtrim( str_pad( (string) ( $units % 1000000 ), 6, '0', STR_PAD_LEFT ), '0' );
		return (string) intdiv( $units, 1000000 ) . ( '' === $fraction ? '' : '.' . $fraction );
	}
}

/** Woo order data is read only. No order save/refund/stock API is called. */
class WCJ_Returns_Facts {
	/** Maximum absolute scaled amount for comparison only, never payment math. */
	const AMOUNT_MAX = 999999999999999999;

	/**
	 * Read a signed persisted amount exactly, with at most six decimal places.
	 * Larger/finer amounts fail closed, not rounded. Native floats must round-trip.
	 *
	 * @param mixed $value Native stored amount.
	 * @return int Signed millionths for allocation classification only.
	 */
	private function amount( $value ) {
		if ( is_float( $value ) ) {
			if ( ! is_finite( $value ) || abs( $value ) >= 1000000000000 ) {
				throw new RuntimeException( 'refund_invalid' );
			}
			$decimal = sprintf( '%.6F', 0.0 === $value ? 0.0 : $value );
			if ( (float) $decimal !== $value ) {
				throw new RuntimeException( 'refund_invalid' );
			}
			$value = rtrim( rtrim( $decimal, '0' ), '.' );
		}
		if ( PHP_INT_SIZE < 8 || ( ! is_string( $value ) && ! is_int( $value ) ) || ! preg_match( '/^(-?)(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,6}))?$/D', (string) $value, $match ) ) {
			throw new RuntimeException( 'refund_invalid' );
		}
		$units = (int) $match[2] * 1000000 + (int) str_pad( $match[3] ?? '', 6, '0' );
		return '-' === $match[1] ? -$units : $units;
	}

	/**
	 * Bound signed additions before performing them, so PHP never falls to float.
	 *
	 * @param int $left Existing allocation.
	 * @param int $right Next signed component.
	 * @return int Exact bounded sum.
	 */
	private function add_amount( $left, $right ) {
		if ( ( $right > 0 && $left > self::AMOUNT_MAX - $right ) || ( $right < 0 && $left < -self::AMOUNT_MAX - $right ) ) {
			throw new RuntimeException( 'refund_invalid' );
		}
		return $left + $right;
	}
	/**
	 * Load a new order object without factory-object reuse.
	 *
	 * Persistent and HPOS datastore caches are deliberately unsupported in this
	 * first slice until a native, supported cache-freshness path is qualified.
	 * No possibly stale authorization fallback is permitted.
	 *
	 * @param int  $id Order or refund ID.
	 * @param bool $refund Whether this is a refund.
	 * @return WC_Abstract_Order
	 * @throws RuntimeException Unsupported or missing authoritative data.
	 */
	private function fresh_order( $id, $refund = false ) {
		if ( wp_using_ext_object_cache() ) {
			throw new RuntimeException( 'fresh_cache_unavailable' );
		}
		$util = '\Automattic\WooCommerce\Utilities\OrderUtil';
		if ( class_exists( $util ) && method_exists( $util, 'custom_orders_table_datastore_cache_enabled' ) && $util::custom_orders_table_datastore_cache_enabled() ) {
			throw new RuntimeException( 'fresh_cache_unavailable' );
		}
		// These are scoped, request-local invalidations, not a storewide flush.
		wp_cache_delete( $id, 'posts' );
		wp_cache_delete( $id, 'post_meta' );
		wp_cache_delete( 'order-items-' . $id, 'orders' );
		// Object get_type() is class identity, not the persisted record type.
		if ( ! class_exists( $util ) || ! method_exists( $util, 'get_order_type' ) || $util::get_order_type( $id ) !== ( $refund ? 'shop_order_refund' : 'shop_order' ) ) {
			throw new RuntimeException( 'order_unavailable' );
		}
		$order = $refund ? new WC_Order_Refund( $id ) : new WC_Order( $id );
		if ( (int) $order->get_id() !== $id || $order->get_type() !== ( $refund ? 'shop_order_refund' : 'shop_order' ) ) {
			throw new RuntimeException( 'order_unavailable' );
		}
		return $order;
	}

	/**
	 * Re-read every product item's persisted properties and raw metadata.
	 *
	 * @param WC_Abstract_Order $order Current order.
	 * @param bool              $refund Include shipping/fee facts for allocation classification.
	 * @return array
	 * @throws RuntimeException Unsupported or excessive facts.
	 */
	private function items( $order, $refund = false ) {
		$items = $order->get_items( $refund ? array( 'line_item', 'shipping', 'fee' ) : 'line_item' );
		if ( count( $items ) > 200 ) {
			throw new RuntimeException( 'facts_limit' );
		}
		$out = array();
		foreach ( $items as $id => $unused ) {
			wp_cache_delete( $id, 'order_item_meta' );
			wp_cache_delete( 'item-' . $id, 'order-items' );
			$type = $unused->get_type();
			if ( ! in_array( $type, $refund ? array( 'line_item', 'shipping', 'fee' ) : array( 'line_item' ), true ) ) {
				throw new RuntimeException( 'item_unavailable' );
			}
			$class = array(
				'line_item' => 'WC_Order_Item_Product',
				'shipping'  => 'WC_Order_Item_Shipping',
				'fee'       => 'WC_Order_Item_Fee',
			)[ $type ];
			$item  = new $class( $id );
			$item->read_meta_data( true );
			if ( (int) $item->get_order_id() !== (int) $order->get_id() || (int) $item->get_id() !== (int) $id ) {
				throw new RuntimeException( 'item_unavailable' );
			}
			$out[ (int) $id ] = $item;
		}
		ksort( $out, SORT_NUMERIC );
		return $out;
	}

	/**
	 * Read all relevant order/item/refund facts with explicit iteration bounds.
	 *
	 * The native get_items query can fetch more rows before the count guard;
	 * this is not represented as a SQL LIMIT or native performance guarantee.
	 *
	 * @param int $id Order ID.
	 * @return array Private factual snapshot.
	 * @throws RuntimeException Invalid, stale or unbounded facts.
	 */
	public function load( $id ) {
		$order      = $this->fresh_order( $id );
		$completed  = $order->get_date_completed( 'edit' );
		$capacities = array();
		$names      = array();
		$rows       = array();
		foreach ( $this->items( $order ) as $item_id => $item ) {
			$quantity               = WCJ_Returns_Quantity::parse( $item->get_quantity( 'edit' ) );
			$capacities[ $item_id ] = $quantity;
			$names[ $item_id ]      = (string) $item->get_name( 'edit' );
			$rows[ $item_id ]       = array( $item->get_product_id( 'edit' ), $item->get_variation_id( 'edit' ), $quantity, $item->get_subtotal( 'edit' ), $item->get_total( 'edit' ), $item->get_taxes( 'edit' ) );
		}
		$refund_ids = wc_get_orders(
			array(
				'type'          => 'shop_order_refund',
				'parent'        => $id,
				'limit'         => 101,
				'return'        => 'ids',
				'orderby'       => 'ID',
				'order'         => 'ASC',
				'cache_results' => false,
			)
		);
		if ( ! is_array( $refund_ids ) || count( $refund_ids ) > 100 ) {
			throw new RuntimeException( 'facts_limit' );
		}
		sort( $refund_ids, SORT_NUMERIC );
		$refund_rows = array();
		$line_count  = 0;
		$amount_only = false;
		foreach ( $refund_ids as $refund_id ) {
			$refund = $this->fresh_order( (int) $refund_id, true );
			if ( (int) $refund->get_parent_id( 'edit' ) !== $id ) {
				throw new RuntimeException( 'refund_invalid' );
			}
			$lines         = array();
			$allocated     = 0;
			$refund_amount = $this->amount( $refund->get_amount( 'edit' ) );
			if ( $refund_amount < 0 ) {
				throw new RuntimeException( 'refund_invalid' );
			}
			foreach ( $this->items( $refund, true ) as $line_id => $line ) {
				if ( ++$line_count > 200 ) {
					throw new RuntimeException( 'facts_limit' );
				}
				$parent = (int) $line->get_meta( '_refunded_item_id', true, 'edit' );
				$type   = $line->get_type();
				$total  = $this->amount( $line->get_total( 'edit' ) );
				$taxes  = $line->get_taxes( 'edit' );
				$taxes  = isset( $taxes['total'] ) && is_array( $taxes['total'] ) ? $taxes['total'] : array();
				if ( count( $taxes ) > 200 ) {
					throw new RuntimeException( 'facts_limit' );
				}
				$line_amount = $total;
				foreach ( $taxes as &$tax ) {
					$tax         = $this->amount( $tax );
					$line_amount = $this->add_amount( $line_amount, $tax );
					if ( $tax > 0 ) {
						$amount_only = true;
					}
				}
				unset( $tax );
				ksort( $taxes );
				$allocated = $this->add_amount( $allocated, -$line_amount );
				if ( 'line_item' !== $type || $total > 0 ) {
					$amount_only       = true;
					$lines[ $line_id ] = array( $type, $parent, 0, $total, $taxes );
					continue;
				}
				$native = $line->get_quantity( 'edit' );
				$raw    = (string) $native;
				$units  = WCJ_Returns_Quantity::parse( is_float( $native ) && $native < 0 ? -$native : ( 0 === strpos( $raw, '-' ) ? substr( $raw, 1 ) : $native ) );
				if ( $units > 0 && ( 0 !== strpos( $raw, '-' ) || ! isset( $capacities[ $parent ] ) || $units > $capacities[ $parent ] ) ) {
					throw new RuntimeException( 'refund_invalid' );
				}
				if ( $units > 0 ) {
					$capacities[ $parent ] -= $units;
				} else {
					$amount_only = true;
				}
				$lines[ $line_id ] = array( $units > 0 ? 'quantity' : 'amount_only', $parent, $units, $total, $taxes );
			}
			$unallocated               = $allocated !== $refund_amount;
			$amount_only               = $amount_only || empty( $lines ) || $unallocated;
			$refund_rows[ $refund_id ] = array( $refund_amount, $lines, $unallocated );
		}
		$owner  = (int) $order->get_customer_id( 'edit' );
		$status = (string) $order->get_status( 'edit' );
		$stamp  = $completed ? $completed->getTimestamp() : 0;
		$values = array( $id, $owner, $status, $stamp, $rows, $refund_rows );
		$guest  = 0 === $owner ? $this->binding( $order ) : array();
		if ( 0 === $owner ) {
			$values[] = $guest['digest'];
		}
		$hash = hash( 'sha256', wp_json_encode( $values ) );
		return array(
			'order'       => $order,
			'owner'       => $owner,
			'status'      => $status,
			'completed'   => $stamp,
			'capacities'  => $capacities,
			'names'       => $names,
			'hash'        => $hash,
			'amount_only' => (bool) $amount_only,
			'guest'       => $guest,
		);
	}

	/**
	 * Stable guest identity comparison, never authentication or a rotating key.
	 *
	 * @param int $id Order ID.
	 * @param mixed $email Raw persisted billing email.
	 * @param mixed $key Raw persisted Woo order key.
	 * @return string Domain-separated immutable identity digest.
	 */
	public static function guest_digest( $id, $email, $key ) {
		$email = is_string( $email ) ? $email : '';
		$key   = is_string( $key ) ? $key : '';
		return hash( 'sha256', "wcj_return_guest_owner_v1\n" . wp_json_encode( array( get_current_blog_id(), $id, strtolower( $email ), $key ) ) );
	}

	/**
	 * Raw edit-context binding. Invalid identity is flagged, not a queue exception.
	 *
	 * @param WC_Order $order Fresh persisted shop order.
	 * @return array Private data, never rendered or passed to scheduler arguments.
	 */
	private function binding( $order ) {
		$email = $order->get_billing_email( 'edit' );
		$key   = $order->get_order_key( 'edit' );
		return array(
			'digest' => self::guest_digest( (int) $order->get_id(), $email, $key ),
			'email'  => is_string( $email ) ? $email : '',
			'valid'  => is_string( $email ) && strlen( $email ) <= 254 && is_email( $email ) && sanitize_email( $email ) === $email && is_string( $key ) && '' !== $key && strlen( $key ) <= 256,
		);
	}

	/**
	 * Fresh light identity before challenge/dispatch, without items or refunds.
	 *
	 * @param int $id Canonical positive order ID.
	 * @return array Private ownership and email identity snapshot.
	 */
	public function guest_identity( $id ) {
		$order = $this->fresh_order( $id );
		return array( 'id' => (int) $order->get_id(), 'owner' => (int) $order->get_customer_id( 'edit' ), 'guest' => $this->binding( $order ) );
	}
}
