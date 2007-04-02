<?php
//IMathAS:  Admin actions
//(c) 2006 David Lippman
require("../validate.php");

switch($_GET['action']) {
	
	case "chgrights":  
		if ($myrights < 100 && $_POST['newrights']>75) { break;}
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		
		$query = "UPDATE imas_users SET rights='{$_POST['newrights']}'";
		if ($myrights == 100) {
			$query .= ",groupid='{$_POST['group']}'";
		}
		$query .= " WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		if ($myrights == 100) { //update library groupids
			$query = "UPDATE imas_libraries SET groupid='{$_POST['group']}' WHERE ownerid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		break;
	case "resetpwd":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$md5pw =md5("password");
		$query = "UPDATE imas_users SET password='$md5pw' WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "deladmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_users WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_affected_rows()==0) { break;}
		$query = "DELETE FROM imas_students WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_teachers WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_assessment_sessions WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_exceptions WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "DELETE FROM imas_msgs WHERE msgto='{$_GET['id']}' AND isread>1"; //delete msgs to user
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+2 WHERE msgto='{$_GET['id']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "DELETE FROM imas_msgs WHERE msgfrom='{$_GET['id']}' AND isread>1"; //delete msgs from user
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE msgfrom='{$_GET['id']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		break;
	case "chgpwd":
		$query = "SELECT password FROM imas_users WHERE id = '$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
	
		if ((md5($_POST['oldpw'])==$line['password']) && ($_POST['newpw1'] == $_POST['newpw2'])) {
			$md5pw =md5($_POST['newpw1']);
			$query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error()); 
		} else {
			echo "<HTML><body>Password change failed.  <A HREF=\"forms.php?action=chgpwd\">Try Again</a>\n";
			echo "</body></html>\n";
			exit;
		}
		break;
	case "newadmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 100 && $_POST['newrights']>75) { break;}
		$query = "SELECT id FROM imas_users WHERE SID = '{$_POST['adminname']}';";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row != null) {
			echo "<html><body>Username is already used.\n";
			echo "<a href=\"forms.php?action=newadmin\">Try Again</a> or ";
			echo "<a href=\"forms.php?action=chgrights&id={$row[0]}\">Change rights for existing user</a></body></html>\n";
			exit;
		}
		
		$md5pw =md5("password");
		if ($myrights < 100) {
			$newgroup = $groupid;
		} else if ($myrights == 100) {
			$newgroup = $_POST['group'];
		}
		$query = "INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid) VALUES ('{$_POST['adminname']}','$md5pw','{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['newrights']}','{$_POST['email']}','$newgroup');";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "logout":
		$sessionid = session_id();
		$query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
		break;
	case "modify":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$topbar = array();
		if (isset($_POST['stutopbar'])) {
			$topbar[0] = implode(',',$_POST['stutopbar']);
		} else {
			$topbar[0] = '';
		}
		if (isset($_POST['insttopbar'])) {
			$topbar[1] = implode(',',$_POST['insttopbar']);
		} else {
			$topbar[1] = '';
		}
		
		$topbar = implode('|',$topbar);
			
		$hideicons = $_POST['HIassess'] + $_POST['HIinline'] + $_POST['HIlinked'] + $_POST['HIforum'] + $_POST['HIblock'];
		$avail = 3 - $_POST['stuavail'] - $_POST['teachavail'];
		$query = "UPDATE imas_courses SET name='{$_POST['coursename']}',enrollkey='{$_POST['ekey']}',hideicons='$hideicons',available='$avail',lockaid='{$_POST['lockaid']}',";
		$query .= "allowunenroll='{$_POST['allowunenroll']}',copyrights='{$_POST['copyrights']}',msgset='{$_POST['msgset']}',topbar='$topbar',cploc='{$_POST['cploc']}' WHERE id='{$_GET['id']}'";
		if ($myrights==40) { $query .= " AND ownerid='$userid'";}
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "addcourse":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$topbar = array();
		if (isset($_POST['stutopbar'])) {
			$topbar[0] = implode(',',$_POST['stutopbar']);
		} else {
			$topbar[0] = '';
		}
		if (isset($_POST['insttopbar'])) {
			$topbar[1] = implode(',',$_POST['insttopbar']);
		} else {
			$topbar[1] = '';
		}
		$topbar = implode('|',$topbar);
		$hideicons = $_POST['HIassess'] + $_POST['HIinline'] + $_POST['HIlinked'] + $_POST['HIforum'] + $_POST['HIblock'];
		$avail = 3 - $_POST['stuavail'] - $_POST['teachavail'];
		
		$itemorder = addslashes(serialize(array()));
		$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,allowunenroll,copyrights,msgset,itemorder,topbar,cploc,available) VALUES ";
		$query .= "('{$_POST['coursename']}','$userid','{$_POST['ekey']}','$hideicons','{$_POST['allowunenroll']}','{$_POST['copyrights']}','{$_POST['msgset']}','$itemorder','$topbar','{$_POST['cploc']}','$avail');";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$cid = mysql_insert_id();
		//if ($myrights==40) {
			$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$cid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		//}
		$query = "INSERT INTO imas_gbscheme (courseid) VALUES ('$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "delete":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
		if ($myrights < 75) { $query .= " AND ownerid='$userid'";}
		if ($myrights == 75) {
			$query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
			} else {
				break;
			}
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_affected_rows()==0) { break;}
		
		$query = "SELECT id FROM imas_assessments WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line = mysql_fetch_row($result)) {
			$query = "DELETE FROM imas_questions WHERE assessmentid='{$line[0]}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$line[0]}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_exceptions WHERE assessmentid='{$line[0]}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		$query = "SELECT id FROM imas_forums WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
			$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
			while ($row2 = mysql_fetch_row($r2)) {
				$query = "DELETE FROM imas_forum_views WHERE threadid='{$row2[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "DELETE FROM imas_forums WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "DELETE FROM imas_assessments WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		//delete inline text files
		$query = "SELECT id FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
		$r3 = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($ilid = mysql_fetch_row($r3)) {
			$query = "SELECT filename FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
			while ($row = mysql_fetch_row($result)) {
				$safefn = addslashes($row[0]);
				$query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($r2)==1) {
					unlink($uploaddir . $row[0]);
				}
			}
			$query = "DELETE FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "DELETE FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		//delete linked text files
		$query = "SELECT text FROM imas_linkedtext WHERE courseid='{$_GET['id']}' AND text LIKE 'file:%'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$safetext = addslashes($row[0]);
			$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($r2)==1) { 
				$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
				$filename = substr($row[0],5);
				unlink($uploaddir . $filename);
			}
		}
		
		$query = "DELETE FROM imas_linkedtext WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_items WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_teachers WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_students WHERE courseid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "SELECT id FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$query = "DELETE FROM imas_grades WHERE gbitemid={$row[0]}";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "DELETE FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_gbscheme WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_gbcats WHERE courseid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		
		
		break;
	case "remteacher":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_teachers WHERE id='{$_GET['tid']}'";
		if ($myrights < 100) {
			$query = "SELECT imas_teachers.id FROM imas_teachers,imas_users WHERE imas_teachers.id='{$_GET['tid']}' AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$query = "DELETE FROM imas_teachers WHERE id='{$_GET['tid']}'";
			} else {
				break;
			}
			
			//$query = "DELETE imas_teachers FROM imas_users,imas_teachers WHERE imas_teachers.id='{$_GET['tid']}' ";
			//$query .= "AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
		exit;
	case "addteacher":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 75) {
			$query = "SELECT imas_users.groupid FROM imas_users,imas_courses WHERE imas_courses.ownerid=imas_users.id AND imas_courses.id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0) != $groupid) { 
				break;
			}
		}
		$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('{$_GET['tid']}','{$_GET['cid']}')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
		exit;
	case "importmacros":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname("../config.php"), '/\\') .'/assessment/libs/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.php')!==FALSE) {
				$handle = fopen($uploadfile, "r");
				$atstart = true;
				if ($handle) {
					while (!feof($handle)) {
						$buffer = fgets($handle, 4096);
						if (strpos($buffer,"//")===0) {
							$comments .= substr($buffer,2) .  "<BR>";
						} else if (strpos($buffer,"function")===0) {
							$func = substr($buffer,9,strpos($buffer,"(")-9);
							$outlines .= "<h3><a name=\"$func\">$func</a></h3>\n";
							$funcs[] = $func;
							$outlines .= $comments;
							$comments = '';
						} else if ($atstart && trim($buffer)=='') {
							$startcomments = $comments;
							$atstart = false;
							$comments = '';
						} else {
							$comments = '';
						}
					}
				}
				fclose($handle);
				$lib = basename($uploadfile,".php");
				$outfile = fopen($uploaddir . $lib.".html", "w");
				fwrite($outfile,"<html><body>\n<h1>Macro Library $lib</h1>\n");
				fwrite($outfile,$startcomments);
				fwrite($outfile,"<ul>\n");
				foreach($funcs as $func) {
					fwrite($outfile,"<li><a href=\"#$func\">$func</a></li>\n");
				}
				fwrite($outfile,"</ul>\n");
				fwrite($outfile, $outlines);
				fclose($outfile);
			}
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "transfer":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
		if ($myrights < 75) {
			$query .= " AND ownerid='$userid'";
		}
		if ($myrights==75) {
			$query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			//$query = "UPDATE imas_courses,imas_users SET imas_courses.ownerid='{$_POST['newowner']}' WHERE ";
			//$query .= "imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
		} else {
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		break;
	case "deloldusers":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$old = time() - 60*60*24*30*$_POST['months'];
		$who = $_POST['who'];
		if ($who=="students") {
			$query = "DELETE FROM imas_users WHERE lastaccess<$old AND (rights=0 OR rights=10)";
		} else if ($who=="all") {
			$query = "DELETE FROM imas_users WHERE lastaccess<$old AND rights<100";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "addgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "SELECT id FROM imas_groups WHERE name='{$_POST['gpname']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=listgroups\">Try again</a></body></html>\n";
			exit;
		}
		$query = "INSERT INTO imas_groups (name) VALUES ('{$_POST['gpname']}')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "modgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "SELECT id FROM imas_groups WHERE name='{$_POST['gpname']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=modgroup&id={$_GET['id']}\">Try again</a></body></html>\n";
			exit;
		}
		$query = "UPDATE imas_groups SET name='{$_POST['gpname']}' WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "delgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_groups WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_users SET groupid=0 WHERE groupid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_libraries SET groupid=0 WHERE groupid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "removediag";
		if ($myrights <75) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_diags WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
}

session_write_close();
if (isset($_GET['cid'])) {
	header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid={$_GET['cid']}");
} else {
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php");
}
exit;
?>
