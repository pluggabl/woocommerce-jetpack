<?php
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2018 Setasign - Jan Slabon (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfToken;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * Class CrossReference
 *
 * This class processes the standard cross reference of a PDF document.
 *
 * @package setasign\Fpdi\PdfParser\CrossReference
 */
class CrossReference {

	/**
	 * The byte length in which the "startxref" keyword should be searched.
	 *
	 * @var int
	 */
	public static $trailerSearchLength = 5500;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $fileHeaderOffset = 0;

	/**
	 * PdfParser
	 *
	 * @var PdfParser
	 */
	protected $parser;

	/**
	 * ReaderInterface
	 *
	 * @var ReaderInterface[]
	 */
	protected $readers = array();

	/**
	 * CrossReference constructor.
	 *
	 * @param PdfParser        $parser Get PdfParser.
	 * @param fileHeaderOffset $fileHeaderOffset Get fileHeaderOffset.
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	public function __construct( PdfParser $parser, $fileHeaderOffset = 0 ) {
		$this->parser           = $parser;
		$this->fileHeaderOffset = $fileHeaderOffset;

		$offset = $this->findStartXref();
		$reader = null;
		/** Noinspection TypeUnsafeComparisonInspection */
		while ( false !== $offset ) { // By doing an unsafe comparsion we ignore faulty references to byte offset 0.
			$reader  = $this->readXref( $offset + $this->fileHeaderOffset );
			$trailer = $reader->getTrailer();
			$this->checkForEncryption( $trailer );
			$this->readers[] = $reader;

			if ( isset( $trailer->value['Prev'] ) ) {
				$offset = $trailer->value['Prev']->value;
			} else {
				$offset = false;
			}
		}

		// fix faulty sub-section header.
		if ( $reader instanceof FixedReader ) {
			/**
			 * Fix faulty sub-section header.
			 *
			 * @var FixedReader $reader.
			 */
			$reader->fixFaultySubSectionShift();
		}

		if ( null === $reader ) {
			throw new CrossReferenceException( 'No cross-reference found.', CrossReferenceException::NO_XREF_FOUND );
		}
	}

	/**
	 * Get the size of the cross reference.
	 *
	 * @return integer
	 */
	public function getSize() {
		return $this->getTrailer()->value['Size']->value;
	}

	/**
	 * Get the trailer dictionary.
	 *
	 * @return PdfDictionary
	 */
	public function getTrailer() {
		return $this->readers[0]->getTrailer();
	}

	/**
	 * Get the cross reference readser instances.
	 *
	 * @return ReaderInterface[]
	 */
	public function getReaders() {
		return $this->readers;
	}

	/**
	 * Get the offset by an object number.
	 *
	 * @param int $objectNumber Get objectNumber.
	 * @return integer|bool
	 */
	public function getOffsetFor( $objectNumber ) {
		foreach ( $this->getReaders() as $reader ) {
			$offset = $reader->getOffsetFor( $objectNumber );
			if ( false !== $offset ) {
				return $offset;
			}
		}

		return false;
	}

	/**
	 * Get an indirect object by its object number.
	 *
	 * @param int $objectNumber Get objectNumber.
	 * @return PdfIndirectObject
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	public function getIndirectObject( $objectNumber ) {
		$offset = $this->getOffsetFor( $objectNumber );
		if ( false === $offset ) {
			throw new CrossReferenceException(
				\sprintf( 'Object (id:%s) not found.', $objectNumber ),
				CrossReferenceException::OBJECT_NOT_FOUND
			);
		}

		$parser = $this->parser;

		$parser->getTokenizer()->clearStack();
		$parser->getStreamReader()->reset( $offset + $this->fileHeaderOffset );

		try {
			/**
			 * PdfIndirectObject
			 *
			 * @var PdfIndirectObject $object
			 */
			$object = $parser->readValue( null, PdfIndirectObject::class );
		} catch ( PdfTypeException $e ) {
			throw new CrossReferenceException(
				\sprintf( 'Object (id:%s) not found at location (%s).', $objectNumber, $offset ),
				CrossReferenceException::OBJECT_NOT_FOUND,
				$e
			);
		}

		if ( $object->objectNumber !== $objectNumber ) {
			throw new CrossReferenceException(
				\sprintf( 'Wrong object found, got %s while %s was expected.', $object->objectNumber, $objectNumber ),
				CrossReferenceException::OBJECT_NOT_FOUND
			);
		}

		return $object;
	}

	/**
	 * Read the cross-reference table at a given offset.
	 *
	 * Internally the method will try to evaluate the best reader for this cross-reference.
	 *
	 * @param int $offset Get int value.
	 * @return ReaderInterface
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	protected function readXref( $offset ) {
		$this->parser->getStreamReader()->reset( $offset );
		$this->parser->getTokenizer()->clearStack();
		$initValue = $this->parser->readValue();

		return $this->initReaderInstance( $initValue );
	}

	/**
	 * Get a cross-reference reader instance.
	 *
	 * @param PdfToken|PdfIndirectObject $initValue Get PdfToken.
	 * @return ReaderInterface|bool
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	protected function initReaderInstance( $initValue ) {
		$position = $this->parser->getStreamReader()->getPosition()
			+ $this->parser->getStreamReader()->getOffset() + $this->fileHeaderOffset;

		if ( $initValue instanceof PdfToken && 'xref' === $initValue->value ) {
			try {
				return new FixedReader( $this->parser );
			} catch ( CrossReferenceException $e ) {
				$this->parser->getStreamReader()->reset( $position );
				$this->parser->getTokenizer()->clearStack();

				return new LineReader( $this->parser );
			}
		}

		if ( $initValue instanceof PdfIndirectObject ) {
			// check for encryption.
			$stream = PdfStream::ensure( $initValue->value );

			$type = PdfDictionary::get( $stream->value, 'Type' );
			if ( 'XRef' !== $type->value ) {
				throw new CrossReferenceException(
					'The xref position points to an incorrect object type.',
					CrossReferenceException::INVALID_DATA
				);
			}

			$this->checkForEncryption( $stream->value );

			throw new CrossReferenceException(
				'This PDF document probably uses a compression technique which is not supported by the ' .
				'free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)',
				CrossReferenceException::COMPRESSED_XREF
			);
		}

		throw new CrossReferenceException(
			'The xref position points to an incorrect object type.',
			CrossReferenceException::INVALID_DATA
		);
	}

	/**
	 * Check for encryption.
	 *
	 * @param PdfDictionary $dictionary Get dictionary.
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	protected function checkForEncryption( PdfDictionary $dictionary ) {
		if ( isset( $dictionary->value['Encrypt'] ) ) {
			throw new CrossReferenceException(
				'This PDF document is encrypted and cannot be processed with FPDI.',
				CrossReferenceException::ENCRYPTED
			);
		}
	}

	/**
	 * Find the start position for the first cross-reference.
	 *
	 * @return int The byte-offset position of the first cross-reference.
	 * @throws CrossReferenceException CrossReferenceException.
	 */
	protected function findStartXref() {
		$reader = $this->parser->getStreamReader();
		$reader->reset( -self::$trailerSearchLength, self::$trailerSearchLength );

		$buffer    = $reader->getBuffer( false );
		$pos       = \strrpos( $buffer, 'startxref' );
		$addOffset = 9;
		if ( false === $pos ) {
			// Some corrupted documents uses startref, instead of startxref.
			$pos = \strrpos( $buffer, 'startref' );
			if ( false === $pos ) {
				throw new CrossReferenceException(
					'Unable to find pointer to xref table',
					CrossReferenceException::NO_STARTXREF_FOUND
				);
			}
			$addOffset = 8;
		}

		$reader->setOffset( $pos + $addOffset );

		try {
			$value = $this->parser->readValue( null, PdfNumeric::class );
		} catch ( PdfTypeException $e ) {
			throw new CrossReferenceException(
				'Invalid data after startxref keyword.',
				CrossReferenceException::INVALID_DATA,
				$e
			);
		}

		return $value->value;
	}
}
