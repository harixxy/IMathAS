var cnt = new Array();

function additem(inloc,outloc) {
	var text = document.getElementById(inloc).value;
	document.getElementById(inloc).value = '';
	var outn = document.getElementById(outloc);
	var newn = document.createElement("tr");
	var newid = outloc+'-'+cnt[outloc];
	cnt[outloc] += 1;
	newn.id = 'tr'+newid;
	var newtd = document.createElement("td");
	var html = "<input type=hidden name="+newid+" id="+newid+" value='"+text+"'>" + text;
	newtd.innerHTML = html;
	newn.appendChild(newtd);
	
	html = "  <a href='#' onclick=\"removeitem('"+newid+"','"+outloc+"')\">Remove</a>";
	html += " <a href='#' onclick=\"moveitemup('"+newid+"','"+outloc+"')\">Move up</a>";
	html += " <a href='#' onclick=\"moveitemdown('"+newid+"','"+outloc+"')\">Move down</a>";
	newtd = document.createElement("td");
	newtd.innerHTML = html;
	newn.appendChild(newtd);
	outn.appendChild(newn);
}
function onenter(e,inloc,outloc) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	if (key==13) {
		additem(inloc,outloc);
		return false;
	} else {
		return true;
	}
}
function removeitem(id,outloc) {
	var outn = document.getElementById(outloc);
	outn.removeChild(document.getElementById('tr'+id));
}
function moveitemup(id,outloc) {
	var outn = document.getElementById(outloc);
	var cur = document.getElementById('tr'+id);
	var prev = cur.previousSibling;
	if (prev != null) {
		outn.removeChild(cur);
		outn.insertBefore(cur,prev);
	}
}
function moveitemdown(id,outloc) {
	var outn = document.getElementById(outloc);
	var cur = document.getElementById('tr'+id);
	var next = cur.nextSibling;
	if (next != null) {
		outn.removeChild(cur);
		if (next.nextSibling!=null) {
			outn.insertBefore(cur,next.nextSibling);
		} else {
			outn.appendChild(cur);
		}
	}
}