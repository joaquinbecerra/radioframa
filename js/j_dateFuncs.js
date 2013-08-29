	/*
	Generic date parser/validator for html forms.
	
        You set date part order for parsing/redisplay below. can be: 
		'mdy' 'dmy' 'ymd' 
	
		yyyymmdd (no seporators) is always recognized as is yyyy/mm/dd no matter what the date part order is.
	
        If time is allowed, formats accepted/parsed are:
		"24hm""24hms" "24hms" "hm" "hms" (hm is standard 12 hr am/pm)
		
	It will accept most punctuation as a seporators.  And will default the year if left off. 
	
        If "." is entered it will return today's date.
        If time is allowd, ".." will return today's date and time.
        You can also do ". 2p" for today at 2pm.
        
	If dateValidate is called with allowTime=1 then time can be entered and parsed, otherwise time is not allowed.
	
	This will generate a db readable date(tested in sybase and mysql)
        in the hidden field "yyyymmdd_date"
		The format for this date is: yyyy/mm/dd 24h:mm:ss 
		
                If time is not allowed, then the time portion will be blanck (yyyy/mm/dd)
                
                
	To Use:
		link to this script inside of  <header></header> tags:
			<script language="JavaScript" src="js/j_dateFuncs.js"></script>
                        
                then in form:
        	<input name="date" id="date" value="" maxlength="30" size="30" onchange="dateValidate('1',this,'yyyymmdd_date');" title="mm/dd/yyyy" type="text">
		<input name="yyyymmdd_date" id="yyyymmdd_date" value="" type="hidden">
	
        Note; be sure to change the 'title' in the main date input if you change the date part order.
        */

var newDate;//declared globally
function dateValidate(allowTime,input,yyyymmdd_id){//yyyymmdd_id is the tag id of the field to recieve a db date in proper format.
	/*Set datePartOrder to what you want to interpret dates and then redisplay them as.
		example: if you set to 'mdy' and date of 1/4/05 is passed this gets parsed to jan 4,2005
			 if you had set to 'dmy' then 1/04/2005 is read as april 1, 2005	
	*/
	var datePartOrder="mdy";//mdy, dmy, ymd. 
	var timeFormat="hm";//can be "24hm""24hms" "24hms" "hm" "hms" 
	var errMsg="Sorry, unrecognized date format";
	var format=datePartOrder;//default..may get changed below.
	var dateStr=input.value,yyyymmddStr="";
	var rtn,now=new Date(),newDateStr="";
	var gotDate=false,gotTime=false;
	var year,month,day,hour=0,minute=0,second=0,ampm;
	var parts=new Array("","","","","","","");
	//first handle our special entries of "." ".." and ". 2p"
	if((dateStr.length>0)&&(dateStr.substring(0,1)=='.')){//default to today
		newDate=new Date();
		newDateStr=createDateString(newDate,datePartOrder);
		if(dateStr.length>1){//check for time too.
			if(allowTime){
				if(dateStr.substring(1,2)=='.'){
					newDateStr+=" "+createTimeString(newDate,timeFormat);							
				}else{
					if(dateStr.substring(1,2)!=" ")newDateStr+=" ";//add space separator if needed.
					newDateStr+=dateStr.substring(1,dateStr.length);//just append on the rest to pass along.
				}
			}else alert("Time is not allowed in this field. Try just '"+dateStr.substring(0,1)+"'");
		}
		dateStr=newDateStr;		
	}
	if((dateStr.length>0)&&(isSeporator(dateStr.substring(0,1)))){
		alert(errMsg);
		dateStr="";
	}
	switch (dateStr.length){
		case 0://just blanck out.
			newDateStr=""; gotDate=true; break;
		default :
			var last=0,counter=0;
			//parse out the different date parts.
			for(i=0;i<dateStr.length;i++){
				ch=dateStr.substring(i,i+1);
				if(isSeporator(ch) || isAMPM(ch)){
					parts[counter]=dateStr.substring(last,i);
					if(isAMPM(ch))last=i;//don't skip the ampm designatore, because we want to pick it up.
					else last=i+1;
					counter++;		
					if(ch==" " && counter<3)counter=3;//if this was a space, assume next part is a time measurement			
				}
			}
			//get the last bit or the whole bit if no separators..
			if(last<dateStr.length){
				if(counter>0 && parts[counter-1]=="")counter--;//if this is the last am/pm don't count the last increment
				parts[counter]=dateStr.substring(last,dateStr.length);				
			}
	
			//check for yyyymmdd and override format if so
			if(parts[0].length==8 && isNumeric(parts[0]))format="yyyymmdd";
	
			//check for yyyy/mm/dd and override format if so
			if(parts[0].length==4 && isNumeric(parts[0]))format="yyyy/mm/dd";
	
			newDate=new Date();
			switch (format){//extract out the dateparts based on our preset format.
				case "mdy":
					month=parts[0];
					day=parts[1];
					year=parts[2];
					break;			
				case "dmy":
					month=parts[1];
					day=parts[0];
					year=parts[2];
					break;
				case "ymd": 
					month=parts[1];
					day=parts[2];
					year=parts[0];
					break;
				case "yyyy/mm/dd":
					year=parts[0];
					month=parts[1];
					day=parts[2];
					break;
				case "yyyymmdd":
					year=parts[0].substring(0,4);
	                                month=parts[0].substring(4,6);
        	                        day=parts[0].substring(6,8);
					break;
			};
			if(year=="")year=""+now.getFullYear()+"";//need to turn into a string.
			if(year.length==2){
				if(year<50)year="20"+year;
				else year="19"+year;
			}else if(year.length==1)year="200"+year;
			month=month-1;//months are stored on scale of 0-11
			month=formatNumber(month);
			day=formatNumber(day);
			if(allowTime){
				if(parts[3]!=""){//any time entered? note; time entries will start in parts[3]
					gotTime=true;
					hour=parts[3];
					minute=parts[4];
					second=parts[5];
					ampm=parts[6].toLowerCase();
					if(isAMPM(minute)){
						ampm=minute.toLowerCase();
						minute="0";
						second="0";
					}
					if(isAMPM(second)){
						ampm=second.toLowerCase();
						second="0";
					}
					if((ampm=="pm" || ampm=="p")&& (hour<12))hour=new Number(hour)+12;//convert to 24 hour 
					else if(hour==12)hour="0";//if no ampm or if am, convert 12 to 0hour.
					hour=""+hour;//convert back to string.
				}
			}
			hour=formatNumber(hour);
			minute=formatNumber(minute);
			second=formatNumber(second);
			
			gotDate=createDate(year,month,day,hour,minute,second);
			if(gotDate){
				newDateStr=createDateString(newDate,datePartOrder);
				yyyymmddStr=createDateString(newDate,"ymd");
				if(allowTime && gotTime){
					newDateStr+=" "+createTimeString(newDate,timeFormat);
					yyyymmddStr+=" "+createTimeString(newDate,"24hms");
				}
			}
			break;
	};
	input.value=newDateStr;
	document.getElementById(yyyymmdd_id).value=yyyymmddStr;
	if(!gotDate){
		alert(errMsg);
		input.value='';
		setFocus("document."+input.form.name+"."+input.name);//pass the full text name to setFocus.
		return false;
	}
	return true;
}
function setFocus(inputName){//delay the focus to give everything a chance to run thru.  Had a nasty timing issue where tabbing out to next field occured after onChange script occured and we couldn't ever get the focus to stick.. This seems to solve the problme well.
	setTimeout(inputName+".focus()",100);
}
function formatNumber(num){
	if(num=="")num="00";
	else if(num=="0")num="00";
	else if(num<10 && num.length==1)num="0"+num;
	return num;
}
function createDate(year,month,day,hour,minute,second){
	//yyyy, mm, dd are required, rest are optional
	//format should be yyyy,mm,dd,hh,mm,ss.
	//note month is on a scale of 0-11.
	//could use better day filtering.. ie 1999 02 29 comes out as 3/1/1999
	newDate=new Date();
	var gotIt=false;		
	if(true){//not sure if i should do the range checks below...
//	if(isNumeric(year) && isNumeric(month) && month<12 && month>=0 && isNumeric(day) && day>0 && day<32 ){
		gotIt=newDate.setFullYear(year,month,day);
		if(arguments.length>3) newDate.setHours(hour);
		if(arguments.length>4)newDate.setMinutes(minute);
		if(arguments.length>5)newDate.setSeconds(second);
	}
	return gotIt;
}
function isSeporator(ch){
	if(ch=="." ||ch=="," || ch=="-" || ch==";" || ch==":" || ch=="/" || ch==" " || ch=="\\") return true;
	else return false;		
}
function isAMPM(ch){
	ch=ch.substring(0,1);
	ch=ch.toUpperCase();
	if(ch=="P" || ch=="A") return true;
	else return false;
}
function createTimeString(dDate,timeFormat){
	var ampm, h24;
	var h=dDate.getHours(),m=dDate.getMinutes(),s=dDate.getSeconds(),rtn="";
	h24=h;
	if(h>11) ampm="pm";
	else ampm="am";
	if(h>12)h=h%12;//convert to 12hr
	if(h==0)h=12;//change hour 0 to 12
	
	//alert("h24:"+h24+" h:"+h);
	
	if(s<10) s="0"+s;
	if(h<10) h="0"+h;
	if(m<10) m="0"+m;
	switch(timeFormat){
		case "24hm":
			rtn=h24+":"+m; break;
		case "24hms": 
			rtn=h24+":"+m+":"+s; break;
		case "hm":
			rtn=h+":"+m+" "+ampm;
			break;
		case "hms": 
			rtn=h+":"+m+":"+s+" "+ampm; break;
	};
	return rtn;
}

function createDateString(dDate,datePartOrder){
	var m=dDate.getMonth()+1, d=dDate.getDate(), y=dDate.getFullYear(), rtn="";
	if(m<10) m="0"+m;
	if(d<10) d="0"+d;
	switch (datePartOrder){
		case "mdy":
			rtn=m+"/"+d+"/"+y; break;			
		case "dmy":
			rtn=d+"/"+m+"/"+y; break;
		case "ymd": 
			rtn=y+"/"+m+"/"+d; break;	
	};
	return rtn;
}
function isNumeric(checkString){
    var newString = "";  
	for (i = 0; i < checkString.length; i++) {
        ch = checkString.substring(i, i+1);
		if (ch >= "0" && ch <= "9") {
            newString += ch;
        }
    }
	if (checkString == newString) return true;
	else return false;
}
