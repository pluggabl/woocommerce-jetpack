<?php
/** Deliberately bounded stored-history read. No cleanup tool or product factory. */
defined( 'ABSPATH' ) || exit;

final class WCJ_Product_Slug_History_Refusal extends RuntimeException {}

final class WCJ_Product_Slug_History {
 const MAX_BYTES = 4096;
 const MAX_READ = 100;
 const MAX_SHOW = 25;
 const MAX_CHARS = 200;
 const UNAVAILABLE = 'No product you can edit has this ID.';

 public function __construct() {
  add_action( 'wp_ajax_wcj_product_slug_history', array( $this, 'handle' ) );
 }

 private static function fail( $message, $code = 422 ) {
  throw new WCJ_Product_Slug_History_Refusal( $message, $code );
 }

 public static function parse_id( $value ) {
  if ( ! is_string( $value ) || strlen( $value ) > strlen( (string) PHP_INT_MAX ) ||
   ! preg_match( '/\A[1-9][0-9]*\z/D', $value ) ||
   ( strlen( $value ) === strlen( (string) PHP_INT_MAX ) && strcmp( $value, (string) PHP_INT_MAX ) > 0 ) ) {
   self::fail( 'Enter one whole positive product or variation ID.' );
  }
  return (int) $value;
 }

 public static function text( $value, $bytes, $slug = true, $title = false ) {
  if ( ! is_string( $value ) || (int) $bytes > self::MAX_BYTES || strlen( $value ) !== (int) $bytes ||
   1 !== preg_match( '//u', $value ) || preg_match( true === $title ? '/[\p{Cc}]/u' : '/[\p{Cc}\p{Cf}]/u', $value ) ||
   ( $slug && ( '' === $value || preg_match( '/\A(?:N;|[abisdOCRrE]:)/D', $value ) ) ) ) {
   self::fail( 'A saved record uses a format or size this check cannot display. Use the product editor or ask support. No history was changed.' );
  }
  preg_match_all( '/./us', $value, $characters );
  return array( 'text' => implode( '', array_slice( $characters[0], 0, self::MAX_CHARS ) ),
   'shortened' => count( $characters[0] ) > self::MAX_CHARS );
 }

 private static function post( $id ) {
  global $wpdb;
  // CASE bounds bytes at the database boundary, including an unexpectedly large title.
  $sql = "SELECT ID, post_parent, LEFT(post_type,32) AS post_type, LEFT(post_status,32) AS post_status,
   OCTET_LENGTH(post_title) AS title_bytes,
   CASE WHEN OCTET_LENGTH(post_title)<=4096 THEN post_title ELSE NULL END AS title,
   OCTET_LENGTH(post_name) AS slug_bytes,
   CASE WHEN OCTET_LENGTH(post_name)<=4096 THEN post_name ELSE NULL END AS slug
   FROM {$wpdb->posts} WHERE ID=%d LIMIT 1";
  $rows = $wpdb->get_results( $wpdb->prepare( $sql, $id ), ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $rows ) ) {
   throw new RuntimeException( 'Stored projection unavailable.' );
  }
  return $rows ? $rows[0] : null;
 }

 private static function authorize() {
  if ( ! WCJ_Diagnose_Customize_Workspace::allowed( 'old_slugs' ) ) {
   self::fail( 'This check is not available to your account. Ask an administrator.', 403 );
  }
 }

 public static function read( $input ) {
  global $wpdb;
  self::authorize();
  $id = self::parse_id( $input );
  if ( ! current_user_can( 'edit_post', $id ) ) { self::fail( self::UNAVAILABLE, 403 ); }
  $post = self::post( $id );
  if ( ! $post ) { self::fail( self::UNAVAILABLE, 403 ); }
  if ( ! in_array( $post['post_type'], array( 'product', 'product_variation' ), true ) ) {
   self::fail( 'This ID is not a product or variation.' );
  }
  $statuses = array( 'publish', 'draft', 'pending', 'private' );
  $variation = 'product_variation' === $post['post_type'];
  if ( ! in_array( $post['post_status'], $statuses, true ) && ! ( $variation && 'inherit' === $post['post_status'] ) ) {
   self::fail( self::UNAVAILABLE, 403 );
  }
  $editor = $id;
  if ( $variation ) {
   $parent = (int) $post['post_parent'];
   if ( $parent <= 0 || ! current_user_can( 'edit_post', $parent ) ) { self::fail( self::UNAVAILABLE, 403 ); }
   $parent_post = self::post( $parent );
   if ( ! $parent_post || 'product' !== $parent_post['post_type'] || ! in_array( $parent_post['post_status'], $statuses, true ) ) {
    self::fail( self::UNAVAILABLE, 403 );
   }
   $editor = $parent;
  }
  $title = self::text( $post['title'], $post['title_bytes'], false, true );
  // An empty current slug is a real saved state, not missing history or a default.
  $current = self::text( $post['slug'], $post['slug_bytes'], false );
  if ( preg_match( '/\A(?:N;|[abisdOCRrE]:)/D', $post['slug'] ) ) {
   self::fail( 'The current slug uses a format this check cannot display. Use the product editor. No history was changed.' );
  }
  $sql = "SELECT meta_id, OCTET_LENGTH(meta_value) AS value_bytes,
   CASE WHEN OCTET_LENGTH(meta_value)<=4096 THEN meta_value ELSE NULL END AS value
   FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id ASC LIMIT 101";
  $rows = $wpdb->get_results( $wpdb->prepare( $sql, $id, '_wp_old_slug' ), ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 101 ) {
   throw new RuntimeException( 'Stored history unavailable.' );
  }
  $overflow = count( $rows ) > self::MAX_READ;
  $entries = array();
  foreach ( array_slice( $rows, 0, self::MAX_READ ) as $index => $row ) {
   // Validate every inspected record, not just the displayed subset. Never deserialize.
   $shown = self::text( $row['value'], $row['value_bytes'] );
   if ( $index < self::MAX_SHOW ) {
    $shown['same_as_current'] = $row['value'] === $post['slug'];
    $entries[] = $shown;
   }
  }
  self::authorize();
  if ( ! current_user_can( 'edit_post', $id ) || ( $variation && ! current_user_can( 'edit_post', $editor ) ) ) {
   self::fail( self::UNAVAILABLE, 403 );
  }
  $result = array( 'id' => $id, 'type' => $variation ? 'variation' : 'product', 'title' => $title,
   'current' => $current, 'entries' => $entries, 'count' => min( self::MAX_READ, count( $rows ) ),
   'overflow' => $overflow, 'shown' => count( $entries ), 'read_at' => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
   'editor_url' => admin_url( 'post.php?post=' . $editor . '&action=edit' ) );
  $encoded = json_encode( $result );
  if ( false === $encoded || strlen( $encoded ) > 49152 ) { throw new RuntimeException( 'Response bound exceeded.' ); }
  return $result;
 }

 public function handle() {
  try {
   if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ||
    ! is_string( $_POST['nonce'] ?? null ) || ! wp_verify_nonce( $_POST['nonce'], 'wcj_product_slug_history' ) ) {
    self::fail( 'This check could not be verified. Reload this page and try again.', 403 );
   }
   $result = self::read( $_POST['id'] ?? null );
  } catch ( WCJ_Product_Slug_History_Refusal $e ) {
   wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
   return;
  } catch ( Throwable $e ) {
   wp_send_json_error( array( 'message' => 'Saved history could not be read safely. Nothing was changed by this check. Try again or ask support.' ), 500 );
   return;
  }
  wp_send_json_success( $result );
 }
}
