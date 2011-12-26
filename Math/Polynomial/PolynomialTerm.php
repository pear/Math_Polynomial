<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class representing an individual term of a Polynomial
 * 
 * @see Math_Polynomial
 * @see Math_PolynomialOp
 * 
 * @category Math
 * @package Math_Polynomial
 * @author Keith Palmer <Keith@UglySlug.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Class representing a term of a Polynomial
 * 
 * Represents a polynomial term of the form: 
 * 	Cx^E where C is a constant (float), and E is an exponent (int)
 * 
 * @see Math_Polynomial
 * 
 * @package Math_Polynomial
 * @author Keith Palmer <keith@UglySlug.com>
 */
class Math_PolynomialTerm
{
	/**
	 * The coefficient of the term
	 * 
	 * @var float
	 * @access protected
	 */
	var $_coef;
	
	/**
	 * The exponent of the term
	 * 
	 * @var integer
	 * @access protected
	 */
	var $_exp;
	
	/**
	 * Construct a polynomial term object
	 * 
	 * @access public
	 * 
	 * @param float $flt_coef
	 * @param integer $int_exp
	 */
	function Math_PolynomialTerm($flt_coef = 0.0, $int_exp = 0)
	{
		$this->_coef = (float) $flt_coef;
		$this->_exp = (int) $int_exp;
	}
	
	/**
	 * Get the exponent from the Polynomial Term
	 * 
	 * @access public
	 * 
	 * @return integer
	 */
	function getExponent()
	{
		return $this->_exp;
	}
	
	/**
	 * Get the coefficient from the Polynomial Term
	 * 
	 * @access public
	 * 
	 * @return float
	 */
	function getCoefficient()
	{
		return $this->_coef;
	}
	
	/**
	 * Set the exponent of the term
	 * 
	 * @access public
	 * 
	 * @param integer $int_exp
	 * @return void
	 */
	function setExponent($int_exp)
	{
		$this->_exp = (int) $int_exp;
	}
	
	/**
	 * Set the coefficient of the term
	 * 
	 * @access public
	 * 
	 * @param float $flt_coef
	 * @return void
	 */
	function setCoefficient($flt_coef)
	{
		$this->_coef = (float) $flt_coef;
	}
	
	/**
	 * Get a string representation of just this term
	 * 
	 * @access public
	 * 
	 * @return string
	 */
	function toString()
	{
		if ($this->_coef != 0 and $this->_exp > 1) {
			return $this->_coef . 'x^' . $this->_exp;
		} else if ($this->_coef != 0 and $this->_exp == 1) {
			return $this->_coef . 'x';
		} else if ($this->_coef != 0) {
			return $this->_coef;
		} else {
			return '0';
		}
	}
}

?>