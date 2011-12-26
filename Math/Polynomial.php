<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class representing Polynomial equations
 * 
 * The Math_Polynomial class represents simple polynomials of the form: 
 * 	ax^n + bx^(n-1) + cx^(n-2) + ... yx + z
 * 
 * Coefficients can be doubles or floats, exponents should be integers!
 * 
 * @see Math_PolynomialOp
 * @see Math_PolynomialTerm
 * @see Polynomial_examples.php
 * 
 * @category Math
 * @package Math_Polynomial
 * @author Keith Palmer <Keith@UglySlug.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Requires PolynomialTerm class to represent individual terms of the Polynomial
 */
require_once 'Math/Polynomial/PolynomialTerm.php';

/**
 * Require PEAR for PEAR errors
 */
require_once 'PEAR.php';

/**
 * Polynomial class 
 * 
 * The Math_Polynomial class represents simple polynomials of the form: 
 * 	ax^n + bx^(n-1) + cx^(n-2) + ... yx + z
 * 
 * Methods having a Polynomial or mixed type as a parameter should be able
 * to take either a string representation of a polynomial or a Polynomial
 * object as a parameter. Simple algebraic operations on polynomials are
 * supported using the following methods: 
 * 	- add() Add another polynomial to the current polynomial
 * 	- subtract() Subtract another polynomial from the current polynomial
 * 	- multiply() Multiply the current polynomial by another polynomial
 * 	- divide() Divide the current polynomial by another polynomial 
 * 	- mod() Perform the 'mod' function ( get the remainder of poly. division )
 * 
 * Other operations include: 
 * 	- getDerivative(x) Get the nth derivative of the Polynomial
 * 	- degree() Get the degree ( highest exponent )
 * 	- evaluate(x) Evaluate the polynomial at a given x value
 * 
 * String representation of the Polynomial object can be retrieved with the 
 * toString() method. 
 * 
 * @package Math_Polynomial
 * @author Keith Palmer <keith@AcademicKeys.com>
 */
class Math_Polynomial
{
	/**
	 * An array of PolynomialTerm objects
	 * 
	 * @var array
	 * @access protected
	 */
	var $_terms; 
    
	/**
	 * Whether or not Polynomial may contain multiple terms of the same degree
	 * 
	 * @var bool
	 * @access protected
	 */
	var $_needs_combining;
    
	/**
	 * Whether or nothe Polynomial terms list needs to be sorted 
	 * 
	 * @var bool
	 * @access protected
	 */
    var $_needs_sorting;
    
	/**
	 * Polynomial Constructor
	 * 
	 * Constructs an empty ( equal to 0 )  Polynomial object OR constructs a 
	 * Polynomial from another Polynomial object or from a string representation
	 * of a polynomial. 
	 * 
	 * @param mixed $mixed_poly String or Polynomial to construct from 
	 */
	function Math_Polynomial($mixed_poly = null)
	{
		// Init vars
		$this->_terms = array();
		$this->_needs_combining = false;
		$this->_needs_sorting = false;
		
		if (is_a($mixed_poly, 'Math_Polynomial')) { // Load from another Polynomail
			$this->_terms = $mixed_poly->_terms;
			$this->_needs_combining = $mixed_poly->_needs_combining;
			$this->_needs_sorting = $mixed_poly->_needs_sorting;
		} else { // Parse from string/integer
			$this->_parsePolynomial(trim((string) $mixed_poly));
		}
	}
	
	/**
	 * Tell the number of terms in the Polynomial object
	 * 
	 * @access public
	 * 
	 * @see Math_Polynomial::getTerm(), Math_Polynomial::addTerm(), Math_PolynomialTerm
	 * 
	 * @return integer
	 */
	function numTerms()
	{
		if ($this->_needs_combining) {
			$this->_combineLikeTerms();
		}
		
		return count($this->_terms);
	}
	
	/**
	 * Get the term in the nth position from the Polynomial
	 * 
	 * @access public
	 * 
	 * @see Math_Polynomial::numTerms(), Math_Polynomial::addTerm(), Math_PolynomialTerm
	 * 
	 * @param integer $n
	 * @return object
	 */
	function getTerm($n)
	{
		if ($this->_needs_combining) {
			$this->_combineLikeTerms();
		}
		
		if (isset($this->_terms[$n])) {
			return $this->_terms[$n];
		} else {
			return new Math_PolynomialTerm(0, 0);
		}
	}
	
	/**
	 * Add a term to the Polynomial
	 * 
	 * @access public
	 * 
	 * @see Math_Polynomial::getTerm(), Math_Polynomial::numTerms(), Math_PolynomialTerm
	 * 
	 * @param object $term
	 * @return bool
	 */
	function addTerm($term)
	{
		if (is_a($term, 'Math_PolynomialTerm')) {
			
			if ($term->getCoefficient() != 0) { // Only accept non-zero terms
				$this->_needs_combining = true;
				$this->_needs_sorting = true;
				
				$this->_terms[] = $term;
			}
			
			return true;
		} else {
			return PEAR::raiseError('Wrong parameter datatype to Math_Polynomial::addTerm().');
		}
	}
	
	/**
	 * Parse an individual term of Math_Polynomial into a Math_PolynomialTerm object
	 * 
	 * @access protected
	 * 
	 * @param string $str_term
	 * 
	 * @return object A Math_PolynomialTerm object
	 */
	function _parseTerm($str_term)
	{
		$str_term = strtolower(str_replace(' ', '', $str_term)); // Lowercase and get rid of spaces
		$neg = false;
		$term = new Math_PolynomialTerm();
		
		if (!strlen($str_term)) {
			return $term; // Defaults to 0, should be good to go
		}
		
		if ($str_term[0] == '-') {
			$neg = true;
			$str_term = substr($str_term, 1);
		}
		
		if ($str_term == 'x') { // x
			$term->setCoefficient(1);
			$term->setExponent(1);
		} else if (false !== strpos($str_term, 'x^')) { // Cx^n || x^n
			
			// Explode by x^, look at two parts
			$arr_tmp = explode('x^', $str_term);
			if (count($arr_tmp) == 2) {
				if (strlen($arr_tmp[0]) == 0) { // x^n
					$term->setCoefficient(1);
				} else { // Cx^n
					$term->setCoefficient($arr_tmp[0]);
				}
				
				$term->setExponent($arr_tmp[1]);
			}
		} else if (false !== strpos($str_term, 'x')) { // Cx
			$pos = strpos($str_term, 'x');
			$str_term = substr($str_term, 0, $pos);
			$term->setCoefficient($str_term);
			$term->setExponent(1);
		} else { // C
			$term->setCoefficient($str_term);
			$term->setExponent(0);
		}
		
		if ($neg) {
			$term->setCoefficient($term->getCoefficient() * -1.0);
		}
		
		return $term;
	}
	
	/**
	 * Parse a string
	 * 
	 * @uses Math_Polynomial::_parseTerm()
	 * 
	 * @access protected
	 * 
	 * @param string $str_poly A string representation of a Polynomial
	 * 
	 * @return void
	 */
	function _parsePolynomial($str_poly)
	{
		$str_poly = str_replace(' ', '', $str_poly); // Get rid of spaces
		
		$str_poly = str_replace('--', '+', $str_poly);
		$str_poly = str_replace('-', '+-', $str_poly);
		$arr_str_terms = explode('+', $str_poly);
		
		foreach ($arr_str_terms as $str_term) {
			$term = $this->_parseTerm($str_term);
			
			if ($term->getCoefficient() != 0) { // Discard zero-coefficient terms 
				$this->_terms[] = $term;
			}
		}
			
		return;
	}
	
	/**
	 * Retrieve a string representation of the Polynomial
	 * 
	 * @access public
	 * 
	 * @param bool $spaces Whether or not the string should contain spaces
	 * 						( i.e.: 4x^2 - 2x vs. 4x^2-2x )
	 * 
	 * @return string String representation
	 */
	function toString($spaces = true)
	{
		if ($this->_needs_combining) {
			$this->_combineLikeTerms();
		}
		
		if ($this->_needs_sorting) {
			$this->_sortTerms();
		}
		
		$str = '';
		
		if (!count($this->_terms)) {
			return '0';
		}
		
		if ($this->degree() == 0) { // If degree is 0, just print constant ( must be only one term )
			$str = $str . $this->_terms[0]->getCoefficient();
			return $str;
		}
		
		$count = count($this->_terms);
		$first = true;
		$sign_swap = false; // Used for a back hack for display purposes... gotta fix this
		
		for ($i = 0; $i < $count; $i++) {
			if (!$first && $this->_terms[$i]->getCoefficient() > 0) {
				$str = $str . ' + ';
			} else if (!$first && $this->_terms[$i]->getCoefficient() < 0) {
				$str = $str . ' - ';
				$this->_terms[$i]->setCoefficient($this->_terms[$i]->getCoefficient() * -1);
				$sign_swap = true;
			}
			
			if ($this->_terms[$i]->getCoefficient() == 0) { // Term multiples out to 0, don't print
				;
			} else if ($this->_terms[$i]->getExponent() == 0) { // Term is a constant
				$str = $str . $this->_terms[$i]->getCoefficient();
			} else if ($this->_terms[$i]->getExponent() == 1 && $this->_terms[$i]->getCoefficient() == 1) { // Term is just x
				$str = $str . 'x';
			} else if ($this->_terms[$i]->getExponent() == 1) { // Term is Cx ( no exponent )
				$str = $str . $this->_terms[$i]->getCoefficient() . 'x';
			} else if ($this->_terms[$i]->getCoefficient() == 1) { // Term is x^E ( no coefficient )
				$str = $str . 'x^' . $this->_terms[$i]->getExponent();
			} else {
				$str = $str . $this->_terms[$i]->getCoefficient() . 'x^' . $this->_terms[$i]->getExponent(); // Term is normal :-P
			}
	
			$first = false;
			
			if ($sign_swap) {
				$this->_terms[$i]->setCoefficient($this->_terms[$i]->getCoefficient() * -1);
				$sign_swap = false;
			}
		}
		
		if (!$spaces) { // Remove spaces
			$str = str_replace(' ', '', $str);
		}
		
		return $str;
	}
	
	/**
	 * Sort the terms list
	 * 
	 * Uses a bubble-sort to sort the list of terms in descending order by their 
	 * exponent values. 
	 * 
	 * @access protected
	 * 
	 * @return void
	 */
	function _sortTerms()
	{
		$bln_swap = true;
		$count = count($this->_terms);
		
		for ($i = 0; $i < $count and $bln_swap; $i++) {
			$bln_swap = false;
			for ($j = 0; $j < ($count - 1); $j++) {
				if ($this->_terms[$j]->getExponent() < $this->_terms[$j + 1]->getExponent()) {
					list($this->_terms[$j], $this->_terms[$j + 1]) = array($this->_terms[$j + 1], $this->_terms[$j]);
					$bln_swap = true;
				}
			}
		}
		
		$this->_needs_sorting = false;
	}
	
	/**
	 * Combine like terms inside the Polynomial object
	 * 
	 * Examines each term of the Polynomial and, if any of the terms have 
	 * equivalent exponents, adds the two terms together ( add coefficients, 
	 * keep the same exponent ) This is used to simplify the Polynomial for 
	 * output. 
	 * 
	 * @access protected
	 * 
	 * @return void
	 */
	function _combineLikeTerms()
	{
		if (count($this->_terms) == 0) {
			return;
		}
		
		if ($this->_needs_sorting) {
			$this->_sortTerms();
		}
		
		$last_term = $this->_terms[0];
		$count = count($this->_terms);
		$arr_terms = array();
		
		for ($i = 1; $i < $count; $i++) {
			$this_term = $this->_terms[$i];
			
			if ($last_term->getExponent() == $this_term->getExponent()) { // Terms are like, add together
				// Add the two terms together
				$last_term = new Math_PolynomialTerm($last_term->getCoefficient() + $this_term->getCoefficient(), $last_term->getExponent());
			} else {
				if ($last_term->getCoefficient()) {// There's a possibility that $last_term has 0 coefficient, we don't want those
					$arr_terms[] = $last_term;
				}
				
				$last_term = new Math_PolynomialTerm($this_term->getCoefficient(), $this_term->getExponent());
			}
		}
		
		$arr_terms[] = $last_term;
		
		$this->_needs_combining = false;
		$this->_terms = $arr_terms;
	}
	
	/**
	 * Retrieve the degree ( highest exponent value ) of the polynomial
	 * 
	 * @access public
	 * 
	 * @return integer The degree of the polynomial
	 */
	function degree()
	{
		if ($this->_needs_combining) {
			$this->_combineLikeTerms();
		}
			
		if ($this->_needs_sorting) {
			$this->_sortTerms();
		}
		
		if (count($this->_terms) == 0) {
			return 0;
		} else {
			return $this->_terms[0]->getExponent();
		}
	}
	
	/**
	 * Retrieve a string naming the degree of the polynomial
	 * 
	 * Tries to retrieve a string to name the polynomial degree, for example:
	 * <samp>
	 * 3 - Contant 
	 * 3x - Linear equation
	 * 3x^2 - Quadtratic
	 * 3x^3 - Cubic
	 * etc.
	 * </samp>
	 * 
	 * @access public 
	 * 
	 * @return string String naming the polynomial degree
	 */
	function degreeString()
	{
		switch ($this->degree()) {
			case 0:
				return 'constant';
			case 1:
				return 'linear';
			case 2:
				return 'quadratic';
			case 3:
				return 'cubic';
			case 4:
				return 'quartic';
			case 5:
				return 'quintic';
			default:
				return 'unknown';
		}
	}
}

?>
