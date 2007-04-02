<?
//Polynomial functions.  Version 1.0, Jan 11, 2007


global $allowedmacros;
array_push($allowedmacros,"formpoly","writepoly","addpolys","subtpolys","multpolys","quadroot","getcoef","polypower");


//formpoly(coefficients,powers or degree)
//Creates a polynomial object
//Use writepoly to create a display form of the polynomial
//coefficients: list/array of coefficients
//powers or degree: list/array of powers, or highest degree
//highest degree assumes coefficients correspond to consecutively
//decreasing powers
function formpoly($coef,$deg) {
	$poly = array();
	if (!is_array($coef)) {
		$coef = explode(',',$coef);
	}
	if (is_array($deg) || strpos($deg,',')!=false) {
		if (!is_array($deg)) {
			$deg = explode(',',$deg);
		}
		for ($i=0;$i<min(count($deg),count($coef));$i++) {
			$poly[$i][0] = $coef[$i]*1;
			$poly[$i][1] = $deg[$i];
		}	
	} else {
		for ($i=0;$i<count($coef);$i++) {
			$poly[$i][0] = $coef[$i]*1;
			$poly[$i][1] = $deg;
			$deg--;
		}
	}
	return $poly;
}


//writepoly(poly,[var,showzeros])
//Creates a display form for polynomial object
//poly: polynomial object, created with formpoly
//var: input variable.  Defaults to x
//showzeros:  optional, defaults to false.  If true, shows zero coefficients
function writepoly($poly,$var="x",$sz=false) {
	$po = '';
	$first = true;
	for ($i=0;$i<count($poly);$i++) {
		if (!$sz && $poly[$i][0]==0) {continue;}
		if (!$first) {
			if ($poly[$i][0]<0) {
				$po .= ' - ';
			} else {
				$po .= ' + ';
			}
		} else {
			if ($poly[$i][0]<0) {
				$po .= ' - ';
			}
		}
		if (abs($poly[$i][0])!=1 || $poly[$i][1]==0) {
			$po .= abs($poly[$i][0]);
		}
		if ($poly[$i][1]>1) {
			$po .= " $var^". $poly[$i][1];
		} else if ($poly[$i][1]>0) {
			$po .= " $var";
		}
		$first = false;
	}
	return $po;
}


//addpolys(poly1,poly2)
//Adds polynomials, arranging terms from highest to lowest powers
function addpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1]])) {
			$p[$p2[$i][1]] += $p2[$i][0];
		} else {
			$p[$p2[$i][1]] = $p2[$i][0];
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}


//subtpolys(poly1,poly2)
//Subtracts polynomials: poly1-poly2, arranging terms from highest to lowest powers
function subtpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1]])) {
			$p[$p2[$i][1]] = $p[$p2[$i][1]] - $p2[$i][0];
		} else {
			$p[$p2[$i][1]] = -1*$p2[$i][0];
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}


//multpolys(poly1,poly2)
//Multiplies polynomials
function multpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		for ($j=0;$j<count($p2);$j++) {
			$newdeg = $p1[$i][1] + $p2[$j][1];
			$newcoef = $p1[$i][0]*$p2[$j][0];
			if (isset($p[$newdeg])) {
				$p[$newdeg] += $newcoef;
			} else {
				$p[$newdeg] = $newcoef;
			}
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}

//polypower(poly,power)
//Calculates poly^power
function polypower($p,$pow) {
	$op = $p;
	for ($i=1;$i<$pow;$i++) {
		$op = multpolys($op,$p);
	}
	return $op;
}


//quadroot(a,b,c)
//Quadratic equation, solving ax^2+bx+c = 0
//Return an array of the two solutions, ordered smaller then larger
//if no solution exists, an array of "DNE" strings is returned
function quadroot($a,$b,$c) {
	$disc = $b*$b - 4*$a*$c;
	if ($disc<0) {
		return (array("DNE","DNE"));
	} else {
		$x1 = (-1*$b + sqrt($disc))/(2*$a);
		$x2 = (-1*$b - sqrt($disc))/(2*$a);
		$mn = min($x1,$x2);
		$mx = max($x1,$x2);

		return (array($mn,$mx));
	}
}


//getcoef(poly,degree)
//Gets the coefficient corresponding to the degree specified
//if no such term is defined, 0 is returned (since that is the coefficient!)
//poly: polynomial object, created with formpoly
//degree: degree of term to get coefficient of
function getcoef($p,$deg) {
	$coef = 0;
	for ($i=0;$i<count($p);$i++) {
		if ($p[$i][1]==$deg) {
			$coef = $p[$i][0];
			break;
		}
	}
	return $coef;
}


?>

