<?php
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2018 Setasign - Jan Slabon (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser;

use setasign\Fpdi\FpdiException;

/**
 * Exception for the pdf parser class
 *
 * @package setasign\Fpdi\PdfParser
 */
class PdfParserException extends FpdiException {

	/**
	 * Int
	 *
	 * @var int
	 */
	const NOT_IMPLEMENTED = 0x0001;

	/**
	 * Int
	 *
	 * @var int
	 */
	const IMPLEMENTED_IN_FPDI_PDF_PARSER = 0x0002;

	/**
	 * Int
	 *
	 * @var int
	 */
	const INVALID_DATA_TYPE = 0x0003;

	/**
	 * Int
	 *
	 * @var int
	 */
	const FILE_HEADER_NOT_FOUND = 0x0004;

	/**
	 * Int
	 *
	 * @var int
	 */
	const PDF_VERSION_NOT_FOUND = 0x0005;

	/**
	 * Int
	 *
	 * @var int
	 */
	const INVALID_DATA_SIZE = 0x0006;
}
