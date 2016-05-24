<?php

//namespace PHPMathParser;

abstract class Alg_TerminalExpression {

    protected $value = '';

    public function __construct($value) {
        $this->value = $value;
    }

    public static function factory($value) {

//        var_dump($value);
        if (is_object($value) && $value instanceof Alg_TerminalExpression) {
            return $value;
        } elseif (is_numeric($value)) {
            return new Alg_Number($value);
        } elseif ($value == '+') {
            return new Alg_Addition($value);
        } elseif ($value == '-') {
            return new Alg_Subtraction($value);
        } elseif ($value == '*') {
            return new Alg_Multiplication($value);
        } elseif ($value == '/') {
            return new Alg_Division($value);
        } elseif (in_array($value, array('(', ')'))) {
            return new Alg_Parenthesis($value);
        } elseif ($value == '^') {
            return new Alg_Power($value);
        }
        throw new Exception('Undefined Value ' . $value);
    }

    abstract public function operate(Alg_Stack $stack);

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
