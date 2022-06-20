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
 * Class for handling LZW encoded data
 *
 * @package setasign\Fpdi\PdfParser\Filter
 */
class Lzw implements FilterInterface {

	/**
	 * Data
	 *
	 * @var null|string
	 */
	protected $data;

	/**
	 * Array
	 *
	 * @var array
	 */
	protected $sTable = array();

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $dataLength = 0;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $tIdx;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $bitsToGet = 9;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $bytePointer;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $nextData = 0;

	/**
	 * Int
	 *
	 * @var int
	 */
	protected $nextBits = 0;

	/**
	 * Int
	 *
	 * @var array
	 */
	protected $andTable = array( 511, 1023, 2047, 4095 );

	/**
	 * Method to decode LZW compressed data.
	 *
	 * @param string $data The compressed data.
	 * @return string The uncompressed data
	 * @throws LzwException LzwException.
	 */
	public function decode( $data ) {
		if ( "\x00" === $data[0] && "\x01" === $data[1] ) {
			throw new LzwException(
				'LZW flavour not supported.',
				LzwException::LZW_FLAVOUR_NOT_SUPPORTED
			);
		}

		$this->initsTable();

		$this->data       = $data;
		$this->dataLength = \strlen( $data );

		// Initialize pointers.
		$this->bytePointer = 0;

		$this->nextData = 0;
		$this->nextBits = 0;

		$oldCode = 0;

		$uncompData = '';
		$code       = $this->getNextCode();
		while ( ( $code ) !== 257 ) {
			if ( 256 === $code ) {
				$this->initsTable();
				$code = $this->getNextCode();

				if ( 257 === $code ) {
					break;
				}

				$uncompData .= $this->sTable[ $code ];
				$oldCode     = $code;

			} else {
				if ( $code < $this->tIdx ) {
					$string      = $this->sTable[ $code ];
					$uncompData .= $string;

					$this->addStringToTable( $this->sTable[ $oldCode ], $string[0] );
					$oldCode = $code;
				} else {
					$string      = $this->sTable[ $oldCode ];
					$string     .= $string[0];
					$uncompData .= $string;

					$this->addStringToTable( $string );
					$oldCode = $code;
				}
			}
		}

		return $uncompData;
	}

	/**
	 * Initialize the string table.
	 */
	protected function initsTable() {
		$this->sTable = array();

		for ( $i = 0; $i < 256; $i++ ) {
			$this->sTable[ $i ] = \chr( $i );
		}

		$this->tIdx      = 258;
		$this->bitsToGet = 9;
	}

	/**
	 * Add a new string to the string table.
	 *
	 * @param string $oldString Get oldString.
	 * @param string $newString Get newString.
	 */
	protected function addStringToTable( $oldString, $newString = '' ) {
		$string = $oldString . $newString;

		// Add this new String to the table.
		$this->sTable[ $this->tIdx++ ] = $string;

		if ( 511 === $this->tIdx ) {
			$this->bitsToGet = 10;
		} elseif ( 1023 === $this->tIdx ) {
			$this->bitsToGet = 11;
		} elseif ( 2047 === $this->tIdx ) {
			$this->bitsToGet = 12;
		}
	}

	/**
	 * Returns the next 9, 10, 11 or 12 bits.
	 *
	 * @return integer
	 */
	protected function getNextCode() {
		if ( $this->bytePointer === $this->dataLength ) {
			return 257;
		}

		$this->nextData  = ( $this->nextData << 8 ) | ( \ord( $this->data[ $this->bytePointer++ ] ) & 0xff );
		$this->nextBits += 8;

		if ( $this->nextBits < $this->bitsToGet ) {
			$this->nextData  = ( $this->nextData << 8 ) | ( \ord( $this->data[ $this->bytePointer++ ] ) & 0xff );
			$this->nextBits += 8;
		}

		$code            = ( $this->nextData >> ( $this->nextBits - $this->bitsToGet ) ) & $this->andTable[ $this->bitsToGet - 9 ];
		$this->nextBits -= $this->bitsToGet;

		return $code;
	}
}
