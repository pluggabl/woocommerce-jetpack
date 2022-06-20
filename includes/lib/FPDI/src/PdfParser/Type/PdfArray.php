<?php
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2018 Setasign - Jan Slabon (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing a PDF array object
 *
 * @package setasign\Fpdi\PdfParser\Type
 * @property array $value The value of the PDF type.
 */
class PdfArray extends PdfType {

	/**
	 * Parses an array of the passed tokenizer and parser.
	 *
	 * @param Tokenizer $tokenizer Get tokenizer.
	 * @param PdfParser $parser Get PdfParser.
	 * @return bool|self
	 * @throws PdfTypeException
	 */
	public static function parse( Tokenizer $tokenizer, PdfParser $parser ) {
		$result = array();

		// Recurse into this function until we reach the end of the array.
		$token = $tokenizer->getNextToken();
		while ( ']' !== ( $token ) ) {
			$value = $parser->readValue( $token );
			if ( false === $token || false === ( $value ) ) {
				return false;
			}

			$result[] = $value;
		}

		$v        = new self();
		$v->value = $result;

		return $v;
	}

	/**
	 * Helper method to create an instance.
	 *
	 * @param PdfType[] $values Get pdf type value.
	 * @return self
	 */
	public static function create( array $values = array() ) {
		$v        = new self();
		$v->value = $values;

		return $v;
	}

	/**
	 * Ensures that the passed array is a PdfArray instance with a (optional) specific size.
	 *
	 * @param mixed    $array Get array value.
	 * @param null|int $size Get size value.
	 * @return self
	 * @throws PdfTypeException
	 */
	public static function ensure( $array, $size = null ) {
		$result = PdfType::ensureType( self::class, $array, 'Array value expected.' );

		if ( null !== $size && \count( $array->value ) !== $size ) {
			throw new PdfTypeException(
				\sprintf( 'Array with %s entries expected.', $size ),
				PdfTypeException::INVALID_DATA_SIZE
			);
		}

		return $result;
	}
}
