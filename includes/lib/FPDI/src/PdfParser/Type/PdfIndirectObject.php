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
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing an indirect object
 *
 * @package setasign\Fpdi\PdfParser\Type
 */
class PdfIndirectObject extends PdfType {

	/**
	 * Parses an indirect object from a tokenizer, parser and stream-reader.
	 *
	 * @param int          $objectNumberToken Get objectNumberToken.
	 * @param int          $objectGenerationNumberToken Get objectGenerationNumberToken.
	 * @param PdfParser    $parser Get PdfParser Value.
	 * @param Tokenizer    $tokenizer Get tokenizer.
	 * @param StreamReader $reader Get reader.
	 * @return bool|self
	 * @throws PdfTypeException
	 */
	public static function parse(
		$objectNumberToken,
		$objectGenerationNumberToken,
		PdfParser $parser,
		Tokenizer $tokenizer,
		StreamReader $reader
	) {
		$value = $parser->readValue();
		if ( false === $value ) {
			return false;
		}

		$nextToken = $tokenizer->getNextToken();
		if ( 'stream' === $nextToken ) {
			$value = PdfStream::parse( $value, $reader );
		} elseif ( false !== $nextToken ) {
			$tokenizer->pushStack( $nextToken );
		}

		$v                   = new self();
		$v->objectNumber     = (int) $objectNumberToken;
		$v->generationNumber = (int) $objectGenerationNumberToken;
		$v->value            = $value;

		return $v;
	}

	/**
	 * Helper method to create an instance.
	 *
	 * @param int     $objectNumber Get objectNumber.
	 * @param int     $generationNumber Get generationNumber.
	 * @param PdfType $value Get Pdftype value.
	 * @return self
	 */
	public static function create( $objectNumber, $generationNumber, PdfType $value ) {
		$v                   = new self();
		$v->objectNumber     = (int) $objectNumber;
		$v->generationNumber = (int) $generationNumber;
		$v->value            = $value;

		return $v;
	}

	/**
	 * Ensures that the passed value is a PdfIndirectObject instance.
	 *
	 * @param mixed $indirectObject Get indirectObject.
	 * @return self
	 * @throws PdfTypeException
	 */
	public static function ensure( $indirectObject ) {
		return PdfType::ensureType( self::class, $indirectObject, 'Indirect object expected.' );
	}

	/**
	 * The object number.
	 *
	 * @var int
	 */
	public $objectNumber;

	/**
	 * The generation number.
	 *
	 * @var int
	 */
	public $generationNumber;
}
