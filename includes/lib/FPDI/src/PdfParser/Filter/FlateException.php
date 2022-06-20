<?php
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2018 Setasign - Jan Slabon (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for flate filter class
 *
 * @package setasign\Fpdi\PdfParser\Filter
 */
class FlateException extends FilterException {

	/**
	 * Integer
	 *
	 * @var integer
	 */
	const NO_ZLIB = 0x0401;

	/**
	 * Integer
	 *
	 * @var integer
	 */
	const DECOMPRESS_ERROR = 0x0402;
}
