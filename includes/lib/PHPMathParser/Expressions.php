<?php
/**
 * Namespace PHPMathParser;
 *
 * @package Booster_For_WooCommerce/lib
 */

/**
 * WCJ_Parenthesis
 */
class WCJ_Parenthesis extends WCJ_TerminalExpression {

	protected $precedence = 6;
	/**
	 * WCJ_Parenthesis
	 */
	public function operate( WCJ_Stack $stack ) {
	}
	/**
	 * GetPrecedence
	 */
	public function getPrecedence() {
		return $this->precedence;
	}
	/**
	 * IsNoOp
	 */
	public function isNoOp() {
		return true;
	}
	/**
	 * IsParenthesis
	 */
	public function isParenthesis() {
		return true;
	}
	/**
	 * IsOpen
	 */
	public function isOpen() {
		return '(' === $this->value;
	}

}
	/**
	 * WCJ_Number
	 */
class WCJ_Number extends WCJ_TerminalExpression {
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		return $this->value;
	}

}
		/**
		 * WCJ_Operator
		 */
abstract class WCJ_Operator extends WCJ_TerminalExpression {

	protected $precedence = 0;
	protected $leftAssoc  = true;
		/**
		 * GetPrecedence
		 */
	public function getPrecedence() {
		return $this->precedence;
	}
		/**
		 * IsLeftAssoc
		 */
	public function isLeftAssoc() {
		return $this->leftAssoc;
	}
		/**
		 * IsOperator
		 */
	public function isOperator() {
		return true;
	}

}
		/**
		 * WCJ_Addition
		 */
class WCJ_Addition extends WCJ_Operator {

	protected $precedence = 4;
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		return $stack->pop()->operate( $stack ) + $stack->pop()->operate( $stack );
	}

}
		/**
		 * WCJ_Subtraction
		 */
class WCJ_Subtraction extends WCJ_Operator {

	protected $precedence = 4;
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		$left  = $stack->pop()->operate( $stack );
		$right = $stack->pop()->operate( $stack );
		return $right - $left;
	}

}
		/**
		 * WCJ_Multiplication
		 */
class WCJ_Multiplication extends WCJ_Operator {

	protected $precedence = 5;
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		return $stack->pop()->operate( $stack ) * $stack->pop()->operate( $stack );
	}

}
		/**
		 * WCJ_Division
		 */
class WCJ_Division extends WCJ_Operator {

	protected $precedence = 5;
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		$left  = $stack->pop()->operate( $stack );
		$right = $stack->pop()->operate( $stack );
		return $right / $left;
	}

}
		/**
		 * WCJ_Power
		 */
class WCJ_Power extends WCJ_Operator {

	protected $precedence = 5;
		/**
		 * Operate
		 */
	public function operate( WCJ_Stack $stack ) {
		$left  = $stack->pop()->operate( $stack );
		$right = $stack->pop()->operate( $stack );
		return pow( $left, $right );
	}
}
