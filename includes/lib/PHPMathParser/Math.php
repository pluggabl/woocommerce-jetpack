<?php
/**
 * Namespace PHPMathParser;
 *
 * @package Booster_For_WooCommerce/lib
 */
require_once 'Stack.php';
require_once 'TerminalExpression.php';
require_once 'Expressions.php';

/**
 * WCJ_Math
 */
class WCJ_Math {


	protected $variables = array();
	/**
	 * Evaluate
	 */
	public function evaluate( $string ) {
		$stack = $this->parse( $string );
		return $this->run( $stack );
	}
	/**
	 * Parse
	 */
	public function parse( $string ) {
		$tokens    = $this->tokenize( $string );
		$output    = new WCJ_Stack();
		$operators = new WCJ_Stack();
		foreach ( $tokens as $token ) {
			$token      = $this->extractVariables( $token );
			$expression = WCJ_TerminalExpression::factory( $token );
			if ( $expression->isOperator() ) {
				$this->parseOperator( $expression, $output, $operators );
			} elseif ( $expression->isParenthesis() ) {
				$this->parseParenthesis( $expression, $output, $operators );
			} else {
				$output->push( $expression );
			}
		}
		$op = $operators->pop();
		while ( ( $op ) ) {
			if ( $op->isParenthesis() ) {
				throw new RuntimeException( 'Mismatched Parenthesis' );
			}
			$output->push( $op );
		}
		return $output;
	}
	/**
	 * RegisterVariable
	 */
	public function registerVariable( $name, $value ) {
		$this->variables[ $name ] = $value;
	}
	/**
	 * Run
	 */
	public function run( WCJ_Stack $stack ) {
		$operator = $stack->pop();
		while ( ( $operator ) && $operator->isOperator() ) {
			$value = $operator->operate( $stack );
			if ( ! is_null( $value ) ) {
				$stack->push( WCJ_TerminalExpression::factory( $value ) );
			}
		}
		return $operator ? $operator->render() : $this->render( $stack );
	}
				/**
				 * ExtractVariables
				 */
	protected function extractVariables( $token ) {
		if ( '$' === $token[0] ) {
			$key = substr( $token, 1 );
			return isset( $this->variables[ $key ] ) ? $this->variables[ $key ] : 0;
		}
		return $token;
	}
				/**
				 * Render
				 */
	protected function render( WCJ_Stack $stack ) {
		$output = '';
		$el     = $stack->pop();
		while ( ( $el ) ) {
			$output .= $el->render();
		}
		if ( $output ) {
			return $output;
		}
		throw new RuntimeException( 'Could not render output' );
	}
					/**
					 * ParseParenthesis
					 */
	protected function parseParenthesis( WCJ_TerminalExpression $expression, WCJ_Stack $output, WCJ_Stack $operators ) {
		if ( $expression->isOpen() ) {
			$operators->push( $expression );
		} else {
			$clean = false;
			$end   = $operators->pop();
			while ( ( $end ) ) {
				if ( $end->isParenthesis() ) {
					$clean = true;
					break;
				} else {
					$output->push( $end );
				}
			}
			if ( ! $clean ) {
				throw new RuntimeException( 'Mismatched Parenthesis' );
			}
		}
	}
						/**
						 * ParseOperator
						 */
	protected function parseOperator( WCJ_TerminalExpression $expression, WCJ_Stack $output, WCJ_Stack $operators ) {
		$end = $operators->poke();
		if ( ! $end ) {
			$operators->push( $expression );
		} elseif ( $end->isOperator() ) {
			do {
				if ( $expression->isLeftAssoc() && $expression->getPrecedence() <= $end->getPrecedence() ) {
					$output->push( $operators->pop() );
				} elseif ( ! $expression->isLeftAssoc() && $expression->getPrecedence() < $end->getPrecedence() ) {
					$output->push( $operators->pop() );
				} else {
					break;
				}
				$end = $operators->poke();
			} while ( ( $end ) && $end->isOperator() );
			$operators->push( $expression );
		} else {
			$operators->push( $expression );
		}
	}
							/**
							 * Tokenize
							 */
	protected function tokenize( $string ) {
		$parts = preg_split( '((\f+|\+|-|\(|\)|\*|\^|/)|\s+)', $string, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$parts = array_map( 'trim', $parts );
		return $parts;
	}

}
