/*Curtesy of http://www.kryogenix.org/code/browser/sorttable/ 
Thanks guys!
*/

addEvent(window, "load", sortables_init);

var SORT_COLUMN_INDEX;

function sortables_init() {
    // Find all tables with class sortable and make them sortable
    if (!document.getElementsByTagName) return;
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("sortable") != -1) && (thisTbl.id)) {
            //initTable(thisTbl.id);
            ts_makeSortable(thisTbl);
        }
    }
}

function ts_makeSortable(table) {
    if (table.rows && table.rows.length > 0) {
        var firstRow = table.rows[0];
    }
    if (!firstRow) return;

    // We have a first row: assume it's the header, and make its contents clickable links
    for (var i=0;i<firstRow.cells.length;i++) {
        var cell = firstRow.cells[i];
        var txt = ts_getInnerText(cell);
	if(txt.indexOf("sortable") == -1){//skip if we've already done this table
	    cell.innerHTML = '<a href="#" class="sortheader" '+
	    'onclick="ts_resortTable(this, '+i+');return false;">' +
	    txt+'<span class="sortarrow">&nbsp;</span></a>';
	}
    }
}

function ts_getInnerText(el) {
    	if (typeof el == "string") return el;
	if (typeof el == "undefined") { return el };
	if (el.innerText) return el.innerText;	//Not needed but it is faster
	var str = "";

	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += ts_getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function ts_resortTable(lnk,clid) {
    // get the span
    var span;
    for (var ci=0;ci<lnk.childNodes.length;ci++) {
        if (lnk.childNodes[ci].tagName && lnk.childNodes[ci].tagName.toLowerCase() == 'span') span = lnk.childNodes[ci];
    }
    var spantext = ts_getInnerText(span);
    var td = lnk.parentNode;
    var column = clid || td.cellIndex;
    var table = getParent(td,'TABLE');

    // Work out a type for the column
    if (table.rows.length <= 1) return;
    var i=1;
    do{//loop thru finding the first non empty cell in this col so we can determine datatype
	var itm = ts_getInnerText(table.rows[i].cells[column]);
	i++;
    }while((itm=="") || (itm.charCodeAt(0)==160 && itm.length==1));//&nbsp or a ' ' commonly used to fill an empty <td>
    sortfn = ts_sort_caseinsensitive;
    if(!isNaN(Date.parse(itm)))sortfn = ts_sort_date;
    else if (itm.match(/^[£$]/)) sortfn = ts_sort_currency;
    else if (itm.match(/^[\d\.]+$/)) sortfn = ts_sort_numeric;
    //alert(sortfn);
    SORT_COLUMN_INDEX = column;
    var firstRow = new Array();
    var newRows = new Array();
    for (i=0;i<table.rows[0].length;i++) { firstRow[i] = table.rows[0][i]; }
    for (j=1;j<table.rows.length;j++) { newRows[j-1] = table.rows[j]; }

    newRows.sort(sortfn);

    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '&nbsp;&nbsp;&uarr;';
        newRows.reverse();
        span.setAttribute('sortdir','up');
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        span.setAttribute('sortdir','down');
    }

    // We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
    // don't do sortbottom rows
    for (i=0;i<newRows.length;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sortbottom') == -1))) table.tBodies[0].appendChild(newRows[i]);}
    // do sortbottom rows only
    for (i=0;i<newRows.length;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sortbottom') != -1)) table.tBodies[0].appendChild(newRows[i]);}

    // Delete any other arrows there may be showing
    var allspans = document.getElementsByTagName("span");
    for (var ci=0;ci<allspans.length;ci++) {
        if (allspans[ci].className == 'sortarrow') {
            if (getParent(allspans[ci],"table") == getParent(lnk,"table")) { // in the same table as us?
                allspans[ci].innerHTML = '&nbsp;&nbsp;&nbsp;';
            }
        }
    }

    span.innerHTML = ARROW;
}

function getParent(el, pTagName) {
	if (el == null) return null;
	else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
		return el;
	else
		return getParent(el.parentNode, pTagName);
}
function ts_sort_date(a,b){/*Sort 2 dates.. note that Date.parse will parse most date formats
    and return the number of seconds (+/-) since unix epoch.  The range of a js date is
    +/- 100,000 days from epoch (I believe);*/
    d1 = Date.parse(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    d2 = Date.parse(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    if((isNaN(d1))&&(isNaN(d2))){//neither a date
	return 0;
    }else if(isNaN(d1))return -1;
    else if(isNaN(d2)) return 1;
    else if(d1==d2)return 0;
    else if(d1<d2)return -1;
    return 1;

}

function ts_sort_currency(a,b) {
    aa = parseFloat(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,''));
    bb = parseFloat(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,''));
    if((isNaN(aa))&&(isNaN(bb))){//neither a number
	return 0;
    }else if(isNaN(aa))return -1;
    else if(isNaN(bb)) return 1;
    else return aa-bb;
}

function ts_sort_numeric(a,b) {
    aa = parseFloat(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    bb = parseFloat(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    if((isNaN(aa))&&(isNaN(bb))){//neither a number
	return 0;
    }else if(isNaN(aa))return -1;
    else if(isNaN(bb)) return 1;
    else return aa-bb;
}
function ts_sort_caseinsensitive(a,b) {
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).toLowerCase();
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).toLowerCase();
    if (aa==bb) return 0;
    if (aa<bb) return -1;
    return 1;
}

function ts_sort_default(a,b) {
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa==bb) return 0;
    if (aa<bb) return -1;
    return 1;
}


function addEvent(elm, evType, fn, useCapture)
// addEvent and removeEvent
// cross-browser event handling for IE5+,  NS6 and Mozilla
// By Scott Andrew
{
  if (elm.addEventListener){
    elm.addEventListener(evType, fn, useCapture);
    return true;
  } else if (elm.attachEvent){
    var r = elm.attachEvent("on"+evType, fn);
    return r;
  } else {
    alert("Handler could not be removed");
  }
}

