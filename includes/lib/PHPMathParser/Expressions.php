<?php

//namespace PHPMathParser;

class WCJ_Parenthesis extends WCJ_TerminalExpression {

    protected $precedence = 6;

    public function operate(WCJ_Stack $stack) {
    }

    public function getPrecedence() {
        return $this->precedence;
    }

    public function isNoOp() {
        return true;
    }

    public function isParenthesis() {
        return true;
    }

    public function isOpen() {
        return $this->value == '(';
    }

}

class WCJ_Number extends WCJ_TerminalExpression {

    public function operate(WCJ_Stack $stack) {
        return $this->value;
    }

}

abstract class WCJ_Operator extends WCJ_TerminalExpression {

    protected $precedence = 0;
    protected $leftAssoc = true;

    public function getPrecedence() {
        return $this->precedence;
    }

    public function isLeftAssoc() {
        return $this->leftAssoc;
    }

    public function isOperator() {
        return true;
    }

}

class WCJ_Addition extends WCJ_Operator {

    protected $precedence = 4;

    public function operate(WCJ_Stack $stack) {
        return $stack->pop()->operate($stack) + $stack->pop()->operate($stack);
    }

}

class WCJ_Subtraction extends WCJ_Operator {

    protected $precedence = 4;

    public function operate(WCJ_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return $right - $left;
    }

}

class WCJ_Multiplication extends WCJ_Operator {

    protected $precedence = 5;

    public function operate(WCJ_Stack $stack) {
        return $stack->pop()->operate($stack) * $stack->pop()->operate($stack);
    }

}

class WCJ_Division extends WCJ_Operator {

    protected $precedence = 5;

    public function operate(WCJ_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return $right / $left;
    }

}

class WCJ_Power extends WCJ_Operator {

    protected $precedence = 5;

    public function operate(WCJ_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return pow($left,$right);
    }
}
