<?php

header('Content-type: text/plain');

include 'Math/Polynomial.php';
include 'Math/PolynomialOp.php';

print("\n-- Algebra --\n");
$p = new Math_Polynomial('3x^2 + 2x');
$q = new Math_Polynomial('4x + 1');
print('P is: ' . $p->toString() . "\n");
print('Q is: ' . $q->toString() . "\n");

$mul = Math_PolynomialOp::mul($p, $q); // Multiply p by q
print('P multiplied by Q is: ' . $mul->toString() . "\n"); // Print string representation

print('The degree of that result is: ' . $mul->degree() . "\n");
print('That result evaluated at x = 10 is: ' . number_format(Math_PolynomialOp::evaluate($mul, 10)) . "\n");

$sub = Math_PolynomialOp::sub($p, $q);
print('P minus Q is: ' . $sub->toString() . "\n");

$r = new Math_Polynomial('3x^3 - 5x^2 + 10x-3');
$s = new Math_Polynomial('3x+1');
$remainder = new Math_Polynomial();

print('R is: ' . $r->toString() . "\n");
print('S is: ' . $s->toString() . "\n");

$div = Math_PolynomialOp::div($r, $s, &$t);
print('R divided by S is: ' . $div->toString() . ' ( remainder of: ' . $remainder->toString() . ' )' . "\n");


print("\n-- Creating Polynomials --\n");
$roots = Math_PolynomialOp::createFromRoots(1, 2, -3);
print('Here is a polynomial with the roots 1, 2, and -3: ' . $roots->toString() . "\n");


print("\n-- Derivatives --\n");
print('f(x) is: ' . $p->toString() . "\n");

$der1 = Math_PolynomialOp::getDerivative($p);
print('f\'(x) is: ' . $der1->toString() . ' (first derivative)' . "\n");

$der2 = Math_PolynomialOp::getDerivative($p, 2);
print('f\'\'(x) is: ' . $der2->toString() . ' (second derivative)' . "\n");

print("\n");

?>