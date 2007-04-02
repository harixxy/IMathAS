<?php
//IMathAS:  Question library import
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid)) && $myrights<75) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
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
	
	$cid = $_GET['cid'];
	
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . $_POST['filename'];
		
		$libstoadd = $_POST['libs'];
		
		list($packname,$names,$parents,$libitems,$unique,$lastmoddate) = parselibs($filename);
		//need to addslashes before SQL insert
		$names = array_map('addslashes_deep', $names);
		$parents = array_map('addslashes_deep', $parents);
		$libitems = array_map('addslashes_deep', $libitems);
		$unique = array_map('addslashes_deep', $unique);
		$lastmoddate = array_map('addslashes_deep', $lastmoddate);
		
		$root = $_POST['parent'];
		$librights = $_POST['librights'];
		$qrights = $_POST['qrights'];
		$touse = '';
		//write libraries
		$lookup = implode("','",$unique);
		$query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_libraries WHERE uniqueid IN ('$lookup')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$exists[$row[1]] = $row[0];
			$adddate[$row[0]] = $row[2];
			$lastmod[$row[0]] = $row[3];
		}

		$mt = microtime();
		$updatel = 0;
		$newl = 0;
		$newli = 0;
		$updateq = 0;
		$newq = 0;
		foreach ($libstoadd as $libid) {
			if ($parents[$libid]==0) {  //use given root parent
				$parent = $root;
			} else if (isset($libs[$parents[$libid]])) { //if parent has been set
				$parent = $libs[$parents[$libid]];
			} else { //otherwise, skip this library (skip children if parent not added)
				continue;
			}
			$now = time();
			if (isset($exists[$unique[$libid]]) && $_POST['merge']==1) {
				if ($lastmoddate[$libid]>$adddate[$exists[$unique[$libid]]]) { //if library has changed
					$query = "UPDATE imas_libraries SET name='{$names[$libid]}',adddate=$now,lastmoddate=$now WHERE id={$exists[$unique[$libid]]}";
					if ($isgrpadmin) {
						$query .= " AND groupid='$groupid'";
					} else if (!$isadmin) {
						$query .= " AND (ownerid='$userid' or userights>1)";
					}
					mysql_query($query) or die("error on: $query: " . mysql_error());
					if (mysql_affected_rows()>0) {
						$libs[$libid] = $exists[$unique[$libid]];
						$updatel++;
					}
				}
			} else if (isset($exists[$unique[$libid]]) && $_POST['merge']==-1 ) {
				$libs[$libid] = $exists[$unique[$libid]];
			} else {
				if ($unique[$libid]==0 || (isset($exists[$unique[$libid]]) && $_POST['merge']==0)) {
					$unique[$libid] = substr($mt,11).substr($mt,2,2).$libid;
				}
				$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,parent,groupid) VALUES ";
				$query .= "('{$unique[$libid]}',$now,$now,'{$names[$libid]}','$userid','$librights','$parent','$groupid')";
				mysql_query($query) or die("error on: $query: " . mysql_error());
				$libs[$libid] = mysql_insert_id();
				$newl++;
			}
			if (isset($libs[$libid])) {
				if ($touse=='') {$touse = $libitems[$libid];} else if (isset($libitems[$libid])) {$touse .= ','.$libitems[$libid];}
			}
		}
		
		//write questions, get qsetids
		$qids = parseqs($filename,$touse,$qrights);

		//write imas library items, connecting libraries to items
		foreach ($libstoadd as $libid) {
			if (!isset($libs[$libid])) { $libs[$libid]=0;} //assign questions to unassigned if library is closed.  Shouldn't ever trigger
			$query = "SELECT qsetid FROM imas_library_items WHERE libid={$libs[$libid]}";
			$result = mysql_query($query) or die("error on: $query: " . mysql_error());
			$existingli = array();
			while ($row = mysql_fetch_row($result)) { //don't add new LI if exists
				$existingli[] = $row[0]; 	
			}
			$qidlist = explode(',',$libitems[$libid]);
			foreach ($qidlist as $qid) {
				if (isset($qids[$qid]) && (array_search($qids[$qid],$existingli)===false)) {
					$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('{$libs[$libid]}','{$qids[$qid]}','$userid')";
					mysql_query($query) or die("Import failed on $query: " . mysql_error());
					$newli++;
				}
			}
			unset($existingli);
		}
		
		unlink($filename);
		echo "Import Successful.<br>";
		echo "New Libraries: $newl.<br>";
		echo "New Questions: $newq.<br>";
		echo "Updated Libraries: $updatel.<br>";
		echo "Updated Questions: $updateq.<br>";
		echo "New Library items: $newli.<br>";
		if ($isadmin || $isgrpadmin) {
			echo "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Return to Admin page</a>";
			//header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php");
		} else {
			echo "<a href=\" http://" . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid\">Return to Course page</a>";
			//header("Location: http://" . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid");
		}
		exit;
	}
	
	require("../header.php");
?>
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>
<?php
	if ($isadmin || $isgrpadmin) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Import Libraries</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Libraries</div>\n";
	}
	echo "<h3>Import Question Libraries</h3>\n";
	echo "<form enctype=\"multipart/form-data\" method=post action=\"importlib.php?cid=$cid\">\n";
	
	if ($_FILES['userfile']['name']=='') {
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"3000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
	} else {
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			echo "<p>Error uploading file!</p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
		list($packname,$names,$parents,$libitems,$unique,$lastmoddate) = parselibs($uploadfile);
		if (!isset($parents)) {
			echo "<p>This file does not appear to contain a library structure.  It may be a question set export. ";
			echo "Try the <a href=\"import.php?cid=$cid\">Import Question Set</a> page</p>\n";
			require("../footer.php");
			exit;
		} else {
			echo "<p>This page will import entire questions libraries with heirarchy structure.  To import specific questions ";
			echo "into existing libraries, use the  <a href=\"import.php?cid=$cid\">Question Import</a> page</p>\n";
		}
		echo $packname;
		
		echo "<h3>Select Libraries to import</h3>\n";
		echo "<p>Note:  If a parent library is not selected, NONE of the children libraries will be added,";
		echo "regardless of whether they're checked or not</p>\n";
		
		echo "<p>\n";
		echo "Set Question Use Rights to: <select name=qrights>\n";
		echo "<option value=\"0\">Private</option>\n";
		echo "<option value=\"2\" SELECTED>Allow use, use as template, no modifications</option>\n";
		echo "<option value=\"3\">Allow use and modifications</option>\n";
		echo "</select>\n";
		echo "</p><p>\n";
		echo "Set Library Use Rights to: <select name=\"librights\">\n";
		
		echo "<option value=\"0\">Private</option>\n";
		echo "<option value=\"1\">Closed to group, private to others</option>\n";
		echo "<option value=\"2\" SELECTED>Open to group, private to others</option>\n";
		if ($isadmin || $isgrpadmin || $allownongrouplibs) {
			echo "<option value=\"4\">Closed to all</option>\n";
			echo "<option value=\"5\">Open to group, closed to others</option>\n";
			echo "<option value=\"8\">Open to all</option>\n";
		}
			
		
		echo "</select></p>\n";
		
		echo <<<END
<script>
var curlibs = '0';
function libselect() {
	window.open('../course/libtree.php?libtree=popup&cid=$cid&selectrights=1&select=parent&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("parent").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
END;
		
		echo "<p>Parent library: <span id=\"libnames\">Root</span><input type=hidden name=\"parent\" id=\"parent\"  value=\"0\">\n";
		echo "<input type=button value=\"Select Parent\" onClick=\"libselect()\"></p> ";
		
		echo "<p>If a library or question already exists on this system, do you want to:<br/>\n";
		echo "<input type=radio name=merge value=\"1\" CHECKED>Update existing, <input type=radio name=merge value=\"0\">import as new, or  <input type=radio name=merge value=\"-1\">Keep existing<br/>";
		echo "Note that updating existing libraries will not place those imported libraries in the parent selected above.</p>\n";
		
		
		echo <<<END
<style type="text/css">
<style type="text/css">
<!--
@import url("$imasroot/course/libtree.css");
-->
</style>
<script type="text/javascript">
function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById('b'+id);
	if (node.className == "show") {
		node.className = "hide";
		button.innerHTML = "+";
	} else {
		node.className = "show";
		button.innerHTML = "-";
	}
}
</script>
END;
		
		function printlist($parent) {
			global $parents,$names;
			$children = array_keys($parents,$parent);
			foreach ($children as $child) {
				if (!in_array($child,$parents)) { //if no children
					echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=\"$child\" CHECKED>{$names[$child]}</li>";
				} else { // if children
					echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
					echo "</span><input type=checkbox name=\"libs[]\" value=$child CHECKED>";
					echo "<span class=hdr onClick=\"toggle($child)\">{$names[$child]}</span>";
					echo "<ul class=hide id=$child>\n";
					printlist($child);
					echo "</ul></li>\n";
				}
			}
		}
		echo "Base\n";
		echo "<ul class=base>";
		printlist(0);
		echo "</ul>";
		
		
		echo "<p><input type=submit name=\"process\" value=\"Import Libraries\"></p>\n";
	}
	
	echo "</form>\n";
	require("../footer.php");
	function parselibs($file) {
		$handle = fopen($file,"r");
		if (!$handle) {
			echo "eek!  handle doesn't exist";
			exit;
		}
		$line = '';
		while (!feof($handle) && $line!="START QUESTION") {
			$line = rtrim(fgets($handle, 4096));
			if ($line=="PACKAGE DESCRIPTION") {
				$dopackd = true;
				$packname = rtrim(fgets($handle, 4096));
			} else if ($line=="START LIBRARY") {
				$dopackd = false;
				$libid = -1;
			} else if ($line=="ID") {
				$libid = rtrim(fgets($handle, 4096));
			} else if ($line=="UID") {
				$unique[$libid] = rtrim(fgets($handle, 4096));
			} else if ($line=="LASTMODDATE") {
				$lastmoddate[$libid] = rtrim(fgets($handle, 4096));
			} else if ($line=="NAME") {
				if ($libid != -1) {
					$names[$libid] = rtrim(fgets($handle, 4096));
				}
			} else if ($line=="PARENT") {
				if ($libid != -1) {
					$parents[$libid]= rtrim(fgets($handle, 4096));
				}
			} else if ($line=="START LIBRARY ITEMS") {
				$libitemid = -1;
			} else if ($line=="LIBID") {
				$libitemid = rtrim(fgets($handle, 4096));
			} else if ($line=="QSETIDS") {
				if ($libitemid!=-1) {
					$libitems[$libitemid] = rtrim(fgets($handle, 4096));
				}
			} else if ($dopackd ==true) {
				$packname .= rtrim($line);
			}
		}
		fclose($handle);
		return array($packname,$names,$parents,$libitems,$unique,$lastmoddate);
	}
	function parseqs($file,$touse,$rights) {
		function writeq($qd,$rights,$qn) {
			global $userid,$isadmin,$updateq,$newq,$isgrpadmin;
			$now = time();
			$qd = array_map('addslashes_deep', $qd);
			$query = "SELECT id,adddate,lastmoddate FROM imas_questionset WHERE uniqueid='{$qd['uqid']}'";
			$result = mysql_query($query) or die("Error: $query: " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$qsetid = mysql_result($result,0,0);
				$adddate = mysql_result($result,0,1);
				$lastmoddate = mysql_result($result,0,2);
				$exists = true;
			} else {
				$exists = false;
			}
			if ($exists && $_POST['merge']==1) {
				if ($qd['lastmod']>$adddate) { //only update if changed
					if ($isgrpadmin) {
						//$query = "UPDATE imas_questionset,imas_users SET imas_questionset.description='{$qd['description']}',imas_questionset.author='{$qd['author']}',";
						//$query .= "imas_questionset.qtype='{$qd['qtype']}',imas_questionset.control='{$qd['control']}',imas_questionset.qcontrol='{$qd['qcontrol']}',imas_questionset.qtext='{$qd['qtext']}',";
						//$query .= "imas_questionset.answer='{$qd['answer']}',imas_questionset.lastmoddate=$now,imas_questionset.adddate=$now WHERE imas_questionset.id='$qsetid'";
						//$query .= " AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
						$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE WHERE imas_questionset.id='$qsetid' AND imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)>0) {
							$query = "UPDATE imas_questionset SET description='{$qdata[$qn]['description']}',author='{$qdata[$qn]['author']}',";
							$query .= "qtype='{$qdata[$qn]['qtype']}',control='{$qdata[$qn]['control']}',qcontrol='{$qdata[$qn]['qcontrol']}',qtext='{$qdata[$qn]['qtext']}',";
							$query .= "answer='{$qdata[$qn]['answer']}',adddate=$now,lastmodddate=$now WHERE id='$qsetid'";
						} else {
							return $qsetid;
						}
					} else {
						$query = "UPDATE imas_questionset SET description='{$qd['description']}',author='{$qd['author']}',";
						$query .= "qtype='{$qd['qtype']}',control='{$qd['control']}',qcontrol='{$qd['qcontrol']}',qtext='{$qd['qtext']}',";
						$query .= "answer='{$qd['answer']}',lastmoddate=$now,adddate=$now WHERE id='$qsetid'";
						if (!$isadmin) {
							$query .= " AND ownerid=$userid";
						}
					}
					mysql_query($query) or die("error on: $query: " . mysql_error());
					if (mysql_affected_rows()>0) {
						$updateq++;
					}
				} 
				return $qsetid;
			} else if ($exists && $_POST['merge']==-1) {
				return $qsetid;	
			} else {
				if ($qd['uqid']==0 || ($exists && $_POST['merge']==0)) {
					$mt = microtime();
					$qd['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
				}
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,userights,description,author,qtype,control,qcontrol,qtext,answer) VALUES ";
				$query .= "('{$qd['uqid']}',$now,$now,'$userid','$rights','{$qd['description']}','{$qd['author']}','{$qd['qtype']}','{$qd['control']}','{$qd['qcontrol']}',";
				$query .= "'{$qd['qtext']}','{$qd['answer']}')";
				mysql_query($query) or die("Import failed on $query: " . mysql_error());
				$newq++;
				return mysql_insert_id();
			}
		}
		$touse = explode(',',$touse);
		$qnum = -1;
		$part = '';
		$handle = fopen($file,"r");
		$line = '';
		while (!feof($handle)) {
			$line = rtrim(fgets($handle, 4096));
			if ($line == "START QUESTION") {
				$part = '';
				if ($qnum>-1) {
					foreach($qdata as $k=>$val) {
						$qdata[$k] = rtrim($val);
					}
					if (in_array($qdata['qid'],$touse)) {
						$qid = writeq($qdata,$rights,$qnum);
						if ($qid!==false) {
							$qids[$qdata['qid']] = $qid;
						}
					}
					unset($qdata);
				}
				$qnum++;
				continue;
			} else if ($line == "DESCRIPTION") {
				$part = 'description';
				continue;
			} else if ($line == "QID") {
				$part = 'qid';
				continue;
			} else if ($line == "UQID") {
				$part = 'uqid';
				continue;
			} else if ($line == "LASTMOD") {
				$part = 'lastmod';
				continue;
			} else if ($line == "AUTHOR") {
				$part = 'author';
				continue;
			} else if ($line == "CONTROL") {
				$part = 'control';
				continue;
			} else if ($line == "QCONTROL") {
				$part = 'qcontrol';
				continue;
			} else if ($line == "QTEXT") {
				$part = 'qtext';
				continue;
			} else if ($line == "QTYPE") {
				$part = 'qtype';
				continue;
			} else if ($line == "ANSWER") {
				$part = 'answer';
				continue;
			} else {
				if ($part=="qtype") {
					$qdata['qtype'] .= $line;	
				} else if ($qnum>-1) {
					$qdata[$part] .= $line . "\n";	
				}
			}
		}
		fclose($handle);
		foreach($qdata as $k=>$val) {
			$qdata[$k] = rtrim($val);
		}
		if (in_array($qdata['qid'],$touse)) {
			$qid = writeq($qdata,$rights,$qnum);
			if ($qid!==false) {
				$qids[$qdata['qid']] = $qid;
			}	
		}
		return $qids;
	}
?>
	