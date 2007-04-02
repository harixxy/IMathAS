<?php
	require_once("../validate.php");
		
	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		$isadmin = false;
		$isgrpadmin = false;
		if (isset($_GET['cid']) && $_GET['cid']=="admin") {
			if ($myrights <75) {
				require("../header.php");
				echo "You need to log in as an admin to access this page";
				require("../footer.php");
				exit;
			} else if ($myrights < 100) {
				$isgrpadmin = true;
			} else if ($myrights == 100) {
				$isadmin = true;
			}
		}
		echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>IMathAS Library Selection</title>
END;
	}
	echo <<<END
<style type="text/css">
<!--
@import url("$imasroot/course/libtree.css");
-->
</style>
<script type="text/javascript" src="$imasroot/javascript/libtree.js"></script>
END;
	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		echo <<<END
</head>
<body>
<form>
END;
	} 
	$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
	$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
	//$query = "SELECT id,name,parent FROM imas_libraries ORDER BY parent";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
	if (isset($_GET['select'])) {
		$select = $_GET['select'];
	} else if (!isset($select)) {
		$select = "child";
	}
	if (isset($_GET['selectrights'])) {
		$selectrights = $_GET['selectrights'];
	} else {
		$selectrights = 0;
	}
	if (!isset($_GET['type'])) {
		if (isset($selecttype)) {
			$_GET['type'] = $selecttype;
		} else {
			$_GET['type'] = "checkbox";
		}
	}
	$allsrights = 2+3*$selectrights;
	
	$rights = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$id = $line['id'];
		$name = $line['name'];
		$parent = $line['parent'];
		if ($line['count']==0) {
			$isempty[$id] = true;
		}
		$ltlibs[$parent][] = $id;
		$parents[$id] = $parent;
		$names[$id] = $name;
		$rights[$id] = $line['userights'];
		$ownerids[$id] = $line['ownerid'];
		$groupids[$id] = $line['groupid'];
	}
	//if parent has lower userights, up them to match child library	
	function setparentrights($alibid) {
		global $rights,$parents;
		if ($parents[$alibid]>0) {
			if ($rights[$parents[$alibid]] < $rights[$alibid]) {
			//if (($rights[$parents[$alibid]]>2 && $rights[$alibid]<3) || ($rights[$alibid]==0 && $rights[$parents[$alibid]]>0)) {
				
				$rights[$parents[$alibid]] = $rights[$alibid];
			}
			setparentrights($parents[$alibid]);
		}
	}
	foreach ($rights as $k=>$n) {
		setparentrights($k);
	}
	
	if (isset($_GET['libs']) && $_GET['libs']!='') {
		$checked = explode(",",$_GET['libs']);
	} else if (!isset($checked)) {
		$checked = array();
	}
	if (isset($_GET['locklibs']) && $_GET['locklibs']!='') {
		$locked = explode(",",$_GET['locklibs']);
	} else if (!isset($locked)) {
		$locked = array();
	}
	$checked = array_merge($checked,$locked);
	$toopen = array();
	
	if (isset($_GET['base'])) {
		$base = $_GET['base'];
	}
	if (isset($base)) {
		echo "<input type=hidden name=\"rootlib\" value=$base>{$names[$base]}</span>";
	} else {
		if ($select == "parent") {
			echo "<input type=radio name=\"libs\" value=0 ";
			if (in_array(0,$checked)) { echo "CHECKED";	}
			echo "> <span id=\"n0\" class=\"r8\">Root</span>";
		} else {
			echo "<span class=\"r8\">Root</span>";
		}
	}
	echo "<ul class=base>";
	
	if ($select == "child") {
		if ($_GET['type']=="radio") {
			echo "<li><span class=dd>-</span><input type=radio name=\"libs\" value=0 ";
			if (in_array(0,$checked)) { echo "CHECKED";	}
			echo "> <span id=\"n0\" class=\"r8\">Unassigned</span></li>\n";
		} else {
			echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=0 ";
			//if (in_array(0,$checked)) { echo "CHECKED";	}
			echo "> <span id=\"n0\" class=\"r8\">Unassigned</span></li>\n";
		}
	} else {
		echo "<li><span class=dd>---</span><span class=\"r8\">Unassigned</span></li>\n";
	}
	
	if (isset($ltlibs[0])) {
		if (isset($base)) {
			printlist($base);
		} else {
			printlist(0);
		}
	}
	echo "</ul>";
	
	$colorcode =  "<p><b>Color Code</b><br/>";
	$colorcode .= "<span class=r8>Open to all</span><br/>\n";
	$colorcode .= "<span class=r4>Closed</span><br/>\n";
	$colorcode .= "<span class=r5>Open to group, closed to others</span><br/>\n";
	$colorcode .= "<span class=r2>Open to group, private to others</span><br/>\n";
	$colorcode .= "<span class=r1>Closed to group, private to others</span><br/>\n";
	$colorcode .= "<span class=r0>Private</span></p>\n";
	
	function printlist($parent) {
		global $names,$ltlibs,$checked,$toopen, $select,$isempty,$rights,$ownerids,$isadmin,$selectrights,$allsrights,$published,$userid,$locked,$groupids,$groupid,$isgrpadmin;
		$arr = array();
		if ($parent==0 && isset($published)) {
			$arr = explode(',',$published);
		} else {
			$arr = $ltlibs[$parent];
		}
		if (count($arr)==0) {return;}
		foreach ($arr as $child) {
			if ($rights[$child]>$allsrights || (($rights[$child]%3)>$selectrights && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {	
			//if ($rights[$child]>$selectrights || $ownerids[$child]==$userid || $isadmin) {
				if (!$isadmin) {
					if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
						$rights[$child]=4;  //adjust coloring
					}
				}
				if (isset($ltlibs[$child])) { //library has children
					//echo "<li><input type=button id=\"b$count\" value=\"-\" onClick=\"toggle($count)\"> {$names[$child]}";
					echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
					if ($select == "parent" || $select=="all") {
						if ($_GET['type']=="radio") {				
							if (in_array($child,$locked) || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) { 
								echo "</span><input type=radio disabled=\"disabled\" ";
							} else {
								echo "</span><input type=radio name=\"libs\" value=$child ";
							}
							if (in_array($child,$checked)) { echo "CHECKED";	}
							echo "><span class=hdr onClick=\"toggle($child)\">";
						} else {
							if (in_array($child,$locked)) { 
								echo "</span><input type=checkbox disabled=\"disabled\" ";
							} else {
								echo "</span><input type=checkbox name=\"libs[]\" value=$child ";
							}
							
							if (in_array($child,$checked)) { echo "CHECKED";	}
							echo "><span class=hdr onClick=\"toggle($child)\">";
						}
					}
					echo " <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span> </span>\n";
					echo "<ul class=hide id=$child>\n";
					printlist($child);
					echo "</ul></li>\n";
					
				} else {  //no children
					if ($select == "child" || $select=="all" || $isempty[$child]==true) {
						if ($_GET['type']=="radio") {
							if (in_array($child,$locked) || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) { 
								echo "<li><span class=dd>---</span> <input type=radio disabled=\"disabled\" ";
							} else {
								if ($select=="parent") {
									echo "<li><span class=dd>---</span> <input type=radio name=\"libs\" value=$child ";
								} else {
									echo "<li><span class=dd>-</span> <input type=radio name=\"libs\" value=$child ";
								}
							}
							
							if (in_array($child,$checked)) { echo "CHECKED";	}
							echo "> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
						} else {
							if (in_array($child,$locked)) { 
								echo "<li><span class=dd>-</span><input type=checkbox disabled=\"disabled\" ";
	
							} else {
								echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=$child ";
							}
							if (in_array($child,$checked)) { echo "CHECKED";	}
							echo "> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
						}
					} else {
						echo "<li><span class=dd>---</span> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
					}
					
				}
			}
		}
	}
	echo "<script type=\"text/javascript\">\n";
	foreach ($checked as $child) {
		if (isset($base)) {
			if ($parents[$child]!=$base) {
				setshow($parents[$child]);
			}
		} else {
			if ($parents[$child]!=0) {
				setshow($parents[$child]);
			}
		}
	}
	function setshow($id) {
		global $parents,$base;
		echo "document.getElementById($id).className = \"show\";";
		echo "document.getElementById('b$id').innerHTML = \"-\";";
		if (isset($base)) {
			if (isset($parents[$id]) && $parents[$id]!=$base) {
				setshow($parents[$id]);
			}
		} else {
			if (isset($parents[$id]) && $parents[$id]!=0) {
				setshow($parents[$id]);
			}
		}
	}
	echo "</script>\n";
	
	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		echo <<<END
<input type=button value="Use Libaries" onClick="setlib(this.form)">
$colorcode
</form>
</body>
</html>
END;
	} else {
		echo $colorcode;
	}
?>