<?php
/**
 * Booster for WooCommerce - PDF Invoicing - TcpdfFpdi
 *
 * This is needed to get round namespaces parse error in PHP < 5.3.0.
 *
 * @version 3.5.2
 * @since   3.5.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return new \setasign\Fpdi\TcpdfFpdi();
