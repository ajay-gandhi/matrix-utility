<?php
$posted = false;
$textarea_rows = 3;
$errors = "Error: ";
$ech = 'checked="true"';
$rr_ech = "";
$determinant = "";
$input_matrix = "";
$spacers = array();
if (isset($_POST['input-matrix'])) {
	$action = $_POST['action'];
	$input_matrix = $_POST['input-matrix'];
	if (is_numeric(str_replace('-', '', preg_replace('/\s+/', '', $_POST['input-matrix']))) == false) {
		$errors .= "<br />Invalid input. Matrix can only contain numbers and fractions (e.g. 4, 0.25, 1/4).";
	}
	$posted = true;
	$og_matrix = explode("\n", $_POST['input-matrix']);
	$textarea_rows = count($og_matrix) + 1;
	foreach ($og_matrix as $w => $r) {
		$og_matrix[$w] = explode(' ', $r);
		$solved_matrix = $og_matrix;
		foreach ($og_matrix[$w] as $w2 => $v) {
			if (strpos($og_matrix[$w][$w2], '/') !== false) {
				$solved_matrix[$w][$w2] = intval(array_shift(explode('/', $og_matrix[$w][$w2]))) / intval(end(explode('/', $og_matrix[$w][$w2])));
			}
			$og_matrix[$w][$w2] = trim($og_matrix[$w][$w2]);
		}
	}
	if ($action == "determinant") {
		$ech = "";
		$determinant = 'checked="true"';
	} else if ($action == "ech" || $action == "rr-ech") {
		$solved_matrix = $og_matrix;
		if (count($og_matrix) > 8) {
			$errors .= "<br />Matrix too large (sorry, requires too much computing power). Max rows: 8";
		} else {
			for ($i = 0; $i < (count($og_matrix) - 1); $i++) {
				for ($c = 1; $c < count($og_matrix); $c++) {
					if ($c != $i) {
						$solved_matrix[$c] = row_reduce($solved_matrix[$i], $solved_matrix[$c], $i);
					}
				}
			}
	
			if ($action == "rr-ech") {
				$rr_ech = 'checked="true"';
				$ech = "";
				for ($i = 0; $i < (count($og_matrix) - 1); $i++) {
					for ($c = 1; $c < count($og_matrix); $c++) {
						if ($c != $i) {
							$solved_matrix[$i] = row_reduce_rev($solved_matrix[$c], $solved_matrix[$i]);
						}
					}
				}
	
				foreach ($solved_matrix as $rnum => $row) {
					$index = 0;
					$cont = true;
					while ($row[$index] == 0 && $cont == true) {
						$index++;
						if ($index == (count($row) - 1)) {
							$cont = false;
						}
					}
					if ($cont == true) {
						$firstVal = $row[$index];
						foreach ($row as $ind => $num) {
							$row[$ind] = create_fraction($num, $firstVal);
						}
					}
					$solved_matrix[$rnum] = $row;
				}
			}
		}
	}
}

/* Display given matrix */
function display($matrix, $which) {
	$output = "";
	global $spacers;
	$numspaces = 0;
	foreach ($matrix as $m => $n) {
		foreach ($n as $a => $b) {
			if (strlen($b) > $spacers[$a]) {
				$numspaces -= $spacers[$a];
				$spacers[$a] = strlen($b);
				$numspaces += $spacers[$a];
				if ($matrix[$m-1][$a] < 0) {
					$numspaces++;
				}
				if ($b < 0) {
					$numspaces--;
				}
			}
		}
	}

	$numspaces += ((count($matrix[0]) - 1) * 3) + 2;
	$space = "";
	for ($z = 0; $z < $numspaces; $z++) {
		$space .= " ";
	}

	foreach ($matrix as $c => $d) {
		foreach ($d as $u => $v) {
			if ($v < 0) {
				$output = substr($output, 0, strlen($output) - 1) . $v . " ";
			} else {
				$output .= $v;
			}
			for ($j = strlen($v) - 3; $j < $spacers[$u]; $j++) {
				$output .= " ";
			}
		}
		$output = substr($output, 0, strlen($output) - 1);
		$output .= "\n\n";
	}
	return $which . " matrix:\n\n" . substr($output, 0, strlen($output) - 2);
}

/* Reduce a specific row (divide by GCF). Only works for simple divisors. */
function divGCF($row) {
	$divisors = array(2, 3, 5, 7, 11);
	foreach ($divisors as $div) {
		$new_row = array();
		$total = count($row);
		$divisible = 0;
		$negative = 0;
		$zeros = 0;
		foreach ($row as $key => $val) {
			if ($val == 0) {
				$zeros++;
				$new_row[$key] = $val;
			} else if ($val % $div == 0) {
				$divisible++;
				$new_row[$key] = $val / $div;
			} else if ($val < 0) {
				$negative++;
				$new_row[$key] = $val * -1;
			}
		}
		if ($zeros == $total) {
			return $new_row;
		} else if (($negative + $zeros) == $total) {
			return divGCF($new_row);
		} else if (($divisible + $zeros) == $total) {
			return divGCF($new_row);
		}
	}
	return $row;
}

/* Attempts to remove decimals from a row */
function remove_dec($row) {
	$common_decimals = array(.1, .15, .25, .3333);
	$dec_reduce = 0;
	foreach ($row as $col => $value) {
		if (intval($value) == 0) {
			$row[$col] = 0;
		}
		if (strpos($value, '.') !== false) {
			foreach ($common_decimals as $reduc) {
				if (fmod($value, $reduc) == 0) {
					$dec_reduce = $reduc;
				} else if (abs(fmod($value, $reduc)) < .001) {
					$dec_reduce = abs($value) - floor(abs($value));
				}
			}
		}
	}

	if ($dec_reduce != 0) {
		foreach ($row as $index => $value) {
			$row[$index] = round($value * (1 / $dec_reduce));
		}
	}

	return divGCF($row);
}

/* Creates and reduces a fraction from two numbers (x, y) -> x/y using GCF */
function create_fraction($x, $y) {
	if ($x % $y == 0) {
		return $x / $y;
	}
	$divisors = array(2, 3, 5, 7, 11);
	foreach ($divisors as $div) {
		if ($x % $div == 0 && $y % $div == 0) {
			return create_fraction($x / $div, $y / $div);
		}
	}
	return $x . "/" . $y;
}

/* Perform row-reduction on two provided rows (arrays) */
function row_reduce($r1, $r2, $i = 0) {
	if ($r2[$i] == 0) {
		return $r2;
	}
	$s = $r2[$i];
	$f = $r1[$i];
	if ($s % $f == 0 || $f % $s == 0) {
		if ($s >= $f) {
			if ($f == 0) {
				$divisor = $s;
			} else {
				$divisor = $s / $f;
			}
			foreach ($r2 as $key => $val) {
				$r2[$key] = $val - ($r1[$key] * $divisor);
			}
		} else {
			$divisor = $f / $s;
			foreach ($r2 as $key => $val) {
				$r2[$key] = ($val * $divisor) - $r1[$key];
			}
		}
	} else {
		$lcm = ($s * $f) / gcd($s, $f);
		$div1 = $lcm / $f;
		$div2 = $lcm / $s;
		foreach ($r2 as $key => $val) {
			$r2[$key] = ($val * $div2) - ($r1[$key] * $div1);
		}
	}
	return remove_dec($r2);
}

/* Perform row-reduction on two provided rows (arrays).
This method is for achieving reduced echelon form. */
function row_reduce_rev($r1, $r2) {
	$start = 0;
	while ($r1[$start] == 0) {
		$start++;
		if ($start == (count($r1) - 1)) {
			return $r2;
		}
	}
	$divisor = $r2[$start] / $r1[$start];
	foreach ($r2 as $key => $val) {
		$r2[$key] = $val - ($r1[$key] * $divisor);
	}
	return remove_dec($r2);
}

/* Find determinant of a matrix */
function find_determinant($m) {
	if (count($m) != count(array_shift($m))) {
		return "DNE";
	}
	if (count($m) == 1 && count(reset($m)) == 1) {
		return array_shift(array_shift($m));
	}
	$final = 0;
	foreach ($m[0] as $col => $value) {
		$small_m = $m;
		unset($small_m[0]);
		foreach ($small_m as $which_row => $row) {
			unset($row[$col]);
			$small_m[$which_row] = $row;
		}
		$final += $value * (find_determinant($small_m));
	}
	return $final;
}

/* Get GCF of two numbers */
function gcd($x, $y) {
	if ($y == 0) {
		return $x;
	}
	return gcd($y, $x % $y);
}

/* Get latest Google library link */
function get_google_snippet($lib_name) {
	$lib_name = str_replace(' ', '-', strtolower($lib_name));
	$page_contents = file_get_contents('https://developers.google.com/speed/libraries/devguide');
	$dom = new DOMDocument;
	@$dom->loadHTML($page_contents);
	$codes = $dom->getElementsByTagName('code');
	foreach ($codes as $code_tag) {
		if ($code_tag->getAttribute('class') == 'snippet') {
			if (strpos($code_tag->nodeValue, $lib_name) !== false) {
				return preg_replace('/\s+/', ' ', $code_tag->nodeValue );
			}
		}
	}
}

if (empty($_POST["input-matrix"])) {
	$posted = false;
	$textarea_rows = 3;
}

?>
<!DOCTYPE HTML>
<html>
<head>
	<title>Matrix Solver - Scyberia</title>
	<?php echo get_google_snippet("jquery"); ?>
	<script type="text/javascript">
	$(document).ready(function() {
	$("textarea").css({
		"font-family": "Monospace",
		"font-size": "11pt",
		"width": "auto",
		"overflow": "none"
	});
	$("textarea#main-t").keyup(function(e){
		var code = (e.keyCode ? e.keyCode : e.which);
		if (code == 13 && parseInt($(this).attr('rows')) < 20) {
			$(this).attr('rows', parseInt($(this).attr('rows')) + 1);
		}
	});
	$("button").click(function() {
		$("textarea#main-t").text("1 2 5\n3 2 7");
		$("p#example").html("1x + 2y = 5<br />3x + 2y = 7");
	});
	});
	</script>
</head>
<body>
<h1>Matrix Solver</h1>
<?php
if ($errors != "Error: ") {
	$posted = false;
	echo '<p style="color:red;">' . $errors . "</p>";
}
?>
<p>Enter the matrix in the space below without brackets. Separate numbers by spaces and rows by line breaks (Enter key).</p>
<button>Click for example</button>
<br /><p id="example" style="font-family:Monospace;"></p><br />
<form id="main" method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<textarea rows="<?php echo $textarea_rows; ?>" cols="30" id="main-t" name="input-matrix"><?php echo $input_matrix; ?></textarea><br />
<p>Find:</p>
<input type="radio" id="echelon" name="action" value="ech" <?php echo $ech; ?> /> <label for="ech">Echelon form</label><br />
<input type="radio" id="rr-echelon" name="action" value="rr-ech" <?php echo $rr_ech; ?> /> <label for="rr-echelon">Reduced echelon form</label><br />
<input type="radio" id="determinant" name="action" value="determinant" <?php echo $determinant; ?> /> <label for="determinant">Determinant</label><br /><br />
<input type="submit" value="Solve" />
</form>
<?php
if ($posted == true) {
	$om = display($og_matrix, "Original");
	if ($action == "determinant") {
		$det = find_determinant($og_matrix);
		$result = "Determinant: " . $det;
		$rows = (count($og_matrix) * 2) + 5;
		$cols = (count($og_matrix) * 4) + 4;
		if ($cols < 17) {
			$cols = 17;
		}
		if ($cols < (15 + strlen($det))) {
			$cols = 15 + strlen($det);
		}
	} else if ($action == "ech" || $action == "rr-ech") {
		$result = "\n" . display($solved_matrix, "Solved");
		$rows = (count($solved_matrix) * 4) + 6;
		$cols = ((max($spacers) - 1) * count($solved_matrix[0])) + (3 * count($solved_matrix[0]));
		if ($cols < 17) {
			$cols = 17;
		}
	}
	echo '<br /><textarea disabled rows="' . $rows . '" cols="' . $cols . '">' . $om . "\n\n\n" . $result . "</textarea>";
}
?>
</body>
</html>