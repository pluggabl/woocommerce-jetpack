<?php

//namespace PHPMathParser;

class Alg_Parenthesis extends Alg_TerminalExpression {

    protected $precedence = 6;

    public function operate(Alg_Stack $stack) {
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

class Alg_Number extends Alg_TerminalExpression {

    public function operate(Alg_Stack $stack) {
        return $this->value;
    }

}

abstract class Alg_Operator extends Alg_TerminalExpression {

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

class Alg_Addition extends Alg_Operator {

    protected $precedence = 4;

    public function operate(Alg_Stack $stack) {
        return $stack->pop()->operate($stack) + $stack->pop()->operate($stack);
    }

}

class Alg_Subtraction extends Alg_Operator {

    protected $precedence = 4;

    public function operate(Alg_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return $right - $left;
    }

}

class Alg_Multiplication extends Alg_Operator {

    protected $precedence = 5;

    public function operate(Alg_Stack $stack) {
        return $stack->pop()->operate($stack) * $stack->pop()->operate($stack);
    }

}

class Alg_Division extends Alg_Operator {

    protected $precedence = 5;

    public function operate(Alg_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return $right / $left;
    }

}

class Alg_Power extends Alg_Operator {

    protected $precedence = 5;

    public function operate(Alg_Stack $stack) {
        $left = $stack->pop()->operate($stack);
        $right = $stack->pop()->operate($stack);
        return pow($left,$right);
    }
}
