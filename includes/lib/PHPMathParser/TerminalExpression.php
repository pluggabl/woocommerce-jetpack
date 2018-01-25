<?php

//namespace PHPMathParser;

abstract class WCJ_TerminalExpression {

    protected $value = '';

    public function __construct($value) {
        $this->value = $value;
    }

    public static function factory($value) {

//        var_dump($value);
        if (is_object($value) && $value instanceof WCJ_TerminalExpression) {
            return $value;
        } elseif (is_numeric($value)) {
            return new WCJ_Number($value);
        } elseif ($value == '+') {
            return new WCJ_Addition($value);
        } elseif ($value == '-') {
            return new WCJ_Subtraction($value);
        } elseif ($value == '*') {
            return new WCJ_Multiplication($value);
        } elseif ($value == '/') {
            return new WCJ_Division($value);
        } elseif (in_array($value, array('(', ')'))) {
            return new WCJ_Parenthesis($value);
        } elseif ($value == '^') {
            return new WCJ_Power($value);
        }
        throw new Exception('Undefined Value ' . $value);
    }

    abstract public function operate(WCJ_Stack $stack);

    public function isOperator() {
        return false;
    }

    public function isParenthesis() {
        return false;
    }

    public function isNoOp() {
        return false;
    }

    public function render() {
        return $this->value;
    }
}
