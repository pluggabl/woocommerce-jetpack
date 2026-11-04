<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Retain the established include path; the companion adapter/exception is intentionally colocated.
/**
 * Native WooCommerce Blocks checkout uploads, separate from Classic sessions.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Certain admission refusal, raised only before blob/catalog publication. */
class WCJ_Checkout_Files_Upload_Capacity_Exception extends RuntimeException {}

/** Real Store API transfer, private claim and permissioned retrieval. */
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- The small internal adapter/exception is coupled to this existing include and is not independently loaded.
/** WCJ Checkout Files Upload Blocks implementation. */
class WCJ_Checkout_Files_Upload_Blocks {
	// phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound
	const API_NAMESPACE = 'booster-checkout-upload';
	/**
	 * Existing module policy owner.
	 *
	 * @var WCJ_Checkout_Files_Upload
	 */
	private $module;
	/**
	 * Private mail scratch paths created by this request only.
	 *
	 * @var array
	 */
	private $mail_files = array();

	/**
	 * Connect the existing module to supported native checkout hooks.
	 *
	 * @param WCJ_Checkout_Files_Upload $module Existing enabled module.
	 */
	public function __construct( $module ) {
		$this->module = $module;
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register' ) );
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'claim_order' ), 20, 2 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_links' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'order_links' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'order_links' ) );
		add_action( 'init', array( $this, 'download' ) );
		add_filter( 'woocommerce_email_attachments', array( $this, 'mail_attachments' ), 20, 3 );
		add_action( 'shutdown', array( $this, 'cleanup_mail_files' ) );
	}

	/** Register only supported WooCommerce extension entry points. */
	public function register() {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) || ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => self::API_NAMESPACE,
				'callback'  => array( $this, 'update' ),
			)
		);
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => self::API_NAMESPACE,
				'data_callback'   => array( $this, 'data' ),
				'schema_callback' => array( $this, 'schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
				'namespace'       => self::API_NAMESPACE,
				'schema_callback' => array( $this, 'checkout_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/** Enqueue a real SlotFill only where the Checkout block is present. */
	public function scripts() {
		if ( ! is_checkout() || is_order_received_page() || ! has_block( 'woocommerce/checkout' ) ) {
			return;
		}
		wp_enqueue_script( 'wcj-checkout-files-upload-blocks', plugins_url( 'js/wcj-checkout-files-upload-blocks.js', __FILE__ ), array( 'wc-blocks-checkout', 'wc-blocks-data-store', 'wp-element', 'wp-plugins', 'wp-data', 'wp-i18n' ), '9.0.0', true );
	}

	/**
	 * Describe existing per-edition and per-cart visibility and required policy.
	 *
	 * @return array Public field policies.
	 */
	public function fields() {
		$fields = array();
		$total  = (int) apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total; ++$i ) {
			if ( 'yes' === wcj_get_option( 'wcj_checkout_files_upload_enabled_' . $i, 'yes' ) && 'disable' !== wcj_get_option( 'wcj_checkout_files_upload_hook_' . $i, 'woocommerce_before_checkout_form' ) && $this->module->is_visible( $i ) ) {
				$fields[] = array(
					'id'       => $i,
					'label'    => wp_strip_all_tags( wcj_get_option( 'wcj_checkout_files_upload_label_' . $i, __( 'Upload file', 'woocommerce-jetpack' ) ) ),
					'accept'   => (string) wcj_get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ),
					'required' => 'yes' === wcj_get_option( 'wcj_checkout_files_upload_required_' . $i, 'no' ),
				);
			}
		}
		return $fields;
	}

	/**
	 * Describe public response fields without private paths or owner secrets.
	 *
	 * @return array Public response schema.
	 */
	public function schema() {
		return array(
			'available'        => array(
				'type'     => 'boolean',
				'readonly' => true,
			),
			'upload_available' => array(
				'type'     => 'boolean',
				'readonly' => true,
			),
			'error'            => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'revision'         => array(
				'type'     => 'integer',
				'readonly' => true,
			),
			'attempt'          => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'state'            => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'order_id'         => array(
				'type'     => 'integer',
				'readonly' => true,
			),
			'fields'           => array(
				'type'     => 'array',
				'items'    => array( 'type' => 'object' ),
				'readonly' => true,
			),
			'files'            => array(
				'type'     => 'array',
				'items'    => array( 'type' => 'object' ),
				'readonly' => true,
			),
			'max_size'         => array(
				'type'     => 'integer',
				'readonly' => true,
			),
		);
	}

	/**
	 * Describe writable Checkout proof separately from the read-only Cart response.
	 *
	 * @return array
	 */
	public function checkout_schema() {
		return array(
			'revision' => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'arg_options' => array(
					'validate_callback' => static function ( $value ) {
						return is_int( $value ) && $value >= 0;
					},
					'sanitize_callback' => static function ( $value ) {
						return $value;
					},
				),
			),
			'attempt'  => array(
				'type'        => 'string',
				'pattern'     => '^[a-f0-9]{32}$',
				'arg_options' => array(
					'validate_callback' => static function ( $value ) {
						return is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{32}$/D', $value );
					},
					'sanitize_callback' => static function ( $value ) {
						return $value;
					},
				),
			),
		);
	}

	/**
	 * Read the current authoritative private snapshot without mutation or retry.
	 *
	 * @return array Public snapshot or a fixed unavailable response.
	 */
	public function data() {
		$data = array(
			'available'        => false,
			'upload_available' => false,
			'error'            => '',
			'revision'         => 0,
			'attempt'          => '',
			'state'            => 'staged',
			'order_id'         => 0,
			'fields'           => $this->fields(),
			'files'            => array(),
			'max_size'         => (int) wp_max_upload_size(),
		);
		try {
			$store                    = new WCJ_Checkout_Files_Upload_Store();
			$key                      = $this->owner_key( false );
			$snapshot                 = $store->transact(
				function ( &$catalog, $storage ) use ( $key ) {
					$record = $catalog['owners'][ $key ] ?? null;
					$units  = $storage->active_staging_units( $catalog );
					$upload = true; // Snapshot only. Explicit mutation admission selects eligible victims.
					try {
						$storage->encode_catalog( $catalog );
					} catch ( RuntimeException $error ) {
						if ( 'private_catalog_capacity' !== $error->getMessage() ) {
							throw $error; }
						$upload = false;
					}
					return array( $record, $upload );
				}
			);
			$data['upload_available'] = $snapshot[1];
			if ( ! $snapshot[1] ) {
				$data['error'] = __( 'The store cannot accept uploads right now. Contact the store.', 'woocommerce-jetpack' );
			}
			$record = $snapshot[0];
			if ( '' !== $key ) {
				if ( null !== $record ) {
					$this->admit_owner( $record, false, false );
					$data['revision'] = $record['revision'];
					$data['attempt']  = $record['attempt'];
					$data['order_id'] = $record['order_id'];
					$data['state']    = 'staged' === $record['state'] && ! hash_equals( $record['cart'], $this->cart_identity() ) ? 'cart-changed' : $record['state'];
					foreach ( $record['files'] as $slot => $file ) {
						$data['files'][] = array(
							'slot' => (int) $slot,
							'name' => $file['name'],
							'size' => $file['size'],
						);
					}
				} else {
					$data['state'] = 'revoked';
				}
			}
			$data['available'] = true;
		} catch ( \Throwable $error ) {
			$data['error'] = __( 'Private uploads are unavailable. Contact the store before placing an order that requires a file.', 'woocommerce-jetpack' );
		}
		return $data;
	}

	/**
	 * Begin explicitly; assign a secret only after known catalog publication.
	 *
	 * @throws RuntimeException When a new provisional owner cannot be admitted.
	 */
	private function begin() {
		if ( ! WC()->cart->get_cart() || ! $this->fields() ) {
			throw new RuntimeException( 'upload_field_unavailable' );
		}
		$key      = $this->owner_key( false );
		$secret   = bin2hex( random_bytes( 32 ) );
		$next_key = $this->domain_hmac( $secret, 'owner' );
		$store    = new WCJ_Checkout_Files_Upload_Store();
		$created  = $store->transact(
			function ( &$catalog, $storage ) use ( $key, $next_key ) {
				$storage->active_staging_units( $catalog );
				if ( isset( $catalog['owners'][ $key ] ) ) {
					$record = $catalog['owners'][ $key ];
					$this->admit_owner( $record, false );
					if ( 'staged' !== $record['state'] ) {
						throw new RuntimeException( 'upload_unknown_outcome_held' );
					}
					return false;
				}
				$next                        = $catalog;
				$next['owners'][ $next_key ] = array(
					'attempt'   => bin2hex( random_bytes( 16 ) ),
					'revision'  => 0,
					'principal' => get_current_user_id(),
					'cart'      => $this->cart_identity(),
					'state'     => 'staged',
					'order_id'  => 0,
					'order_key' => '',
					'files'     => array(),
				);
				$storage->admit_staging( $catalog, $next, $key );
				return true;
			},
			true
		);
		if ( $created ) {
			WC()->session->set( 'wcj_blocks_upload_owner', $secret );
		}
	}

	/**
	 * Handle a native mutation with the current attempt and integer revision.
	 *
	 * @param array $data Validated JSON request body, never logged.
	 * @throws RuntimeException Internally caught before mapping to a fixed route exception.
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException On denial or uncertainty.
	 */
	public function update( $data ) {
		try {
			$this->csrf();
			if ( ! is_array( $data ) || ! isset( $data['revision'], $data['slot'], $data['operation'] ) || ! is_int( $data['revision'] ) || $data['revision'] < 0 || ! is_int( $data['slot'] ) || ! in_array( $data['operation'], array( 'begin', 'upload', 'remove', 'reset', 'new-purchase' ), true ) ) {
				throw new RuntimeException( 'upload_input_invalid' );
			}
			if ( 'begin' === $data['operation'] ) {
				$this->begin();
				return;
			}
			if ( ! is_string( $data['attempt'] ?? null ) || ! preg_match( '/^[a-f0-9]{32}$/D', $data['attempt'] ) ) {
				throw new RuntimeException( 'upload_revision_conflict' );
			}
			if ( 'new-purchase' === $data['operation'] ) {
				$this->new_purchase( $data );
				return;
			}
			$fields = array_column( $this->fields(), null, 'id' );
			if ( 'reset' !== $data['operation'] && ! isset( $fields[ $data['slot'] ] ) ) {
				throw new RuntimeException( 'upload_field_unavailable' );
			}
			$store = new WCJ_Checkout_Files_Upload_Store();
			$file  = 'upload' === $data['operation'] ? $this->validate_file( $data, $fields[ $data['slot'] ], $store ) : null;
			$key   = $this->owner_key( false );
			$store->transact(
				function ( &$catalog, $storage ) use ( $key, $data, $file ) {
					$storage->active_staging_units( $catalog );
					if ( ! isset( $catalog['owners'][ $key ] ) ) {
						throw new RuntimeException( 'upload_revoked' );
					}
					$next   = $catalog;
					$record =& $next['owners'][ $key ];
					$this->admit_owner( $record, true, 'reset' !== $data['operation'] );
					if ( 'staged' !== $record['state'] || $data['revision'] !== $record['revision'] || $data['attempt'] !== $record['attempt'] || PHP_INT_MAX === $record['revision'] ) {
						throw new RuntimeException( 'upload_revision_conflict' );
					}
					if ( 'reset' === $data['operation'] ) {
						foreach ( $record['files'] as $old ) {
							$next['retired'][ $old['id'] ] = $record['revision'] + 1;
						}
						$record['files'] = array();
						$record['cart']  = $this->cart_identity();
						++$record['revision'];
					} else {
						$slot = (string) $data['slot'];
						if ( isset( $record['files'][ $slot ] ) ) {
							$next['retired'][ $record['files'][ $slot ]['id'] ] = $record['revision'] + 1;
							unset( $record['files'][ $slot ] );
						}
						++$record['revision'];
						if ( null !== $file ) {
							$metadata       = $file;
							$metadata['id'] = str_repeat( '0', 64 );
							unset( $metadata['bytes'] );
							$record['files'][ $slot ] = $metadata;
						}
					}
					unset( $record );
					if ( null !== $file ) {
						$storage->admit_staging( $catalog, $next, $key );
						$catalog['owners'][ $key ]['files'][ (string) $data['slot'] ]['id'] = $storage->put( $file['bytes'] );
					} else {
						// Retirement-only: no foreign eviction, but adoption and digit growth count.
						$this->admit_catalog_growth( $storage, $next );
						$catalog = $next;
					}
				},
				true
			);
		} catch ( \Throwable $error ) {
			if ( $error instanceof WCJ_Checkout_Files_Upload_Capacity_Exception || ( $error instanceof RuntimeException && 'private_staging_capacity' === $error->getMessage() ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Store API RouteException messages are JSON encoded by WooCommerce, not emitted as HTML.
				throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'wcj_upload_capacity', __( 'The store cannot accept uploads right now. Contact the store.', 'woocommerce-jetpack' ), 409 );
			}
			if ( $error instanceof RuntimeException && 'upload_revoked' === $error->getMessage() ) {
				$this->revoked();
			}
			$this->reject();
		}
	}

	/**
	 * Reject certain provisional-file loss separately from unknown order outcome.
	 *
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Always signals a revoked upload.
	 */
	private function revoked() {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Store API RouteException messages are JSON encoded by WooCommerce, not emitted as HTML.
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'wcj_upload_revoked', __( 'These provisional files are no longer retained. Choose and upload the files again if needed.', 'woocommerce-jetpack' ), 409 );
	}

	/**
	 * Check reserve before any blob or catalog publication, never after it.
	 *
	 * @param WCJ_Checkout_Files_Upload_Store $storage Current private store.
	 * @param array                           $catalog Full prospective private catalog.
	 * @throws RuntimeException When capacity or another catalog condition fails.
	 * @throws WCJ_Checkout_Files_Upload_Capacity_Exception When finite capacity admission is refused.
	 */
	private function admit_catalog_growth( $storage, $catalog ) {
		try {
			$storage->encode_catalog( $catalog );
		} catch ( RuntimeException $error ) {
			if ( 'private_catalog_capacity' !== $error->getMessage() ) {
				throw $error;
			}
			throw new WCJ_Checkout_Files_Upload_Capacity_Exception( 'private_catalog_capacity' );
		}
	}

	/**
	 * Validate actual transferred bytes using the existing module's file policies.
	 *
	 * @param array                           $data Submitted filename and encoded content.
	 * @param array                           $field Existing field policy.
	 * @param WCJ_Checkout_Files_Upload_Store $store Admitted private store.
	 * @return array Validated file metadata and original bytes.
	 * @throws RuntimeException When file validation or private scratch use fails.
	 */
	private function validate_file( $data, $field, $store ) {
		$limit = (int) wp_max_upload_size();
		if ( ! isset( $data['name'], $data['content'] ) || ! is_string( $data['name'] ) || ! is_string( $data['content'] ) || strlen( $data['name'] ) > 255 || strlen( $data['content'] ) > 4 * (int) ceil( $limit / 3 ) ) {
			throw new RuntimeException( 'upload_size_invalid' );
		}
		$name  = sanitize_file_name( $data['name'] );
		$bytes = base64_decode( $data['content'], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Store API file transfer uses canonical base64 with strict decoding and a size bound, not executable obfuscation.
		if ( '' === $name || false === $bytes || strlen( $bytes ) > $limit || base64_encode( $bytes ) !== $data['content'] ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Store API file transfer uses canonical base64 with strict decoding and a size bound, not executable obfuscation.
			throw new RuntimeException( 'upload_content_invalid' );
		}
		$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( $this->module->is_extension_blocked( $ext, $name ) ) {
			throw new RuntimeException( 'upload_type_invalid' );
		}
		$path = $store->scratch();
		try {
			if ( strlen( $bytes ) !== file_put_contents( $path, $bytes ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Private local storage requires checked native handles, exact byte counts, permissions and atomic publication; remote filesystem adapters cannot preserve those guarantees.
				throw new RuntimeException( 'upload_validation_failure' );
			}
			$type = wp_check_filetype_and_ext( $path, $name );
			if ( empty( $type['type'] ) || empty( $type['ext'] ) || strtolower( $type['ext'] ) !== $ext || ! $this->accepts_file( $field['accept'], $ext, $type['type'] ) ) {
				throw new RuntimeException( 'upload_type_invalid' );
			}
		} finally {
			if ( ! unlink( $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Private local storage requires checked native handles, exact byte counts, permissions and atomic publication; remote filesystem adapters cannot preserve those guarantees.
				throw new RuntimeException( 'private_scratch_cleanup_held' );
			}
		}
		return array(
			'name'   => $name,
			'size'   => strlen( $bytes ),
			'type'   => $type['type'],
			'sha256' => hash( 'sha256', $bytes ),
			'bytes'  => $bytes,
		);
	}

	/**
	 * Honor existing HTML accept extension, MIME and MIME wildcard tokens.
	 *
	 * @param string $configured Existing accept attribute.
	 * @param string $extension Validated filename extension.
	 * @param string $mime Detected MIME type.
	 * @return bool Whether an existing accept token matches.
	 */
	private function accepts_file( $configured, $extension, $mime ) {
		$tokens = array_filter( array_map( 'trim', explode( ',', strtolower( $configured ) ) ) );
		if ( ! $tokens ) {
			return true;
		}
		$mime = strtolower( $mime );
		foreach ( $tokens as $token ) {
			if ( '.' . $extension === $token || $mime === $token || ( '/*' === substr( $token, -2 ) && substr( $mime, 0, strpos( $mime, '/' ) ) . '/*' === $token ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Claim only a real persisted order identity before Woo's optional meta save.
	 * A later save/payment failure NEVER frees or rebinds this claim.
	 *
	 * @param WC_Order        $order Actual Store API order.
	 * @param WP_REST_Request $request Actual checkout request.
	 *
	 * @throws RuntimeException Internally caught if private claim publication fails.
	 */
	public function claim_order( $order, $request ) {
		try {
			$fields     = array_column( $this->fields(), null, 'id' );
			$key        = $this->owner_key( false );
			$extensions = $request->get_param( 'extensions' );
			$submitted  = is_array( $extensions ) ? ( $extensions[ self::API_NAMESPACE ] ?? null ) : null;
			if ( '' === $key ) {
				if ( null !== $submitted && array() !== $submitted ) {
					throw new RuntimeException( 'upload_revoked' );
				}
				foreach ( $fields as $field ) {
					if ( $field['required'] ) {
						throw new RuntimeException( 'upload_revoked' );
					}
				}
				return;
			}
			$this->csrf();
			if ( ! $order instanceof WC_Order || $order->get_id() <= 0 || (int) $order->get_customer_id() !== get_current_user_id() ) {
				throw new RuntimeException( 'upload_order_identity_unknown' );
			}

			$store = new WCJ_Checkout_Files_Upload_Store();
			$claim = $store->transact(
				function ( &$catalog, $storage ) use ( $key, $fields, $submitted, $order ) {
					if ( ! isset( $catalog['owners'][ $key ] ) ) {
						$required = array_filter(
							$fields,
							static function ( $field ) {
								return $field['required'];
							}
						);
						if ( ( null === $submitted || array() === $submitted ) && ! $required ) {
							return null;
						}
						throw new RuntimeException( 'upload_revoked' );
					}
					$record =& $catalog['owners'][ $key ];
					// A changed cart needs no claim for positively owned, empty provisional files.
					// Never adopt, release or rebind this record; claimed/nonempty records stay held.
					if ( 'staged' === $record['state'] && array() === $record['files'] && ! hash_equals( $record['cart'], $this->cart_identity() ) ) {
						$this->admit_owner( $record, false, false );
						$required = array_filter(
							$fields,
							static function ( $field ) {
								return $field['required'];
							}
						);
						if ( ! $required && ( null === $submitted || array() === $submitted || ( is_array( $submitted ) && ( $submitted['revision'] ?? null ) === $record['revision'] && ( $submitted['attempt'] ?? null ) === $record['attempt'] ) ) ) {
							return null;
						}
					}
					$this->admit_owner( $record, true );
					if ( ! is_array( $submitted ) || ( $submitted['revision'] ?? null ) !== $record['revision'] || ( $submitted['attempt'] ?? null ) !== $record['attempt'] ) {
						throw new RuntimeException( 'upload_revision_conflict' );
					}
					foreach ( $fields as $slot => $field ) {
						if ( $field['required'] && ! isset( $record['files'][ $slot ] ) ) {
							throw new RuntimeException( 'upload_required' );
						}
					}
					foreach ( $record['files'] as $slot => $file ) {
						if ( ! isset( $fields[ $slot ] ) ) {
							throw new RuntimeException( 'upload_field_unavailable' );
						}
						$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
						if ( $this->module->is_extension_blocked( $ext, $file['name'] ) || ! $this->accepts_file( $fields[ $slot ]['accept'], $ext, $file['type'] ) || ! hash_equals( $file['sha256'], hash( 'sha256', $storage->get( $file['id'], $file['size'] ) ) ) ) {
							throw new RuntimeException( 'upload_file_no_longer_admitted' );
						}
					}
					$order_id  = (int) $order->get_id();
					$order_key = $this->domain_hmac( $order->get_order_key(), 'order' );
					if ( 'staged' !== $record['state'] && ( $order_id !== $record['order_id'] || ! hash_equals( $record['order_key'], $order_key ) ) ) {
						throw new RuntimeException( 'upload_unknown_outcome_held' );
					}
					foreach ( $catalog['owners'] as $other_key => $other ) {
						if ( $other_key !== $key && $order_id === $other['order_id'] ) {
							throw new RuntimeException( 'upload_order_already_claimed' );
						}
					}
					$record['state']     = 'claimed-unknown';
					$record['order_id']  = $order_id;
					$record['order_key'] = $order_key;
					return array(
						'attempt'  => $record['attempt'],
						'revision' => $record['revision'],
					);
				},
				true
			);
			// This is a projection, NOT a durable-save witness or ownership authority.
			if ( null !== $claim ) {
				$order->update_meta_data( '_wcj_blocks_upload_claim', $claim );
			}
		} catch ( \Throwable $error ) {
			if ( $error instanceof RuntimeException && 'upload_revoked' === $error->getMessage() ) {
				$this->revoked(); }
			$this->reject();
		}
	}

	/**
	 * Explicit new-purchase intent after a positive read of the exact known order.
	 * This NEVER releases, copies, rebinds or declares the previous claim durable.
	 * Pending/failed/draft or unreadable orders remain held, not blindly retried.
	 *
	 * @param array $data Explicit acknowledged-order intent.
	 * @throws RuntimeException When the prior order cannot be positively reconciled.
	 */
	private function new_purchase( $data ) {
		$key   = $this->owner_key( false );
		$store = new WCJ_Checkout_Files_Upload_Store();
		$next  = bin2hex( random_bytes( 32 ) );
		$store->transact(
			function ( &$catalog, $storage ) use ( $key, $data, $next ) {
				if ( ! isset( $catalog['owners'][ $key ] ) || ! WC()->cart->get_cart() ) {
					throw new RuntimeException( 'upload_prior_order_unreconciled' );
				}
				$old = $catalog['owners'][ $key ];
				$this->admit_owner( $old, false, false );
				if ( 'claimed-unknown' !== $old['state'] || isset( $old['successor'] ) || $data['revision'] !== $old['revision'] || ( $data['attempt'] ?? null ) !== $old['attempt'] || ( $data['acknowledged_order'] ?? null ) !== $old['order_id'] ) {
					throw new RuntimeException( 'upload_prior_order_unreconciled' );
				}
				$order = wc_get_order( $old['order_id'] );
				if ( ! $order instanceof WC_Order || (int) $order->get_id() !== $old['order_id'] || ! hash_equals( $old['order_key'], $this->domain_hmac( $order->get_order_key(), 'order' ) ) || ! in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold', 'refunded', 'cancelled' ), true ) ) {
					throw new RuntimeException( 'upload_prior_order_unreconciled' );
				}
				// Public status is only a positive reconciliation observation, not a
				// cache-free save witness. All old claim/files remain exactly retained.
				$new_key                                    = $this->domain_hmac( $next, 'owner' );
				$prospective                                = $catalog;
				$prospective['owners'][ $key ]['successor'] = $new_key;
				$prospective['owners'][ $new_key ]          = array(
					'attempt'   => bin2hex( random_bytes( 16 ) ),
					'revision'  => 0,
					'principal' => get_current_user_id(),
					'cart'      => $this->cart_identity(),
					'state'     => 'staged',
					'order_id'  => 0,
					'order_key' => '',
					'files'     => array(),
				);
				$storage->admit_staging( $catalog, $prospective, $key );
			},
			true
		);
		// An uncertain session save leaves an empty new claim held, never old-file reuse.
		WC()->session->set( 'wcj_blocks_upload_owner', $next );
	}

	/**
	 * Check the explicit nonce in addition to native Store API route checks.
	 *
	 * @throws RuntimeException When the native nonce or session is unavailable.
	 */
	private function csrf() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The Store API nonce is unslashed and verified immediately below before any mutation.
		$nonce = isset( $_SERVER['HTTP_NONCE'] ) && is_string( $_SERVER['HTTP_NONCE'] ) ? wp_unslash( $_SERVER['HTTP_NONCE'] ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wc_store_api' ) || ! WC()->session || ! WC()->cart || '' === (string) WC()->session->get_customer_id() ) {
			throw new RuntimeException( 'upload_session_denied' );
		}
	}

	/**
	 * Bind internal capabilities and order keys to the same private site domain.
	 *
	 * @param string $value Native secret or order key, never request data.
	 * @param string $purpose Internal owner or order purpose.
	 * @return string Domain-separated authentication digest.
	 */
	private function domain_hmac( $value, $purpose ) {
		return hash_hmac( 'sha256', 'wcj-upload-domain/v2/' . WCJ_Checkout_Files_Upload_Store::current_domain() . '/' . $purpose . '/' . $value, wp_salt( 'auth' ) );
	}

	/**
	 * Read an internal native-session capability, never request JSON.
	 *
	 * @param bool $create Retained internal signature; never creates a capability.
	 * @return string Domain-bound owner key, or empty when not begun.
	 * @throws RuntimeException When the session or existing capability is invalid.
	 */
	private function owner_key( $create ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Existing callers all pass false; this private reader never creates a key. Only explicit begin/new_purchase can create one.
		if ( ! WC()->session || ! WC()->cart || '' === (string) WC()->session->get_customer_id() ) {
			throw new RuntimeException( 'upload_session_denied' );
		}
		$secret = WC()->session->get( 'wcj_blocks_upload_owner' );
		if ( null === $secret ) {
			return '';
		}
		if ( ! is_string( $secret ) || ! preg_match( '/^[a-f0-9]{64}$/D', $secret ) ) {
			throw new RuntimeException( 'upload_session_denied' );
		}
		return $this->domain_hmac( $secret, 'owner' );
	}

	/** Stable cart contents, not address, fee totals or mutable order-meta cache. */
	private function cart_identity() {
		$items = array();
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$items[ $key ] = array( (int) $item['product_id'], (int) $item['variation_id'], $item['quantity'], $item['variation'] );
		}
		return hash( 'sha256', wp_json_encode( $items ) );
	}

	/**
	 * Admit the same native capability, principal and optional stable cart.
	 *
	 * @param array $record Private owner record, with one-way guest adoption.
	 * @param bool  $write Whether positive guest adoption may be persisted.
	 * @param bool  $check_cart Whether the stable cart identity must match.
	 * @throws RuntimeException When principal or cart admission fails.
	 */
	private function admit_owner( &$record, $write, $check_cart = true ) {
		$user = get_current_user_id();
		if ( ! is_int( $record['principal'] ) || ( $record['principal'] !== $user && 0 !== $record['principal'] ) || ( $check_cart && ! hash_equals( $record['cart'], $this->cart_identity() ) ) ) {
			throw new RuntimeException( 'upload_owner_or_cart_changed' );
		}
		if ( $write && 0 === $record['principal'] && $user > 0 ) {
			$record['principal'] = $user;
		}
	}

	/**
	 * Reject generically without disclosing paths or authorizing a retry.
	 *
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Always signals an unknown or unavailable upload.
	 */
	private function reject() {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Store API RouteException messages are JSON encoded by WooCommerce, not emitted as HTML.
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'wcj_upload_unavailable', __( 'The upload could not be confirmed. Reload the cart to reconcile its current files. Do not repeat an uncertain checkout.', 'woocommerce-jetpack' ), 409 );
	}

	/**
	 * Read an exact known-order record without a negative metadata witness.
	 *
	 * @param WC_Order $order Actual known order.
	 * @return array|null Matching private owner record.
	 * @throws RuntimeException When private storage cannot be admitted.
	 */
	private function order_record( $order ) {
		$store = new WCJ_Checkout_Files_Upload_Store();
		return $store->transact(
			function ( &$catalog ) use ( $order ) {
				foreach ( $catalog['owners'] as $record ) {
					if ( (int) $order->get_id() === $record['order_id'] && hash_equals( $record['order_key'], $this->domain_hmac( $order->get_order_key(), 'order' ) ) ) {
						return $record;
					}
				}
				return null;
			}
		);
	}

	/**
	 * Display escaped private links on actual order, admin and email views.
	 *
	 * @param WC_Order $order Actual known order.
	 */
	public function order_links( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		try {
			$record = $this->order_record( $order );
			if ( null === $record ) {
				return;
			}
			foreach ( $record['files'] as $slot => $file ) {
				$url = add_query_arg(
					array(
						'wcj_private_checkout_file' => (int) $slot,
						'order_id'                  => $order->get_id(),
						'key'                       => $order->get_order_key(),
					),
					home_url( '/' )
				);
				echo '<p><a rel="noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $file['name'] ) . '</a></p>';
			}
		} catch ( \Throwable $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Deliberately retain the safe fallback/held state and never expose private exception details or replay an uncertain mutation.
			// Never fall back to a public file URL when the private store is unknown.
		}
	}

	/**
	 * Honor the existing admin/customer email attachment options for private files.
	 * Plaintext exists only in the admitted private directory during this request.
	 *
	 * @param array    $attachments Existing attachment paths.
	 * @param string   $email_id Existing WooCommerce email identifier.
	 * @param WC_Order $order Actual known order.
	 * @return array Existing attachments plus admitted private scratch paths.
	 * @throws RuntimeException Internally caught when private attachment validation fails.
	 */
	public function mail_attachments( $attachments, $email_id, $order ) {
		if ( ! $order instanceof WC_Order || ! ( ( 'new_order' === $email_id && 'yes' === wcj_get_option( 'wcj_checkout_files_upload_attach_to_admin_new_order', 'yes' ) ) || ( 'customer_processing_order' === $email_id && 'yes' === wcj_get_option( 'wcj_checkout_files_upload_attach_to_customer_processing_order', 'yes' ) ) ) ) {
			return $attachments;
		}
		try {
			$record = $this->order_record( $order );
			if ( null === $record ) {
				return $attachments;
			}
			$store = new WCJ_Checkout_Files_Upload_Store();
			foreach ( $record['files'] as $file ) {
				$bytes = $store->get( $file['id'], $file['size'] );
				if ( ! hash_equals( $file['sha256'], hash( 'sha256', $bytes ) ) ) {
					throw new RuntimeException( 'private_mail_file_invalid' );
				}
				$path               = $store->scratch();
				$this->mail_files[] = $path;
				if ( strlen( $bytes ) !== file_put_contents( $path, $bytes ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Private local storage requires checked native handles, exact byte counts, permissions and atomic publication; remote filesystem adapters cannot preserve those guarantees.
					throw new RuntimeException( 'private_mail_scratch_invalid' );
				}
				$attachments[] = $path;
			}
		} catch ( \Throwable $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Deliberately retain the safe fallback/held state and never expose private exception details or replay an uncertain mutation.
			// Never attach ciphertext or fall back to a public URL on uncertainty.
		}
		return $attachments;
	}

	/** Idempotent cleanup of this request's own private mail scratch only. */
	public function cleanup_mail_files() {
		foreach ( $this->mail_files as $index => $path ) {
			if ( ! is_link( $path ) && ( ! file_exists( $path ) || unlink( $path ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Private local storage requires checked native handles, exact byte counts, permissions and atomic publication; remote filesystem adapters cannot preserve those guarantees.
				unset( $this->mail_files[ $index ] );
			}
		}
	}

	/**
	 * Use the existing manager, customer and native order-key access classes.
	 *
	 * @param WC_Order $order Actual known order.
	 * @param string   $key Submitted native order key.
	 * @return bool Whether an existing access class admits the download.
	 */
	private function can_download( $order, $key ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain least-privilege access.
		return $order instanceof WC_Order && ( wcj_current_user_can( 'manage_woocommerce' ) || wcj_current_user_can( 'edit_shop_orders' ) || wcj_is_user_role( 'administrator' ) || is_shop_manager() || ( wcj_is_user_logged_in() && (int) $order->get_customer_id() !== 0 && (int) $order->get_customer_id() === (int) wcj_get_current_user_id() ) || ( '' !== $key && $order->key_is_valid( $key ) ) );
	}

	/**
	 * Serve a permissioned read-only file using native order ownership or key.
	 *
	 * @throws RuntimeException Internally caught when download admission fails.
	 */
	public function download() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only download requires can_download() order ownership or order key; numeric selectors cannot authorize access.
		if ( ! isset( $_GET['wcj_private_checkout_file'] ) ) {
			return;
		}
		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended -- Read-only download requires can_download() order ownership or order key; numeric selectors cannot authorize access.
			if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_GET['order_id'] ) || ! is_scalar( $_GET['order_id'] ) || ! is_scalar( $_GET['wcj_private_checkout_file'] ) ) {
				throw new RuntimeException( 'download_denied' );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only download requires can_download() order ownership or order key; numeric selectors cannot authorize access.
			$order = wc_get_order( absint( $_GET['order_id'] ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only download requires can_download() order ownership or order key; numeric selectors cannot authorize access.
			$key = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
			if ( ! $this->can_download( $order, $key ) ) {
				throw new RuntimeException( 'download_denied' );
			}
			$record = $this->order_record( $order );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only download requires can_download() order ownership or order key; numeric selectors cannot authorize access.
			$slot = absint( $_GET['wcj_private_checkout_file'] );
			if ( null === $record || ! isset( $record['files'][ $slot ] ) ) {
				throw new RuntimeException( 'download_denied' );
			}
			$file  = $record['files'][ $slot ];
			$store = new WCJ_Checkout_Files_Upload_Store();
			$bytes = $store->get( $file['id'], $file['size'] );
			if ( ! hash_equals( $file['sha256'], hash( 'sha256', $bytes ) ) ) {
				throw new RuntimeException( 'download_denied' );
			}
			nocache_headers();
			header( 'Content-Type: application/octet-stream' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Referrer-Policy: no-referrer' );
			header( 'Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode( $file['name'] ) );
			header( 'Content-Length: ' . strlen( $bytes ) );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authenticated octet-stream attachment with nosniff and integrity checks; HTML escaping corrupts its bytes.
			echo $bytes; // Raw authenticated attachment bytes, never HTML.
			exit;
		} catch ( \Throwable $error ) {
			wp_die( esc_html__( 'File unavailable.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
		}
	}
}
