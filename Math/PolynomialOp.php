<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Static methods used to operate on Polynomial objects 
 * 
 * @see Math_Polynomial
 * 
 * @category Math
 * @package Math_Polynomial
 * @author Keith Palmer <Keith@UglySlug.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

if (!defined('MATH_POLYNOMIAL_ROUND_BOUNDARY')) {
	/**
	 * Rounding boundary for numbers that get multiplied together
	 * 
	 * If we multiply two numbers, 0.33333 and 3, should we round this result 
	 * to 1, or leave it as 0.99999? If the difference between 1 and 0.99999 is less 
	 * than $_round_boundary it will be rounded to 1, otherwise it will be left 
	 * as a float. 
	 * 
	 * This only gets defined if it's not defined already, allowing the user to 
	 * define this for themselves if they'd like better/worse precision. 
	 * Setting this lower gives better precision... setting it too low makes 
	 * some multiplication look funny and can make division fail. 
	 * 
	 * @var float
	 */
	define('MATH_POLYNOMIAL_ROUND_BOUNDARY', 0.0001);
}

/**
 * Flag indicating concave up 
 * @var integer
 */
define('MATH_POLYNOMIAL_CONCAVE_UP', 1);

/**
 * Flag indicating that this is a critical point
 * @var integer 
 */
define('MATH_POLYNOMIAL_CRITICAL_POINT', 2);

/**
 * Flag indicating concave down
 * @var integer
 */
define('MATH_POLYNOMIAL_CONCAVE_DOWN', 3);

/**
 * Flag indicating curve is increasing
 * @var integer
 */
define('MATH_POLYNOMIAL_INCREASING', 4);

/**
 * Flag indicating curve is decreasing
 * @var integer
 */
define('MATH_POLYNOMIAL_DECREASING', 5);

/**
 * 
 */
define('MATH_POLYNOMIAL_QUADRANT_1', 1);

/**
 * 
 */
define('MATH_POLYNOMIAL_QUADRANT_2', 2);

/**
 * 
 */
define('MATH_POLYNOMIAL_QUADRANT_3', 3);

/**
 * 
 */
define('MATH_POLYNOMIAL_QUADRANT_4', 4);

/**
 * Require Polynomial class definition
 */
require_once 'Math/Polynomial.php';

/**
 * Class for operations on Math_Polynomial objects
 * 
 * Mathematical operations on Polynomial objects. All class methods are static 
 * methods and take as parameters either a Polynomial object or a string 
 * respresentation of a Polynomial in the form of: 
 * 	ax^n + bx^(n-1) + cx^(n-2) + ... yx + z
 * 
 * @see Math_Polynomial
 * 
 * @package Math_Polynomial
 * @author Keith Palmer <Keith@UglySlug.com>
 */
class Math_PolynomialOp
{
	/**
	 * Create a Polynomial object from a string/integer/float
	 * 
	 * @access public
	 * 
	 * @param string $str
	 * @return object
	 */
	function create($str)
	{
		return new Math_Polynomial($str);
	}
	
	/**
	 * Add two Polynomials together and return the result
	 * 
	 * <code>
	 * $p = new Polynomial("x + 2");
	 * $res = PolynomialOp::add("x + 3", $p);
	 * print($res->toString()); // Prints 2x + 5 ( sum of the two )
	 * </code>
	 * 
	 * @see Math_Polynomial::subtract()
	 * 
	 * @access public
	 * 
	 * @param mixed $p1 String representation or Polynomial object
	 * @param mixed $p2
	 * @return object
	 */
	function &add($p1, $p2)
	{
		if (!is_a($p1, 'Math_Polynomial')) {
			$p1 = new Math_Polynomial($p1);
		}
		
		if (!is_a($p2, 'Math_Polynomial')) {
			$p2 = new Math_Polynomial($p2);
		}
		
		$res = new Math_Polynomial();
		
		$count = $p1->numTerms();
		for ($i = 0; $i < $count; $i++) {
			$res->addTerm($p1->getTerm($i));
		}
		
		$count = $p2->numTerms();
		for ($i = 0; $i < $count; $i++) {
			$res->addTerm($p2->getTerm($i));
		}
		
		return $res;
	} 
	
	/**
	 * Subtract one Polynomial from another Polynomial
	 * 
	 * @see Math_PolynomialOp::add()
	 * 
	 * @access public
	 * 
	 * @param mixed $p1 The initial Polynomial 
	 * @param mixed $p2 The Polynomial to subtract from the initial one
	 * @return object
	 */
	function &sub($p1, $p2)
	{
		if (!is_a($p1, 'Math_Polynomial')) {
			$p1 = new Math_Polynomial($p1);
		}
		
		if (!is_a($p2, 'Math_Polynomial')) {
			$p2 = new Math_Polynomial($p2);
		}
		
		$res = new Math_Polynomial($p1);
		
		$count = $p2->numTerms();
		for ($i = 0; $i < $count; $i++) { // Just place the terms in the polynomial, the _combineTerms() method will deal with the rest
			$term = $p2->getTerm($i);
			$res->addTerm(new Math_PolynomialTerm($term->getCoefficient() * -1.0, $term->getExponent()));
		}
		
		return $res;
	}
	
	/**
	 * Multiply two Polynomials together
	 * 
	 * The parameters may be either a Polynomial object or a string representation 
	 * of a polynomial. 
	 * 
	 * @access public
	 * 
	 * @param object $m1 Polynomial object or string representing polynomial
	 * @param object $m2
	 * @return object 
	 */
	function &mul($p1, $p2)
	{
		if (!is_a($p1, 'Math_Polynomial')) {
			$p1 = new Math_Polynomial($p1);
		}
		
		if (!is_a($p2, 'Math_Polynomial')) {
			$p2 = new Math_Polynomial($p2);
		}
		
		$res = new Math_Polynomial();
		
		$count_p1 = $p1->numTerms(); // Number of terms in each
		$count_p2 = $p2->numTerms();
		
		for ($i = 0; $i < $count_p1; $i++) { // For each term in the current polynomial
			for ($j = 0; $j < $count_p2; $j++) { // Multiply by each term ( Multiply coefficient, add exponents )
				$term_p1 = $p1->getTerm($i);
				$term_p2 = $p2->getTerm($j);
				
				$coef = $term_p1->getCoefficient() * $term_p2->getCoefficient(); 
				
				if (abs($coef - round($coef)) < MATH_POLYNOMIAL_ROUND_BOUNDARY) {
					$coef = round($coef);
				}
				
				$res->addTerm(new Math_PolynomialTerm($coef, $term_p1->getExponent() + $term_p2->getExponent()));
			}
		}
		
		return $res;
	}
	
	/**
	 * Divide one Polynomial by another, returning the result
	 * 
	 * Divide the first polynomial by another polynomial object or a string 
	 * represention of another polynomial. Optionally, you can pass another 
	 * Polynomial object by reference to store the remainder of the division 
	 * operator. 
	 * 
	 * <code>
	 * $a = new Polynomial("4x^2 + 2x");
	 * $b = new Polynomial("2x");
	 * $remainder = new Polynomial();
	 * $result = PolynomialOp::div($a, $b, $remainder);
	 * print("A divided by B is: " . $result->toString() . " with a remainder of " . $remainder->toString() . "\n");
	 * </code>
	 * 
	 * @access public
	 * 
	 * @param object $p1
	 * @param object $p2
	 * @param object $rem
	 * @return object
	 */
	function &div($p1, $p2, $rem = null)
	{
		if (!is_a($p1, 'Math_Polynomial')) {
			$p1 = new Math_Polynomial($p1);
		}
		
		if (!is_a($p2, 'Math_Polynomial')) {
			$p2 = new Math_Polynomial($p2);
		}
		
		if (Math_PolynomialOp::isZero($p2)) {
			$err = PEAR::raiseError('Divide by zero error in Math_PolynomialOp::div().');
			return $err;
		}
		
		if (is_null($rem)) {
			$remain = new Math_Polynomial();
		} else {
			if (!is_a($rem, 'Math_Polynomial')) {
				$err = PEAR::raiseError('Reference remainder parameter must be a Math_Polynomial object.');
				return $err;
			} else {
				$remain =& $rem;
			}
		}
		
		$res = new Math_Polynomial();
		
		// Copy terms in $p1 over to the remainder Polynomial
		$count = $p1->numTerms();
		for ($i = 0; $i < $count; $i++) {
			$remain->addTerm($p1->getTerm($i));
		}
		
		/*
		 * Method here is for each term in $p1, find out what we'd need to mult.
		 * the first term in $p2 by to get $p1. That term goes into the result, 
		 * do the multiplication and subtract that from $p1. That gets rid of 
		 * the terms 1 by 1 until we're done.
		 */
		while (true)
		{
			$term_remain = $remain->getTerm(0); // This will change on each loop as we subtract stuff out of $remain
			$term_p2 = $p2->getTerm(0); // This should stay constant, we're not changing $p2
			
			if ($term_p2->getExponent() > $remain->degree()) { // If the degree of b is larger than degree of a, there's no way b can be a factor...
				break;
			}
			
			// Next three lines could be shortened a bit...
			$div = new Math_PolynomialTerm($term_remain->getCoefficient() / $term_p2->getCoefficient(), $term_remain->getExponent() - $term_p2->getExponent());
			
			$div_poly = new Math_Polynomial();
			$div_poly->addTerm($div);
			
			$step_poly = Math_PolynomialOp::mul($p2, $div_poly);
			
			$remain = Math_PolynomialOp::sub($remain, $step_poly);
			
			$res->addTerm($div);
			
			if (Math_PolynomialOp::isZero($remain)) {
				break;
			}
		}
		
		return $res;
	}
	
	/**
	 * Calculate the mod (%) $p1 of $p2 and return the result as a Polynomial
	 * 
	 * <code>
	 * print('p1 % p2 is: ');
	 * $mod = Math_PolynomialOp::mod($p1, $p2);
	 * print($mod->toString());
	 * </code>
	 * 
	 * @param object $p1
	 * @param object $p2
	 * @return object
	 */
	function &mod($p1, $p2)
	{
		$remain = new Math_Polynomial();
		Math_PolynomialOp::div($p1, $p2, $remain);
		
		return $remain;
	}
	
	/**
	 * Evaluate the polynomial for a given x value
	 * 
	 * @access public
	 * 
	 * @param object $m1
	 * @param float $x The x to evaluate the polynomial at
	 * @return float The resulting value ( y value, or value of function f(x) )
	 */
	function evaluate($p, $x)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$val = 0.0; // End value
		$count = $p->numTerms();
		
		for ($i = 0; $i < $count; $i++) {
			$term = $p->getTerm($i);
			$val = $val + $term->getCoefficient() * pow($x, $term->getExponent());
		}
		
		return $val;
	}
	
	/**
	 * Create a Polynomial object which has roots (zeros) provided as parameters
	 * 
	 * The roots can be passed in as either a variable length parameter list or 
	 * a single array of float values.  
	 * 
	 * @access public
	 * 
	 * @param array $arr An array of roots
	 * @return object
	 */
	function &createFromRoots($arr)
	{
		if (is_array($arr)) { // An array of floats/integers
			
			$count = count($arr);
			if ($count > 0) {
				$res = new Math_Polynomial('x - ' . (float) $arr[0]); // Create the initial Polynomial with 0th index 
			}
			
			for ($i = 1; $i < $count; $i++) { // Start at 1, 0th index already used to create Polynomial
				$res = Math_PolynomialOp::mul($res, 'x - ' . (float) $arr[$i]);
			}
			
		} else { // Its a variable length parameter list of values
			
			$res = new Math_Polynomial('x - ' . (float) $arr);
			
			for ($i = 1; $i < func_num_args(); $i++) {
				$arg = (float) func_get_arg($i);
				$res = Math_PolynomialOp::mul($res, 'x - ' . $arg);
			}
		}
		
		return $res;
	}
	
	/**
	 * Get the roots of this Polynomial
	 * 
	 * For Polynomials of degree less than or equal to 4, the exact value of any 
	 * real roots (zeros) of the Polynomial are returned. For Polynomials of 
	 * higher degrees, the roots are estimated using the Newton-Raphson method 
	 * from the {@link http://pear.php.net/package/Math_Numerical_RootFinding/ 
	 * Math_Numerical_RootFinding} package. Remember that these roots are 
	 * *estimates* and for high-degree polynomials all of the roots may not be 
	 * calculated and returned!
	 * 
	 * If you're calculating roots for a higher-degree Polynomial and want to 
	 * provide the initial guesses for the roots, you can pass them in as an 
	 * array parameter. 
	 * 
	 * If possible, this function will return integers instead of floats. 
	 * 
	 * @see Math_PolynomialOp::getRootsLinear(), Math_PolynomialOp::getRootsQuadratic(), Math_PolynomialOp::getRootsCubic(), Math_PolynomialOp::getRootsQuartic(), Math_PolynomialOp::getRootsHighDegree(), Math_Numerical_RootFinding
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param array $guesses
	 * @return array An array of roots ( points where y = 0 )
	 */
	function getRoots($p, $guesses = array())
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		if ($p->degree() == 0) { // Constant
			return array();
		} else if ($p->degree() == 1) { // Linear
			return Math_PolynomialOp::getRootsLinear($p);
		} else if ($p->degree() == 2) { // Quadratic
			return Math_PolynomialOp::getRootsQuadratic($p);
		} else if ($p->degree() == 3) { // Cubic
			return Math_PolynomialOp::getRootsCubic($p);
		} else if ($p->degree() == 4) { // Quartic
			return Math_PolynomialOp::getRootsQuartic($p);	
		} else {
			return Math_PolynomialOp::getRootsHighDegree($p, $guesses);
		}
	}
	
	/**
	 * Round an array of integers or single integer if its within the round boundary
	 * 
	 * @access protected
	 * 
	 * @param mixed $mixed
	 * @return mixed  
	 */
	function _round($mixed)
	{
		if (is_array($mixed)) {
			foreach ($mixed as $key => $num) {
				if (abs(round($num) - $num) < MATH_POLYNOMIAL_ROUND_BOUNDARY) {
					$mixed[$key] = (int) round($num);
				}
			}
		} else if (abs(round($mixed) - $mixed) < MATH_POLYNOMIAL_ROUND_BOUNDARY) {
			return (int) round($mixed);
		}
		
		return $mixed;
	}
	
	/**
	 * Get the roots of a linear Polynomial
	 * 
	 * @see Math_PolynomialOp::getRoots()
	 * 
	 * @access public
	 * 
	 * @param object $m
	 * @return array 
	 */
	function getRootsLinear($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		/*
		 * Slope intercept form
		 * y = mx + b
		 * 0 = mx + b
		 * -b/m = x
		 */ 
		if ($p->degree() == 1) {
			
			$m = $p->getTerm(0);
			$m = $m->getCoefficient();
			
			$b = $p->getTerm(1);
			$b = $b->getCoefficient();
			
			return Math_PolynomialOp::_round(array((-1 * $b) / $m));
		} else {
			return PEAR::raiseError('Parameter to Math_PolynomialOp::getRootsLinear() is not linear.');
		}
	}
	
	/**
	 * Get the roots of a quadratic Polynomial (using the Quadratic Formula)
	 * 
	 * @see Math_Polynomial::getRoots()
	 * 
	 * @access public
	 * 
	 * @param object $m
	 * @return array
	 */
	function getRootsQuadratic($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		/*
		 * Quadratic Formula
		 * x = ( -b +- sqrt(b^2 - 4ac) ) / 2a 
		 */
		if ($p->degree() == 2) {
			$a = 0;
			$b = 0;
			$c = 0;
			
			$num_terms = $p->numTerms();
			for ($i = 0; $i < $num_terms; $i++) {
				
				$term = $p->getTerm($i);
				
				if ($term->getExponent() == 2) {
					$a = $term->getCoefficient();
				} else if ($term->getExponent() == 1) {
					$b = $term->getCoefficient();
				} else if ($term->getExponent() == 0) {
					$c = $term->getCoefficient();
				}
			}
			
			return Math_PolynomialOp::_round(array(((-1 * $b) + sqrt(pow($b, 2) - (4 * $a * $c))) / (2 * $a), ((-1 * $b) - sqrt(pow($b, 2) - (4 * $a * $c))) / (2 * $a)));
		} else {
			return PEAR::raiseError('Parameter to Math_PolynomialOp::getRootsQuadratic() is not quadratic.');
		}
	}
	
	/**
	 * Find and return the real roots of a cubic Polynomial using the cubic formula
	 * 
	 * @access public
	 * 
	 * @see Math_PolynomialOp::getRoots()
	 * 
	 * @param object $m
	 * @return array
	 */
	function getRootsCubic($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		/*
		 * Cubic equation solver
		 * Method from: http://www.1728.com/cubic2.htm 
		 */
		
		if ($p->degree() == 3) {
			$a = 0;
			$b = 0;
			$c = 0;
			$d = 0;
			
			$num_terms = $p->numTerms();
			for ($i = 0; $i < $num_terms; $i++) {
				
				$term = $p->getTerm($i);
				
				if ($term->getExponent() == 3) {
					$a = $term->getCoefficient();
				} else if ($term->getExponent() == 2) {
					$b = $term->getCoefficient();
				} else if ($term->getExponent() == 1) {
					$c = $term->getCoefficient();
				} else if ($term->getExponent() == 0) {
					$d = $term->getCoefficient();
				}
			}
			
			$arr = array();
			
			$f = ((3 * $c / $a) - pow($b, 2) / pow($a, 2)) / 3;
			$g = ((2 * pow($b, 3) / pow($a, 3)) - (9 * $b * $c / pow($a, 2)) + (27 * $d / $a)) / 27;
			$h = (pow($g, 2) / 4) + (pow($f, 3) / 27);
			
			if ($h > 0) { // Just one real root... :-(
				$r = -1 * ($g / 2) + sqrt($h);
				$s = pow($r, (1/3));
				$t = -1 * ($g / 2) - sqrt($h);
				$u = pow($t, (1/3));
				
				$arr[] = ($s + $u) - ($b / (3 * $a));
			} else if ($f == 0 && $g == 0 && $h == 0) { // All three roots are equal
				
				$arr[] = $arr[] = $arr[] = pow(($d / $a), (1/3)) * -1;
			} else { // Find the three roots
				$i = sqrt( (($g * $g) / 4) - $h );
				$j = pow($i, (1/3));
				$k = acos(-1 * ($g / (2 * $i)));
				$l = $j * -1;
				$m = cos($k / 3);
				$n = sqrt(3) * sin($k / 3);
				$p = ($b / (3 * $a)) * -1;
				
				$arr[] = 2 * $j * cos($k / 3) - ($b / (3 * $a));
				$arr[] = $l * ($m + $n) + $p;
				$arr[] = $l * ($m - $n) + $p;
			}
			
			return Math_PolynomialOp::_round($arr);
		} else {
			return PEAR::raiseError('Parameter to Math_PolynomialOp::getRootsCubic() is not cubic.');
		}
	}
	
	/**
	 * Find and return the roots of a Quartic Polynomial (degree 4) with the Quartic formula
	 * 
	 * @see Math_PolynomialOp::getRoots()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return array
	 */
	function getRootsQuartic($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		if ($p->degree() == 4) {
			$arr = array(); // Array of roots
			
			// Simplify it a bit first
			$a_term = $p->getTerm(0);
			$p = Math_PolynomialOp::div($p, $a_term->getCoefficient());
			
			$a = 0;
			$b = 0;
			$c = 0;
			$d = 0;
			$e = 0;
			
			$num_terms = $p->numTerms();
			for ($i = 0; $i < $num_terms; $i++) {
				
				$term = $p->getTerm($i);
				
				if ($term->getExponent() == 4) {
					$a = $term->getCoefficient();
				} else if ($term->getExponent() == 3) {
					$b = $term->getCoefficient();
				} else if ($term->getExponent() == 2) {
					$c = $term->getCoefficient();
				} else if ($term->getExponent() == 1) {
					$d = $term->getCoefficient();
				} else if ($term->getExponent() == 0) {
					$e = $term->getCoefficient();
				}
			}
			
			$f = $c - ((3 * $b * $b) / 8);
			$g = $d + (pow($b, 3) / 8) - (($b * $c) / 2);
			$h = $e - ((3 * pow($b, 4)) / 256) + (($b * $b) * ($c / 16)) - (($b * $d) / 4);
			
			$cubic = new Math_Polynomial('x^3 + ' . ($f / 2) . 'x^2 + ' . ((pow($f, 2) - (4 * $h)) / 16) . 'x - ' . (pow($g, 2) / 64));
			
			$p = 0;
			$q = 0;
			
			foreach (Math_PolynomialOp::getRootsCubic($cubic) as $p_or_q) {
				if ($p == 0 && $p_or_q != 0) {
					$p = sqrt($p_or_q);
				} else if ($q == 0 && $p_or_q != 0) {
					$q = sqrt($p_or_q);
				}
			}
			
			if ($p != 0 && $q != 0) {
				$r = (-1 * $g) / (8 * $p * $q);
				$s = $b / (4 * $a);
				
				$arr[] = $p + $q + $r - $s;
				$arr[] = $p - $q - $r - $s;
				$arr[] = (-1 * $p) + $q - $r - $s;
				$arr[] = (-1 * $p) - $q + $r - $s;
			}
			
			return Math_PolynomialOp::_round($arr);
		} else {
			return PEAR::raiseError('Parameter to Math_PolynomialOp::getRootsQuartic() is not quartic.');
		}
	}
	
	/**
	 * Return a Polynomial which is the negative value of the Polynomial
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return object
	 */
	/*function neg($p)
	{
		
	}*/
	
	/**
	 * Tell whether or not an object is a Polynomial or not
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return bool
	 */
	function isMath_Polynomial($p)
	{
		return is_a($p, 'Math_Polynomial');
	}
	
	/**
	 * Tell whether or not two Polynomials/string representations are equal
	 * 
	 * @access public
	 * 
	 * @param object $p1
	 * @param object $p2
	 * @return bool
	 */
	function equals($p1, $p2)
	{
		if (!is_a($p1, 'Math_Polynomial')) {
			$p1 = new Math_Polynomial($p1);
		}
		
		if (!is_a($p2, 'Math_Polynomial')) {
			$p2 = new Math_Polynomial($p2);
		}
		
		return $p1->toString() == $p2->toString();
	}
	
	/**
	 * Estimate and return the roots of a high-degree Polynomial ( degree 5 or greater )
	 * 
	 * This function uses Newton's method using the Math_Numerical_RootFinding 
	 * PEAR package to estimate the real roots of high-degree Polynomials. If 
	 * you already have estimates of where the roots might be, you can pass in 
	 * an array of guesses. Otherwise, the method will try to calculate some 
	 * good initial guesses for you.  
	 * 
	 * You must have the Math_Numerical_RootFinding package installed for this 
	 * method to work!
	 * 
	 * @see Math_PolynomialOp::getRoots(), Math_Numerical_RootFinding
	 * 
	 * @access public
	 * 
	 * @param object $m
	 * @param array $guesses
	 * @return array
	 */
	function getRootsHighDegree($p, $guesses = array())
	{
		require_once 'Math/Numerical/RootFinding.php';
		
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$arr = array(); // Array to store the roots
		
		$fx = Math_PolynomialOp::createFunction($p);
		$dx = Math_PolynomialOp::createFunction(Math_PolynomialOp::getDerivative($p));
		
		$newton = Math_Numerical_RootFinding::factory('Newtonraphson');
		if (PEAR::isError($newton)) {
			return PEAR::raiseError('Math_Numeric_RootFinding could not be instantiated, message was: ' . $newton->toString());
		}
		
		// We need to find some initial guesses for finding the roots
		if (count($guesses)) { // User has provided guesses for us
			foreach ($guesses as $guess) {
				$arr[] = $newton->compute($fx, $dx, $guess);
			}
		} else { // We need to find the guesses ourselves... yuck.
			
			$criticals = Math_PolynomialOp::getCriticalPoints($p);
			
			$arr[] = $newton->compute($fx, $dx, $criticals[0] - 0.1);
			
			$count = count($criticals);
			for ($i = 1; $i < $count; $i++) {
				$arr[] = $newton->compute($fx, $dx, ($criticals[$i - 1] + $criticals[$i]) / 2);
			}
			
			$arr[] = $newton->compute($fx, $dx, end($criticals) + 0.1);
			
			$arr = array_unique($arr);
		}
		
		return Math_PolynomialOp::_round($arr);
	}
	
	/**
	 * Calculate and return an array of critical points for the Polynomial
	 * 
	 * Critical points of a Polynomial are where something 'important' happens 
	 * in the Polynomial (inflection point, maximum, minumum, etc.) 
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return array
	 */
	function getCriticalPoints($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		return Math_PolynomialOp::getRoots(Math_PolynomialOp::getDerivative($p));
	}
	
	/**
	 * Create a lambda-style anonymous function from the Polynomial
	 * 
	 * Creates an anonymous function representing the Polynomial which takes one 
	 * parameter, an x value to evaluate at, and returns a unique name for the 
	 * function. 
	 * 
	 * @access public 
	 * 
	 * @see create_function(), Math_PolynomialOp::evaluate(), Math_PolynomialOp::createTangentFunction()
	 * 
	 * @param object $p Polynomial object or string representing a Polynomial
	 * @return string
	 */
	function createFunction($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
			
		$code = '';
		
		$count = $p->numTerms();
		for ($i = 0; $i < $count; $i++) {
			$term = $p->getTerm($i);
			
			$code = $code . ' + (' . $term->getCoefficient() . ' * pow($x, ' . $term->getExponent() . ')) ';
		}
		
		if (strlen($code)) {
			return create_function('$x', 'return ' . substr($code, 2) . ';');
		} else {
			return create_function('$x', 'return 0;');
		}
	}
	
	/**
	 * Find and return an array of the local maximums of the Polynomial
	 * 
	 * By default the function returns all local maximums for the Polynomial. 
	 * If you want just maximums on an interval, pass in the $x_min and $x_max 
	 * parameters. 
	 * 
	 * @access public
	 * 
	 * @see Math_PolynomialOp::getLocalMaximums()
	 * 
	 * @param object $p
	 * @param float $x_min
	 * @param float $x_max
	 * @return array
	 */
	function getLocalMaximums($p, $x_min = null, $x_max = null)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$maxs = array();
		$der = Math_PolynomialOp::getDerivative($p);
		
		foreach (Math_PolynomialOp::getRoots($der) as $critical) { 
			if (Math_PolynomialOp::evaluate($der, $critical - 0.1) > 0 && Math_PolynomialOp::evaluate($der, $critical + 0.1) < 0) { // Check if its a max.
				$maxs[] = $critical;
			}
		}
		
		if ($x_min && $x_max) { // Limit to just on the interval
			foreach ($maxs as $key => $max) {
				if ($max < $x_min || $max > $x_max) {
					unset($maxs[$key]);
				}
			}
		}
		
		return $maxs;
	}
	
	/**
	 * Find and return an array of the minimums of the Polynomial
	 * 
	 * By default the method returns all minimums, if you want the minimums 
	 * within an interval, pass in the $x_min and $x_max parameters. 
	 * 
	 * @access public
	 * 
	 * @see Math_PolynomialOp::getLocalMaximums()
	 * 
	 * @param object $p
	 * @param float $x_min
	 * @param float $x_max
	 * @return array
	 */
	function getLocalMinimums($p, $x_min = null, $x_max = null)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$mins = array();
		$der = Math_PolynomialOp::getDerivative($p);
		
		foreach (Math_PolynomialOp::getRoots($der) as $critical) {
			if (Math_PolynomialOp::evaluate($der, $critical - 0.1) < 0 && Math_PolynomialOp::evaluate($der, $critical + 0.1) > 0) { // Check if its a min.
				$mins[] = $critical;
			}
		}
		
		if ($x_min && $x_max) {
			foreach ($mins as $key => $min) {
				if ($min < $x_min || $min > $x_max) {
					unset($mins[$key]);
				}
			}
		}
		
		return $mins;
	}
	
	/**
	 * Get a Polynomial object representing a tangent to the given Polynomial at the given point
	 * 
	 * @see Math_PolynomialOp::getSlopeAt()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x
	 * @return object
	 */
	function getTangentAt($p, $x)
	{
		// y = mx + b
		// 
		// m = getSlope($x)
		// y = evaluate($x);
		// b = y / mx
		
		$m = Math_PolynomialOp::getSlopeAt($p, $x);
		$y = Math_PolynomialOp::evaluate($p, $x);
		$b = $y - ($m * $x);
		
		return new Math_Polynomial($m . 'x + ' . $b);
	}
	
	/**
	 * Create a lambda-style function representing the tangent line at a point
	 * 
	 * @see create_function(), Math_PolynomialOp::createFunction()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x
	 * @return string
	 */
	function createTangentFunction($p, $x)
	{
		$m = Math_PolynomialOp::getSlopeAt($p, $x);
		$y = Math_PolynomialOp::evaluate($p, $x);
		$b = $y - ($m * $x);
		
		return create_function('$x', 'return (' . $m . ' * $x) + ' . $b . ';');
	}
	
	/**
	 * Get a Math_Polynomial object representing the secant line through two points on the given Polynomial
	 * 
	 * @see Math_PolynomialOp::getSecantSlopeAt()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x1
	 * @param float $x2
	 * @return object
	 */
	function getSecantAt($p, $x1, $x2)
	{
		// y = m(x - x1) + y1
		// 
		// m = getSecantSlopeAt($x1, $x2)
		// y1 = evaluate($x1)
		
		$m = Math_PolynomialOp::getSecantSlopeAt($p, $x1, $x2);
		$y1 = Math_PolynomialOp::evaluate($p, $x1);
		$b = ((-1 * $m * $x1) + $y1);
		
		return new Math_Polynomial($m . 'x + ' . $b);
	}
	
	/**
	 * Create a lambda-style function representing the secant line through two points
	 * 
	 * @see create_function(), Math_PolynomialOp::createFunction()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x1
	 * @param float $x2
	 * @return string
	 */
	function createSecantFunction($p, $x1, $x2)
	{
		$m = Math_PolynomialOp::getSecantSlopeAt($p, $x1, $x2);
		$y1 = Math_PolynomialOp::evaluate($p, $x1);
		$b = ((-1 * $m * $x1) + $y1);
		
		return create_function('$x', 'return (' . $m . ' * $x) + ' . $b . ';');
	}
	
	/*
	function getInflectionPoints($p)
	{
		
	}
	
	function getIncreasingDecreasingAt($p, $x)
	{
	
	}
	*/
	
	/**
	 * Get the slope of the Polynomial at a given x value
	 * 
	 * @see Math_PolynomialOp::getTangentAt()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x
	 * @return float
	 */
	function getSlopeAt($p, $x)
	{
		return Math_PolynomialOp::evaluate(Math_PolynomialOp::getDerivative($p), $x);
	}
	
	/**
	 * Alias of {@link Math_PolynomialOp::getSlopeAt()}
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x
	 * @return float
	 */
	function getTangentSlopeAt($p, $x)
	{
		return Math_PolynomialOp::getSlopeAt($p, $x);
	}
	
	/**
	 * Get the slope of a secant to the Polynomial passing through points x1 and x2
	 * 
	 * @see Math_PolynomialOp::getTangentSlopeAt(), Math_PolynomialOp::getSecantAt()
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @param float $x1
	 * @param float $x2
	 * @return float
	 */
	function getSecantSlopeAt($p, $x1, $x2)
	{
		// y2 - y1 / x2 - x1
		
		$y1 = Math_PolynomialOp::evaluate($p, $x1);
		$y2 = Math_PolynomialOp::evaluate($p, $x2);
		
		return ($y2 - $y1) / ($x2 - $x1);
	}
	
	/**
	 * Test if a Math_Polynomial object is an even function (i.e.: f(-x) == f(x) for all x)
	 * 
	 * @see Math_PolynomialOp::isOdd()
	 * 
	 * @access public
	 * 
	 * @param mixed $p
	 * @param integer $num_test_points
	 * @return bool
	 */
	function isEven($p, $num_test_points = 10)
	{
		// f(-x) == f(x) symmetry about y axis
		
		$func = Math_PolynomialOp::createFunction($p);
		for ($i = 0; $i < $num_test_points; $i++) {
			$x = rand();
			
			if ($func($x) != $func(-1 * $x)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Test if a Math_Polynomial object is an odd function (i.e.: f(-x) == -f(x) for all x)
	 * 
	 * @see Math_PolynomialOp::isEven()
	 * 
	 * @access public
	 * 
	 * @param mixed $p
	 * @param integer $num_test_points
	 * @return bool
	 */
	function isOdd($p, $num_test_points = 10)
	{
		// f(-x) == -f(x) symmetry about origin/x=y
		
		$func = Math_PolynomialOp::createFunction($p);
		for ($i = 0; $i < $num_test_points; $i++) {
			$x = rand();
			
			if ($func(-1 * $x) != (-1 * $func($x))) {
				return false;
			}
		}
		
		return true;
	}
	
	/*
	function getConcavityAt($p, $x)
	{
	
	}
	*/

	/**
	 * Get the end behavoir of a Polynomial
	 * 
	 * @see Math_PolynomialOp::getRightEndBehavior()
	 * 
	 * @param object $p
	 * @return integer
	 */	
	/*function getLeftEndBehavior($p)
	{
		// a is the leading coefficient
		// n is the degree of the polynomial
		// - If a is positive and n is even, the right end of the polynomial is in quadrant I while the left end is in quadrant II.
		// - If a is negative and n is even, the right end is in quadrant IV while the left end is in quadrant III.
		// - If a is positive and n is odd, the right end is in quadrant I while the left end is in quadrant III.
		// - If a is negative and n is odd, the right end is in quadrant IV while the left end is in quadrant II.
		
		WRITE TESTS FOR GETLEFTENDBEHAVOIOR!
		
		WRITE TESTS FOR ISODD AND ISEVEN AS WELL!
		
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
	}*/
	
	/**
	 * Get the end behavior of a Polynomial
	 * 
	 * The end behaviors are determined by the degree of the Polynomial and 
	 * whether or not the coefficient is positive or negative. The values 
	 * returned correspond to the MATH_POLYNOMIAL_QUADRANT_* constants for the 
	 * cartesian coordinate system:
	 * <pre>
	 * 	Quad 2 | Quad 1
	 *  ---------------
	 * 	Quad 3 | Quad 4
	 * </pre>
	 * 
	 * Returns an array containing two elements: 
	 * <code>
	 * 	$end_behaviors = Math_PolynomialOp::getEndBehavior('x^2 + 1');
	 * 	print_r($end_behaviors);
	 * 
	 * 	// prints: 
	 * 	Array
	 * 	(
	 * 		[0] => MATH_POLYNOMAIL_QUADRANT_2 // This is the left-end behavior
	 * 		[1] => MATH_POLYNOMIAL_QUADRANT_1 // This is the right-end behavior
	 * 	)
	 * </code>
	 * 
	 * @see Math_PolynomialOp::getRightEndBehavior()
	 * @see Math_PolynomialOp::getLeftEndBehavior()
	 * 
	 * @access public
	 * @static
	 * 
	 * @param object $p
	 * @return array
	 */
	function getEndBehavior($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$term = $p->getTerm(0);
		if ($term->getCoefficient() > 0) { // Positive leading coefficient
			if ($p->degree() % 2) { // Odd degree
				return array(MATH_POLYNOMIAL_QUADRANT_3, MATH_POLYNOMIAL_QUADRANT_1);
			} else { // Even degree
				return array(MATH_POLYNOMIAL_QUADRANT_2, MATH_POLYNOMIAL_QUADRANT_1);
			}
		} else { // Negative leading coefficient
			if ($p->degree() %2) { // Odd degree
				return array(MATH_POLYNOMIAL_QUADRANT_2, MATH_POLYNOMIAL_QUADRANT_4);
			} else { // Even degree
				return array(MATH_POLYNOMIAL_QUADRANT_3, MATH_POLYNOMIAL_QUADRANT_4);
			}
		}
	}
	
	/*
	function getRightEndBehavior()
	{
		
	}
	*/
	
	/**
	 * Get the nth anti-derivative of a Math_Polynomial
	 * 
	 * Returns the nth anti-derivative of the Polynomial. An optional constant can 
	 * be passed in to have that appended as the x^0 term. 
	 * 
	 * @see PolynomialOp::getDerivative()
	 * 
	 * @access public
	 * @static
	 * 
	 * @param object $p
	 * @param integer $n
	 * @param integer $c
	 */
	function &getAntiDerivative($p, $n = 1, $c = 0)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$result = new Math_Polynomial(); // This will store the Polynomial's derivative
		$count = $p->numTerms();
		
		for ($i = 0; $i < $count; $i++) { // For each term, divide coefficient by exponent + 1, add one to exponent
			$term = $p->getTerm($i);
			$der_term = new Math_PolynomialTerm($term->getCoefficient() / ($term->getExponent() + 1), $term->getExponent() + 1);
			$result->addTerm($der_term);
		}
		
		for ($i = 0; $i < ($n - 1); $i++) { // If we want other than the 1st derivative, keep going...
			$result = Math_PolynomialOp::getAntiDerivative($result);
		}
		
		if ($c) {
			$result->addTerm(new Math_PolynomialTerm($c, 0));
		}
		
		return $result;
		
	}
	
	/*
	function areaBoundedBy($p, $x_min, $x_max, $y = 0)
	{
		// $y could be either a constant y value, or a Math_Polynomial object
		// If constant, just subtract block, otherwise subtract area Polynomial takes up... 
		//	(area bounded by two curves)
		
	}
	*/
	
	/**
	 * Get the nth derivative of a Math_Polynomial
	 * 
	 * Returns the nth derivative of the Polynomial. Derivatives are commonly 
	 * used in calculus as they represent slopes or acceleration. To get the 
	 * first derivative, the second parameter should be a 1. For the second 
	 * derivative parameter should be a two, etc. etc. 
	 * 
	 * @see PolynomialOp::getAntiDerivative()
	 * 
	 * @access public
	 * 
	 * @param object $p The Polynomial object
	 * @param integer $der_num The derivative you want (1 = 1st, 2 = 2nd, etc.)
	 * 
	 * @return object A polynomial object representing the nth derivative
	 */
	function &getDerivative($p, $n = 1)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		$result = new Math_Polynomial(); // This will store the Polynomial's derivative
		$count = $p->numTerms();
		
		for ($i = 0; $i < $count; $i++) { // For each term, multiply coefficient by exponent, subtract 1 from exponent
			$term = $p->getTerm($i);
			$der_term = new Math_PolynomialTerm($term->getCoefficient() * $term->getExponent(), $term->getExponent() - 1);
			$result->addTerm($der_term);
		}
		
		for ($i = 0; $i < ($n - 1); $i++) { // If we want other than the 1st derivative, keep going...
			$result = Math_PolynomialOp::getDerivative($result);
			
			if (Math_PolynomialOp::isZero($result)) { // If derivative is ever zero, every derivative thereafter is also 0
				return $result;
			}
		}
		
		return $result;
	}
	
	/**
	 * Tells whether or not the Polynomial is equivalent to zero
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return bool TRUE if its equal to 0, FALSE if it is not 
	 */
	function isZero($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		if ($p->numTerms() == 1) {
			$term = $p->getTerm(0);
			if ($term->getCoefficient() == 0) {
				return true;
			}
		} else if ($p->numTerms() == 0) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Tell whether or not a Polynomial is constant (degree 0)
	 * 
	 * @access public
	 * 
	 * @param object $p
	 * @return bool
	 */
	function isConstant($p)
	{
		if (!is_a($p, 'Math_Polynomial')) {
			$p = new Math_Polynomial($p);
		}
		
		if ($p->numTerms() == 1) {
			$term = $p->getTerm(0);
			if ($term->getExponent() == 0) {
				return true;
			}
		} else if ($p->numTerms() == 0) {
			return true;
		}
		
		return false;
	}
}

?>