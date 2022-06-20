<?php
/**
 * Namespace PHPMathParser;
 *
 * @package Booster_For_WooCommerce/lib
 */

/**
 * WCJ_TerminalExpression
 */
abstract class WCJ_TerminalExpression {
	/**
	 * Value
	 */
	protected $value = '';
	/**
	 * Construct
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}
			/**
			 * Factory
			 */
	public static function factory( $value ) {

		if ( is_object( $value ) && $value instanceof WCJ_TerminalExpression ) {
			return $value;
		} elseif ( is_numeric( $value ) ) {
			return new WCJ_Number( $value );
		} elseif ( '+' === $value ) {
			return new WCJ_Addition( $value );
		} elseif ( '-' === $value ) {
			return new WCJ_Subtraction( $value );
		} elseif ( '*' === $value ) {
			return new WCJ_Multiplication( $value );
		} elseif ( '/' === $value ) {
			return new WCJ_Division( $value );
		} elseif ( in_array( $value, array( '(', ')' ), true ) ) {
			return new WCJ_Parenthesis( $value );
		} elseif ( '^' === $value ) {
			return new WCJ_Power( $value );
		}
		throw new Exception( 'Undefined Value ' . $value );
	}
					/**
					 * Operate
					 */
	abstract public function operate( WCJ_Stack $stack);
						/**
						 * IsOperator
						 */
	public function isOperator() {
		return false;
	}
						/**
						 * IsParenthesis
						 */
	public function isParenthesis() {
		return false;
	}
						/**
						 * IsNoOp
						 */
	public function isNoOp() {
		return false;
	}
						/**
						 * Render
						 */
	public function render() {
		return $this->value;
	}
}
