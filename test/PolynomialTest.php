<?php

require_once '../Polynomial.php';
//require_once 'Math/PolynomialOp.php';
require_once '../PolynomialOp.php';
require_once 'PHPUnit.php';

class Math_PolynomialTest extends PHPUnit_TestCase
{
	function Math_PolynomialTest($name)
	{
		$this->PHPUnit_TestCase($name);
	}
	
	function setUp()
	{
		;
	}
	
	function tearDown()
	{
		;
	}
	
	function testObjectConstructor()
	{
		$p = new Math_Polynomial('3x + 1');
		$q = new Math_Polynomial($p);
		
		$this->assertTrue($p->toString() == $q->toString());
	}
	
	function testToString()
	{
		$str = '4x^2 + 2x + 1';
		$p = new Math_Polynomial($str);
		
		$this->assertTrue($str == $p->toString());
	}
	
	function testAdd()
	{
		$p = new Math_Polynomial('3x + 1');
		$res = Math_PolynomialOp::add($p, '4x^2 + 2x + 1');
		
		$this->assertEquals('4x^2 + 5x + 2', $res->toString());
	}
	
	function testSubtract()
	{
		$p = new Math_Polynomial('3x^2 - 2x + 1');
		$q = new Math_Polynomial('2x^2 + 2x');
		
		$res = Math_PolynomialOp::sub($p, $q);
		
		$this->assertEquals('x^2 - 4x + 1', $res->toString());
	}
	
	function testMultiply()
	{
		$p = new Math_Polynomial('3x + 1');
		$q = new Math_Polynomial('4x^2 + 2x + 1');
		$res = Math_PolynomialOp::mul($p, $q);
		
		$this->assertEquals($res->toString(), '12x^3 + 10x^2 + 5x + 1');
	}
	
	function testDivide()
	{
		$p = new Math_Polynomial('4x^5 + 2x^2 + 3x + 1');
		$q = new Math_Polynomial('3x^2 + 1');
		
		$remainder = new Math_Polynomial();
		
		$res = Math_PolynomialOp::div($p, $q, $remainder);
		
		$this->assertEquals('1.33333333333x^3 - 0.444444444444x + 0.666666666667', $res->toString());
	}
	
	function testFirstDerivative()
	{
		$p = new Math_Polynomial('12x^3 + 6x^2 + 2x + 4');
		$first_der = Math_PolynomialOp::getDerivative($p, 1);
		
		$this->assertEquals('36x^2 + 12x + 2', $first_der->toString());
	}
	
	function testSecondDerivative()
	{
		$p = new Math_Polynomial('12x^3 + 6x^2 + 2x + 4');
		$der = Math_PolynomialOp::getDerivative($p, 2);
		
		$this->assertEquals('72x + 12', $der->toString());
	}
	
	function testAntiDerivative()
	{
		$p = new Math_Polynomial('12x^3 + 6x^2 + 2x + 4');
		$first_der = Math_PolynomialOp::getDerivative($p);
				
		$anti_der = Math_PolynomialOp::getAntiDerivative($first_der, 1, 4);
		
		$this->assertTrue($anti_der->toString() == $p->toString());
	}
	
	function testFromRoots()
	{
		$p = Math_PolynomialOp::createFromRoots(array(0, 3, -3));
		
		$this->assertEquals('x^3 - 9x', $p->toString());
	}
	
	function testGetRootsLinear()
	{
		$roots = Math_PolynomialOp::getRoots('5x + 10');
		
		$this->assertEquals(array(-2), $roots);
	}
	
	function testGetRootsQuadratic()
	{
		$p = new Math_Polynomial('2x^2 + 7x - 4');
		
		$roots = Math_PolynomialOp::getRoots($p);
		
		$this->assertEquals(array(0.5, -4), $roots);
	}
	
	function testGetRootsCubic()
	{
		$p = new Math_Polynomial('x - 6');
		$p = Math_PolynomialOp::mul($p, 'x + 1');
		$p = Math_PolynomialOp::mul($p, 'x - 3');
		
		$roots = Math_PolynomialOp::getRootsCubic($p);
		
		$this->assertEquals(array(6, -1, 3), $roots);
	}
	
	function testRootsQuartic()
	{
		$p = new Math_Polynomial('3x^4 + 6x^3 - 123x^2 - 126X + 1080');
		
		$roots = Math_PolynomialOp::getRootsQuartic($p);
		
		$this->assertEquals(array(5, 3, -4, -6), $roots);
	}
	
	function testLocalMaximums()
	{
		$p = new Math_Polynomial('x^4 - 44x^3 - 66x^2 + 187x + 210');
		
		$maxs = Math_PolynomialOp::getLocalMaximums($p);
		
		$this->assertEquals(array(0.796920717957544155751747894100844860076904296875), $maxs);
	}
	
	function testLocalMinimums()
	{
		$p = new Math_Polynomial('x^4 - 44x^3 - 66x^2 + 187x + 210');
		
		$maxs = Math_PolynomialOp::getLocalMinimums($p);
		
		$this->assertEquals(array(33.9319316690521048940354376100003719329833984375, -1.7288523870096472734303461038507521152496337890625), $maxs);
	}
	
	function testTangent()
	{
		$p = new Math_Polynomial('3x^3 - 2x + 2');
		
		$tangent = Math_PolynomialOp::getTangentAt($p, 0.85);
		
		$this->assertEquals('4.5025x - 1.68475', $tangent->toString());
	}
	
	function testSecant()
	{
		$p = new Math_Polynomial('2x^2 - 3x + 10');
		
		$secant = Math_PolynomialOp::getSecantAt($p, 1, 3.5);
		
		$this->assertEquals('6x + 3', $secant->toString());
	}
	
	function testRemainder()
	{
		
	}
	
	function testParamConstness()
	{
		$p1 = new Math_Polynomial('3x + 1');
		$p2 = '4x^2 + 2x + 1';
		$res = Math_PolynomialOp::add($p1, $p2);
		
		$this->assertEquals('4x^2 + 2x + 1', $p2);
	}
}

header('Content-type: text/plain');

$suite  = new PHPUnit_TestSuite('Math_PolynomialTest');
$result = PHPUnit::run($suite);

print($result -> toString());
  
?>