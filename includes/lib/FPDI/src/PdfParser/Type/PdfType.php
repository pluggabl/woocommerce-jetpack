<?php
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2018 Setasign - Jan Slabon (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;

/**
 * A class defining a PDF data type
 *
 * @package setasign\Fpdi\PdfParser\Type
 */
class PdfType {

	/**
	 * Resolves a PdfType value to its value.
	 *
	 * This method is used to evaluate indirect and direct object references until a final value is reached.
	 *
	 * @param PdfType   $value Get PdfType value.
	 * @param PdfParser $parser Get PdfParser.
	 * @param bool      $stopAtIndirectObject Get stopAtIndirectObject.
	 * @return PdfType
	 * @throws CrossReferenceException CrossReferenceException.
	 * @throws PdfParserException PdfParserException.
	 */
	public static function resolve( PdfType $value, PdfParser $parser, $stopAtIndirectObject = false ) {
		if ( $value instanceof PdfIndirectObject ) {
			if ( true === $stopAtIndirectObject ) {
				return $value;
			}

			return self::resolve( $value->value, $parser, $stopAtIndirectObject );
		}

		if ( $value instanceof PdfIndirectObjectReference ) {
			return self::resolve( $parser->getIndirectObject( $value->value ), $parser, $stopAtIndirectObject );
		}

		return $value;
	}

	/**
	 * Ensure that a value is an instance of a specific PDF type.
	 *
	 * @param string  $type Get string type.
	 * @param PdfType $value Get PdfType value.
	 * @param string  $errorMessage get errorMessage.
	 * @return mixed
	 * @throws PdfTypeException PdfTypeException.
	 */
	protected static function ensureType( $type, $value, $errorMessage ) {
		if ( ! ( $value instanceof $type ) ) {
			throw new PdfTypeException(
				$errorMessage,
				PdfTypeException::INVALID_DATA_TYPE
			);
		}

		return $value;
	}

	/**
	 * The value of the PDF type.
	 *
	 * @var mixed
	 */
	public $value;
}
