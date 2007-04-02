<?php
	require("../validate.php");
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
		
	$cid = $_GET['cid'];
	
	if (isset($_POST['export'])) {
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasitemexport.imas\"");
		
		$checked = $_POST['checked'];
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
		$itemcnt = 0;
		$toexport = array();
		$qcnt = 0;
		$items = unserialize(mysql_result($result,0,0));
		$newitems = array();
		function copysub($items,$parent,&$addtoarr) {
			global $itemcnt,$toexport;
			global $checked;
			foreach ($items as $k=>$item) {
				if (is_array($item)) {
					if (array_search($parent.'-'.($k+1),$checked)!=FALSE) { //copy block
						$newblock = array();
						$newblock['name'] = $item['name'];
						$newblock['startdate'] = $item['startdate'];
						$newblock['enddate'] = $item['enddate'];
						$newblock['SH'] = $item['SH'];
						$newblock['colors'] = $item['colors'];
						$newblock['items'] = array();
						copysub($item['items'],$parent.'-'.($k+1),$newblock['items']);
						$addtoarr[] = $newblock;
					} else {
						copysub($item['items'],$parent.'-'.($k+1),$addtoarr);
					}
				} else {
					$toexport[$itemcnt] = $item;
					$addtoarr[] = $itemcnt;
					$itemcnt++;
				}
			}
		}
		copysub($items,'0',$newitems);
		
		echo "EXPORT DESCRIPTION\n";
		echo $_POST['description']."\n";
		echo "ITEM LIST\n";
		echo serialize($newitems)."\n";
		foreach ($toexport as $exportid=>$itemid) {
			echo "BEGIN ITEM\n";
			echo "ID\n";
			echo $exportid."\n";
			$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			echo "TYPE\n";
			echo $row[0] . "\n";
			switch ($row[0]) {
				case ($row[0]==="InlineText"):
					$query = "SELECT * FROM imas_inlinetext WHERE id='{$row[1]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					$line = mysql_fetch_array($r2, MYSQL_ASSOC);
					echo "TITLE\n";
					echo $line['title'] . "\n";
					echo "TEXT\n";
					echo $line['text'] . "\n";
					echo "STARTDATE\n";
					echo $line['startdate'] . "\n";
					echo "ENDDATE\n";
					echo $line['enddate'] . "\n";
					echo "END ITEM\n";
					break;
				case ($row[0]==="LinkedText"):
					$query = "SELECT * FROM imas_linkedtext WHERE id='{$row[1]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					$line = mysql_fetch_array($r2, MYSQL_ASSOC);
					echo "TITLE\n";
					echo $line['title'] . "\n";
					echo "SUMMARY\n";
					echo $line['summary'] . "\n";
					echo "TEXT\n";
					echo $line['text'] . "\n";
					echo "STARTDATE\n";
					echo $line['startdate'] . "\n";
					echo "ENDDATE\n";
					echo $line['enddate'] . "\n";
					echo "END ITEM\n";
					break;
				case ($row[0]==="Forum"):
					$query = "SELECT * FROM imas_forums WHERE id='{$row[1]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					$line = mysql_fetch_array($r2, MYSQL_ASSOC);
					echo "NAME\n";
					echo $line['name'] . "\n";
					echo "SUMMARY\n";
					echo $line['description'] . "\n";
					echo "STARTDATE\n";
					echo $line['startdate'] . "\n";
					echo "ENDDATE\n";
					echo $line['enddate'] . "\n";
					echo "END ITEM\n";
					break;
				case ($row[0]==="Assessment"):
					$query = "SELECT * FROM imas_assessments WHERE id='{$row[1]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					$line = mysql_fetch_array($r2, MYSQL_ASSOC);
					echo "NAME\n";
					echo $line['name'] . "\n";
					echo "SUMMARY\n";
					echo $line['summary'] . "\n";
					echo "INTRO\n";
					echo $line['intro'] . "\n";
					echo "STARTDATE\n";
					echo $line['startdate'] . "\n";
					echo "ENDDATE\n";
					echo $line['enddate'] . "\n";
					echo "REVIEWDATE\n";
					echo $line['reviewdate'] . "\n";
					echo "SETTINGS\n";
					foreach (array("timelimit","displaymethod","defpoints","defattempts","deffeedback","defpenalty","shuffle","password","cntingb") as $setting) {
						echo "$setting=".$line[$setting]."\n";
					}
					echo "QUESTIONS\n";
					unset($newqorder);
					$qs = explode(',',$line['itemorder']);
					foreach ($qs as $q) {
						if (strpos($q,'~')===FALSE) {
							$qtoexport[$qcnt] = $q;
							$newqorder[] = $qcnt;
							$qcnt++;
						} else {
							unset($newsub);
							$subs = explode('~',$q);
							foreach($subs as $subq) {
								$qtoexport[$qcnt] = $subq;
								$newsub[] = $qcnt;
								$qcnt++;
							}
							$newqorder[] = implode('~',$newsub);
						}
					}
					echo implode(',',$newqorder) . "\n";
					echo "END ITEM\n";
					break;
			} //end item switch
		} // end item export
		
		foreach ($qtoexport as $exportid=>$qid) { //export questions
			echo "BEGIN QUESTION\n";
			echo "QID\n";
			echo $exportid . "\n";
			
			$query = "SELECT imas_questions.*,imas_questionset.uniqueid from imas_questions,imas_questionset ";
			$query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id='$qid'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($r2, MYSQL_ASSOC);
			
			echo "UQID\n";
			echo $line['uniqueid'] . "\n";
			echo "POINTS\n";
			echo $line['points'] . "\n";
			echo "PENALTY\n";
			echo $line['penalty'] . "\n";
			echo "ATTEMPTS\n";
			echo $line['attempts'] . "\n";
			echo "CATEGORY\n";
			echo $line['category'] . "\n";
			echo "END QUESTION\n";
			
			$qsettoexport[] = $line['questionsetid'];
		}
		
		foreach ($qsettoexport as $qsetid) { //export questionset
			echo "BEGIN QSET\n";
			
			$query = "SELECT * from imas_questionset WHERE id='$qsetid'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($r2, MYSQL_ASSOC);
			echo "UNIQUEID\n";
			echo $line['uniqueid'] . "\n";
			echo "LASTMOD\n";
			echo $line['lastmoddate'] . "\n";
			echo "DESCRIPTION\n";
			echo $line['description'] . "\n";
			echo "AUTHOR\n";
			echo $line['author'] . "\n";
			echo "CONTROL\n";
			echo $line['control'] . "\n";
			echo "QCONTROL\n";
			echo $line['qcontrol'] . "\n";
			echo "QTEXT\n";
			echo $line['qtext'] . "\n";
			echo "QTYPE\n";
			echo $line['qtype'] . "\n";
			echo "ANSWER\n";
			echo $line['answer'] . "\n";
			echo "END QSET\n";
			
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
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Export Course Items</div>\n";
	echo "<h3>Export Course Items</h3>\n";
	
	echo "<form method=post action=\"exportitems.php?cid=$cid\">\n";
	
	echo "<p>Export description<br/>\n";
	echo "<textarea rows=5 cols=50 name=description>Course Item Export</textarea></p>\n";
	echo "<p>Select items to export</p>\n";
	
	
	echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\" checked=checked>\n";

	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());

	$items = unserialize(mysql_result($result,0,0));
	$ids = array();
	$types = array();
	$names = array();
	function getsubinfo($items,$parent,$pre) {
		global $ids,$types,$names;
		foreach($items as $k=>$item) {
			if (is_array($item)) {
				$ids[] = $parent.'-'.($k+1);
				$types[] = $pre."Block";
				$names[] = stripslashes($item['name']);
				getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'--');
			} else {
				$ids[] = $item;
				$arr = getiteminfo($item);
				$types[] = $pre.$arr[0];
				$names[] = $arr[1];
			}
		}
	}
	getsubinfo($items,'0','');
	
	function getiteminfo($itemid) {
		$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemtype = mysql_result($result,0,0);
		$typeid = mysql_result($result,0,1);
		switch($itemtype) {
			case ($itemtype==="InlineText"):
				$query = "SELECT title FROM imas_inlinetext WHERE id=$typeid";
				break;
			case ($itemtype==="LinkedText"):
				$query = "SELECT title FROM imas_linkedtext WHERE id=$typeid";
				break;
			case ($itemtype==="Forum"):
				$query = "SELECT name FROM imas_forums WHERE id=$typeid";
				break;
			case ($itemtype==="Assessment"):
				$query = "SELECT name FROM imas_assessments WHERE id=$typeid";
				break;
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$name = mysql_result($result,0,0);
		return array($itemtype,$name);
	}
	
	echo "<table cellpadding=5 class=gb>\n";
	echo "<thead><tr><th></th><th>Type</th><th>Title</th></tr></thead><tbody>\n";
	$alt=0;
	for ($i = 0 ; $i<(count($ids)); $i++) {
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
		echo "<td>";
		echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' checked=checked>";
		echo "</td><td>{$types[$i]}</td><td>{$names[$i]}</td>\n";
		echo "</tr>\n";
	}
	echo "</tbody></table>\n";
	echo "<p><input type=submit name=\"export\" value=\"Export Items\"></p>\n";
	echo "</form>\n";
	echo "<p>Note: Export of questions with static image files is not yet supported</p>";
	require("../footer.php");
?>