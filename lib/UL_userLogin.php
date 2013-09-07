<?php
/*
 This is a modular and easy to use login authentication system.  The goal was to
 encapsulate all the complicated logic, redirects, separate login pages, error
 handling, user administration, passwords...
 and make it idiot easy to restrict a site with user logins.  All you have to do
 is create a users table in a database and include this (details below) at the top of any
 page you want to restrict access to.  Idiot easy.

 Useage:
 Assumes database login has already occured using my standard db library (j_dbHead.php--currently handles sybase or mysql)
	(Note; The database access model employed here (because it's how I always do it:)
	is to have a single database user that all db interaction for the site occurs
	thru.  This user is logged in by the script at the start of the script session and then
	used throughout.  I usually have the login info in a non-web accessable folder
	somewhere, but not generally protected from other users on the system.  This
	has obvious backend security implications if you can't trust other users on the server...
	Caveat emptor.)
 Assumes there is a table set up in logged into db for user data.
	Default Table Structure:
	CREATE TABLE users (
		userID int(11) NOT NULL auto_increment,
		userName varchar(40) NOT NULL,
		`password` varchar(40) NOT NULL,
		admin tinyint(4) NOT NULL default '0',
		enabled tinyint(4) NOT NULL default '1',
		lastLogin date null,
		PRIMARY KEY  (userID),
		UNIQUE KEY userName (userName),
		[,fname varchar(100) default NULL,
		lname varchar(100) default NULL,
		...]
	)

You can have any number of additional text columns (name, phone, email...) in the table and these
	will be automatically included in the user profile edit form.  No number/date columns at this time.
	You can access these thru normal db lookups using the userID.
	Any col ending in "_hide" is ignored for the user profile edit.

Once user is validated, the php constant UL_UID is set with the userID and UL_ISADMIN with true if user is an admin.
User is stuck at login until validated.

Any parameters on original (get) query will be passed thru, even if login detour was needed.

To protect any page, include lines similar to below at head (must be absolute start of file, no spaces)
of each file to be protected.
	require_once("lib/j_dbHead.php");//required for various db convienences...
	getMysqlConnection("phpuser","php123","jDev");//creates a db connection that can be reused by subsequent scripts
	require_once("lib/UL_userLogin.php");UL_checkAuth("test");//user authentication.

I generally put these lines in a single file (ie- headerCheck.php) then just include that at the
top of every file in the directory structure:
<?require_once ("lib/headerCheck.php");?>

User Interface:
	You never need to access this page directly, in fact it can be in a non public www accessable area
	of the server as long as the user the server is running as can get at it.

	To access the admin/change user info or log off, just link to a page (like index.php, not this one)
	and include '&UL_showAdmin=1' or '&UL_logoff=1' in the url.

	When UL_showAdmin=1 is passed in the Query string (get) then the user administration screen will be shown.
		-If user has admin bit set to 1, they will get to edit all users (requires javascript)
		else, users can only edit themselves(js not required). Note; users will be allowed to edit any extra
		fields defined in the table.

	If UL_logoff=1 is passed in the Query string (get) then the user will be logged off and sent to login screen.

	You can do it like this on your index.php:
		<a href="index.php?UL_showAdmin=1"><?if(UL_ISADMIN==1)echo "User administration";else echo "Change password";?></a>
                <a href="index.php?UL_logoff=1">logout</a>

	Thats it :)

*/

function UL_checkAuth($moduleName,$allowStoredCookieLogin=true,$usersTableName="users",$userNameCol="userName",$passwordCol="password",$userIDCol="userID",$adminBitCol="admin",$enabledBitCol="enabled",$lastLoginCol="lastLogin"){
	/*
	$moduleName is a unique (on this server) name for the site so that cookies can be properly identified.. ie "phonebook"
	$allowStoredCookieLogin, if true will give user option of 'saving' login on their pc as a cookie and then
		using that each time they return instead of forcing a login.  This is considered pretty unsecure
		(because someone could just sit down at their pc and walk right in) and should
		only be used when guarded info is trivially sensitive.  Of course it's very convienent too:)
		You have been warned.  Currently only implemented on mysql dbs (or any db that does md5() natively).
		Defaults to true.
	$usersTableName is the name of the table that holds user info in the currently logged into database (dbHead).
		Defaults to "users".
	$userNameCol is the name of the user name column in above table.  This must be atleast 40 chars.
		Defaults to "userName".
	$passwordCol is, you guessed it, the password col.  This must be atleast 40 chars.
		Passwords will be encrypted using standard md5() hash and therefore are not considered 'strong' encryption.
		Defaults to "password".
	$userIDCol is the ID column.  This is an INT or larger.
		This must be auto incremented by the database.
		Defaults to "userID".
	$adminBitCol is colname of an int (can be 1 bit) to say this user has admin rights.
		Defaults to "admin".
	$enabledBitCol is colname of an int(can be 1 bit) to say if this user is enabled.
		Defaults to "enabled".
	$lastLoginCol is colname of a datetime to record the last time user logged in.
		Defaults to "lastLogin".  If blank then ignored.

	This method starts the session for the script and then closes it again when it's done.  You'll need to call session_start
	again if you want to use session variables.  You may need to load class objs prior to calling
	this file if they are stored in the $_SESSION array (so session_start can create them), I'm not sure.
	*/
	
	session_start();

	//Get the goto page and query string, either from the login form or from calling page.
	$UL_gotoPage=(isset($_POST['UL_gotoPage']))?$_POST['UL_gotoPage']:$_SERVER['PHP_SELF'];

	//Deal with the query string, either from query or in form post (passed thru).
	if($_REQUEST['UL_logoff']==1){$UL_queryString="";}//blank out if user is logging out.
	else {$UL_queryString=(isset($_POST['UL_queryString']))?$_POST['UL_queryString']:$_SERVER['QUERY_STRING'];}

	// Var/cookie names for this mod
	$cookieName='UL_'.$moduleName.'_hash';
	$sessionIDVar='UL_'.$cookieName.'_UID';
	$sessionISAdminVar='UL_'.$cookieName.'_ISADMIN';

	//find out if caller is requesting the admin page.
	parse_str($UL_queryString,$UL_queryVars);//parse out the query string (from post or caller) and load into an array.
	$UL_doAdmin=(($UL_queryVars['UL_showAdmin']==1)||($_POST['UL_showAdmin']==1));//could be coming on standard get string or in post from form...  can't just look at request because could be embedded in a queryString form var.

	$UL_userID=false;

	if($_SESSION[$sessionIDVar]!=""){//Attempt to load user id from previously stored session var.
		$UL_userID=$_SESSION[$sessionIDVar];
		$isAdmin=$_SESSION[$sessionISAdminVar];
		$isEnabled=true;
	}
	if((!$UL_userID)&&($allowStoredCookieLogin)){//check for existance of long term cookie (not session)
		if((isset($_COOKIE[$cookieName]))&&($_COOKIE[$cookieName]!="")){//verify it's valid
			if(UL_verifyUPContent($_COOKIE[$cookieName],1)==""){
				$sql="select $userIDCol as uid,$adminBitCol as admin,$enabledBitCol as enabled from $usersTableName where md5(concat($userNameCol,$passwordCol))='".$_COOKIE[$cookieName]."' ";
				//var_dump($sql);exit;
				$a=dosql($sql,1);
				if($a){
					extract($a);
					$UL_userID=$uid;
					$isAdmin=($admin==1);
					$isEnabled=($enabled==1);
				}else{$errMsg="Your saved login information is no longer valid.";}
			}else{$errMsg="That was a very bad cookie.";}
		}
	}
	if($UL_userID===false){//check for posted username and pass to check
		if(isset($_POST['UL_userName'])&& ($_POST['UL_userName']!="")&& isset($_POST['UL_password']) && ($_POST['UL_password']!="")){
			$errMsg=UL_verifyUPContent($_POST['UL_userName'],1);//min 1 char entered.. the password entry below, might restrict this to a higher min..
			if($errMsg=="")$errMsg=UL_verifyUPContent($_POST['UL_password'],1);
			if($errMsg==""){//attempt the login.
				$sql="select $userIDCol as uid,$adminBitCol as admin,$enabledBitCol as enabled from $usersTableName where $userNameCol='".$_POST['UL_userName']."' and $passwordCol='".md5($_POST['UL_password'])."' ";
				$a=dosql($sql,1);
				if($a){
					extract($a);
					$UL_userID=$uid;
					$isAdmin=($admin==1);
					$isEnabled=($enabled==1);
				}else{$errMsg="Unknown username/password.";}
			}
		}
	}
	if($UL_userID!==false && $isEnabled==false){//valid user, but not enabled.. drop out with error and exit.
		echo UL_getHTMLHeader("Unauthorized","")."<h3>Sorry, your login has been disabled.  Please contact the system administer</h3>".UL_getHTMLfooter();
		exit();//Prevent anything else (in page that included this) from running.
	}elseif(($UL_userID===false && $_POST['UL_updateMode']!="insert")||($_REQUEST['UL_logoff']==1)){
		/*Not authenticated successfully or logging out.. put up a login form and any error message.
		 May also be special case of first user inserted...
		 This login form should probably be a template or something:)*/

		//Clear the users saved cookie if there.
		if(($_REQUEST['UL_logoff']==1) && isset($_COOKIE[$cookieName])){
			setCookie($cookieName,"",time() - 3600,"/");
		}
		session_unset();//clear out anything that may currently be in session.
		$html=UL_getHTMLHeader("User Login","UL_userName");

		//Do a special check to see if any users exists and if not then let this first guy be the admin.
		if(dosql("select count(*) from $usersTableName",0)==0){
			$html.="<h3>You need to add an admin user</h3>".UL_getEditForm("",$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,true,$adminBitCol,$enabledBitCol,$lastLoginCol);
		}else{
			$cookieCheckBox=($allowStoredCookieLogin)?"<input type='checkbox' name='UL_setCookie' value='1'><font color='#5B769D'>Remember login</font>":"&nbsp";
			$html.="<font color-='red'>&nbsp $errMsg &nbsp</font>
			<h3><font color='Navy'>Please Login</font></h3>
			<form action='".$_SERVER['PHP_SELF']."' method='post' name='UL_defaultForm'>
				<input type='hidden' name='UL_gotoPage' value='$UL_gotoPage'>
				<input type='hidden' name='UL_queryString' value='$UL_queryString'>
				<table>
					<tr><td class='textboxPrompt'>UserName:</td><td class='textbox'><input type='text' name='UL_userName'></td></tr>
					<tr><td class='textboxPrompt'>Password:</td><td class='textbox'><input type='password' name='UL_password'></td></tr>
					<tr><td></td><td></td></tr>
					<tr><td><input type='submit' value='Login'></td><td class='textboxPrompt'>$cookieCheckBox</td></tr>
				</table>
			</form>
			";
		}
		$html.=UL_getHTMLFooter();
		echo $html;
		exit();//Prevent anything else (in page that included this) from running.

	}else{//Authenticated or very first user.
		if($UL_userID!==false && $isEnabled){//if authenticated set session/constant vars and cookie if approriate
			if(($_REQUEST['UL_setCookie']=='1')&&($allowStoredCookieLogin)){//set cookie checked?
				$a=dosql("select md5(concat($userNameCol,$passwordCol)) from $usersTableName where $userIDCol=$UL_userID",0);
				$expire=time()+(3600*24*365*10);//10 yrs.
				$ateCookie=setCookie($cookieName,$a,$expire,"/");
			}
			$_SESSION[$sessionIDVar]=$UL_userID;//save off into a session var to authenticate next page session.
			$_SESSION[$sessionISAdminVar]=$isAdmin;

			define(UL_UID,$UL_userID);//Set constant with this user's id for use in this script session.
			define(UL_ISADMIN,$isAdmin);

			if($lastLoginCol!=""){//set the last login timestamp, if needed.
				$currDate=getDBDate(getdate());
				dosql("update $usersTableName set $lastLoginCol='$currDate' where $userIDCol=$UL_userID");
			}
		}else{//clear out everything.
			$UL_userID=false;
			$isAdmin=false;
			define(UL_UID,false);
			define(UL_ISADMIN,false);
			unset($_SESSION[$sessionIDVar]);
			unset($_SESSION[$sessionISAdminVar]);
		}
		if($UL_doAdmin){//Show admin page(s)?
			$adminAction=$_POST['UL_adminAction'];
			$editUserID=$_POST['UL_editUserID'];
			switch ($adminAction){
				case "editUser":
					$html.=UL_getHTMLHeader("User Administration",$userNameCol);
					$html.=UL_getEditForm($editUserID,$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol);
					break;
				case "submitUserEdit":
					$html.=UL_getHTMLHeader("User Administration","");
					$html.=UL_submitEditForm($usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol,$moduleName);
					break;
				case "deleteUser":
					$html.=UL_getHTMLHeader("User Administration","");
					$UL_editUserID=$_POST['UL_editUserID'];
					if($UL_editUserID){
						$userName=dosql("select $userNameCol from $usersTableName where $userIDCol=$UL_editUserID",0);
						$html.="<form action='".$_SERVER['PHP_SELF']."' method='post' name='UL_defaultForm' id='UL_defaultForm'><input type='hidden' name='UL_showAdmin' value='1'><input type='hidden' name='UL_adminAction' value='reallyDeleteUser'><input type='hidden' value='$UL_editUserID' name='UL_editUserID' id='UL_editUserID'><h3>Really delete $userName?</h3><input type='submit' value='Delete'></form>";
					}
					break;
				case "reallyDeleteUser":
					$html.=UL_getHTMLHeader("User Administration","");
					$UL_editUserID=$_POST['UL_editUserID'];
					if($UL_editUserID){
						$deleted=dosql("delete from $usersTableName where $userIDCol=$UL_editUserID");
						if($deleted)$html.="This user has been deleted.";
						else $html.="There was some sort of error deleting this user... errp.";
					}
					break;
				default:
					if($isAdmin){
						$html.=UL_getHTMLHeader("User Administration","");
					$html.=UL_getUserList($usersTableName,$userIDCol,$isAdmin,$userNameCol,$enabledBitCol,$moduleName);
					}else{
						$html.=UL_getHTMLHeader("User Administration",$userNameCol);
						$html.=UL_getEditForm($UL_userID,$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol);
					}
					break;
			}
			echo $html."<br><br>".UL_getCancelHTML($UL_gotoPage).UL_getHTMLfooter();
			exit();//Prevent anything else (in page that included this) from running.
		}elseif($_POST['UL_gotoPage']!=""){/*If this post var was set, then we've come from
			one or more login form reloads.  On success, we redirect to the gotopage,
			which was the original requested page.*/
			header("Location: ".$_POST['UL_gotoPage']."?".$_POST['UL_queryString']);
		}//else, just fall thru to the calling script to continue on.
	}
	session_write_close();//write and close the session.
}
function UL_verifyUPContent($str,$minlength=4){/*Verifies that a username and password have valid content and does a minimal strength test.  
					$str  must have atleast $minlength letters or numbers and can include a space but not at the beginning or end.
					This is mostly to protect against sql injection attack, not for strength test.
					Returns an empty string if all ok, the error message if not.
					Note that we also use this same method to check that stored cookies are valid,
					which in our case should be a md5 (32 char hex-dec num)*/
	$count=0;
	if($str=="")return "Empty username/password not allowed.";
	if(rtrim(ltrim($str))!==$str)return "Spaces are not allowed at the beggining or end of the username/password.";
	for($x=0;$x<strlen($str);$x++){
		$n=ord($str[$x]);
		if(($n==32) || ($n>=48 && $n<=57) || ($n>=65 && $n<=90) || ($n>=97 && $n<=122)){//This should probably be done with preg_match but I dislike that syntax
			$count++;
		}else return "Invalid character in the username/password.  Legal values are letters, numbers or embedded spaces.";
	}
	if($count<$minlength)return "Username/password is too short.";
	return "";//Success!
}
function UL_getUserList($usersTableName,$userIDCol,$isAdmin,$userNameCol,$enabledBitCol,$moduleName){
	if($isAdmin){
		$html.="<div align='center'><h3>'".ucfirst($moduleName)."' user administration.  Click a row to edit a user's profile.</h3>";
		$sql="select *,case when $enabledBitCol=0 then 'j_printTable_red' else 'j_printTable_green' end as ".$enabledBitCol."_tdclass from $usersTableName order by $enabledBitCol desc,$userNameCol";
		$html.=j_printTable(dosql($sql),"j_printTable",true,"javascript:UL_userRowClicked('key');",$userIDCol."s");
		$html.="<form action='".$_SERVER['PHP_SELF']."' method='post' name='UL_defaultForm' id='UL_defaultForm'>
                        <input type='hidden' name='UL_showAdmin' value='1'>
                        <input type='hidden' name='UL_updateMode' value='update'>
                        <input type='hidden' name='UL_adminAction' value='editUser'>
                        <input type='hidden' value='' name='UL_editUserID' id='UL_editUserID'>
                        <input type='button' value='Add' onClick=\"
                            var f=document.getElementById('UL_defaultForm');
                                f.UL_editUserID.value='';
                                f.UL_updateMode.value='insert';
                                f.submit();\"</form>";
		$html.="</div><script language='javascript'>function UL_userRowClicked(key){document.UL_defaultForm.UL_editUserID.value=key;document.UL_defaultForm.submit();	}</script>";
	}else $html="";
	return $html;
}
function UL_submitEditForm($usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol,$moduleName=""){
	//var_dump($_POST);exit;
	$html="";
	//If inserting or if admin is updating or insertint, then verify username, otherwise skip because it's not posted.
	if($isAdmin)$html=UL_verifyUPContent($_POST[$userNameCol]);
	
	if($html=="")$html=UL_verifyUPContent($_POST[$passwordCol]);
	
	if($html!=""){//insert requires username, edits require pass
		$html.=UL_getEditForm($_POST['UL_editUserID'],$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol);
	}else{
		if($_POST['UL_updateMode']=="insert"){
			if(($isAdmin)||(dosql("select count(*) from $usersTableName",0)==0)){//check to see if admin or this is the special 'no users yet' situation...
				if(dosql("select count($userIDCol) from $usersTableName where $userNameCol='".$_POST[$userNameCol]."'",0)>0){
					$html.="User name '".$_POST[$userNameCol]."' already exists".UL_getEditForm($_POST['UL_editUserID'],$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol);
				}else{
					$admin=(!$_POST[$adminBitCol])?0:$_POST[$adminBitCol];
					$sql="insert $usersTableName set $userNameCol='".$_POST[$userNameCol]."',$passwordCol='".md5($_POST[$passwordCol])."',$adminBitCol=$admin,$enabledBitCol=1 ";
					$a=dosql($sql);
					if($a==1)$html.="<b><i>Successfully submitted</b></i>".UL_getUserList($usersTableName,$userIDCol,$isAdmin,$userNameCol,$enabledBitCol,$moduleName);
					else $html.="There was some sort of error.";
				}
			}else $html="<h3>We know where you live</h3>";//should not ever be possible..
		}elseif(($_POST['UL_updateMode']=="update") && ($isAdmin || $_POST['UL_editUserID']===UL_UID)){//only allow edit for self unless admin... this is just a safeguard, shouldn't be able to get here.
			$sets="";
			foreach($_POST as $col=>$val){
				if(($col!="UL_gotoPage")&&($col!="UL_updateMode")&&($col!=$userIDCol)&& ($col!="UL_editUserID")&&($col!="UL_adminAction")&&($col!="UL_showAdmin")){
					if($col==$passwordCol){
						$currpass=dosql("select $passwordCol from $usersTableName where $userIDCol=".UL_SQLquote($_POST['UL_editUserID']),0);
						if($val!=$currpass)$val=md5($val);//encrypt any new passwords.  Old passwords will already be the md5'd password (why we just compare to currpass).
					}
					$val=UL_SQLquote($val);//sanitize.. this could remove or change some user data...
					$sql=UL_appendToList($sql,"$col='$val'",",");
				}
			}
			if(!$_POST[$adminBitCol])$sql=UL_appendToList($sql,"$adminBitCol=0",",");//if not set or is zero, include
			if(!$_POST[$enabledBitCol])$sql=UL_appendToList($sql,"$enabledBitCol=0",",");//if not set or is zero, include

			$sql="update $usersTableName set ".$sql." where $userIDCol=".UL_SQLquote($_POST['UL_editUserID']);
			$a=dosql($sql);
			if($a==1){
				$html.="<b><i>Successfully submitted</b></i>".UL_getUserList($usersTableName,$userIDCol,$isAdmin,$userNameCol,$enabledBitCol,$moduleName);
			}elseif($a==0){
				$html.="<h3>No changes made</h3>".UL_getUserList($usersTableName,$userIDCol,$isAdmin,$userNameCol,$enabledBitCol,$moduleName);
			}else{
				$html.="<h3>There was an error submitting your changes</h3>".UL_getEditForm($_POST['UL_editUserID'],$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol);
			}

		}else $html="You do not have access to this user's record.";
	}
	return $html;
}
function UL_getCancelHTML($UL_gotoPage){
	return "<div align='center'><a class='j_href_btn' href='$UL_gotoPage'>Done/Cancel</a></div>";
}
function UL_getEditForm($editUserID,$usersTableName,$userIDCol,$userNameCol,$passwordCol,$UL_gotoPage,$isAdmin,$adminBitCol,$enabledBitCol,$lastLoginCol){
	$a=false;
	if($editUserID){
		$a=dosql("select * from $usersTableName where $userIDCol=$editUserID",1);
		if(!a) return "Unkown User ?!?";
		$html.="<h3>Edit Profile for $a[$userNameCol]</h3>";
	}else $html.="<h3>Add new user</h3>";
	$html.="<hr width='150 px'>
		<form action='".$_SERVER['PHP_SELF']."' method='post' name='UL_defaultForm' AutoComplete='off'>
			<input type='hidden' name='UL_gotoPage' value='$UL_gotoPage'>
			<input type='hidden' name='UL_editUserID' value='$editUserID'>
			<input type='hidden' name='UL_adminAction' value='submitUserEdit'>
			<input type='hidden' name='UL_showAdmin' value='1'>
			<input type='hidden' name='UL_updateMode' value='update'>
			<table>
	";
	if($a){
		foreach($a as $col=>$val){
			if(($col!=$userIDCol) && ($isAdmin|| ($col!=$userNameCol && $col!=$adminBitCol && $col!=$enabledBitCol && $col!=$lastLoginCol && $col!=$userNameCol)) && (strrpos($col,"_hide")===false) ){
				switch($col){
					case $passwordCol:
						$html.="<tr><td class='textboxPrompt'>Password</td><td class='textbox'><input type='password' name='$col' value='$val' onFocus=\"this.select();\"></td></tr>";
						break;
					case $userNameCol:
						$html.="<tr><td class='textboxPrompt'>User Name</td><td class='textbox'><input type='text' name='$col' value='$val'></td></tr>";
						break;
					case $adminBitCol:
						$checked=($val==1)?"checked":"";
						$html.="<tr><td class='textboxPrompt'>Admin</td><td class='textbox'><input type='checkbox' name='$col' $checked value='1'></td></tr>";
						break;
					case $enabledBitCol:
						$checked=($val==1)?"checked":"";
						$html.="<tr><td class='textboxPrompt'>Enabled</td><td class='textbox'><input type='checkbox' name='$col' $checked value='1'></td></tr>";
						break;
					default:
						$html.="<tr><td class='textboxPrompt'>".ucfirst($col)."</td><td class='textbox'><input type='text' name='$col' value='$val'></td></tr>";
						break;
				}
			}
		}
		if($isAdmin){
			$html.="<tr><td colspan='2'><br><br></td></tr><tr><td class='textboxPrompt'>Delete User?</td><td class='textbox'><input type='checkbox' name='deleteMe' value='1' onClick='UL_deleteMeClicked(this.form);'></td></tr>";
			$html.="<script language='javascript'>function UL_deleteMeClicked(form){if(form.deleteMe.checked){form.UL_adminAction.value='deleteUser';}else{form.UL_adminAction.value='submitUserEdit';}}</script>";
		}
	}else{//user add...just do username, pass  and admin.
		$html.="<tr><td class='textboxPrompt'>User Name</td><td class='textbox'><input type='text' name='$userNameCol' value=''></td></tr>";
		$html.="<tr><td class='textboxPrompt'>Password</td><td class='textbox'><input type='password' name='$passwordCol' value=''></td></tr>";
		if(dosql("select count(*) from $usersTableName",0)==0){//special no users yet.. force this first user to be an admin
			$html.="<input type='hidden' name='$adminBitCol' value='1'>";
		}else{
			$html.="<tr><td class='textboxPrompt'>Admin</td><td class='textbox'><input type='checkbox' name='$adminBitCol' value='1'></td></tr>";
		}
		$html.="<input type='hidden' name='UL_updateMode' value='insert'>";
	}

	$html.="<tr><td colspan='2' align='center'><input type='submit' value='submit'></td></tr></table>";

	return $html;
}
function UL_getHTMLHeader($title="User Login",$default){//returns top portion of html including opening body tag.
	$html.="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
			<html><head><title>$title</title>
			<script src='js/j_sortable.js' type='text/javascript'></script>
			<link rel='stylesheet' href='skins/Default/styles.css' type='text/css'>
			</head>";
	if($default)$html.="<body onload='document.UL_defaultForm.$default.focus();'>";
	else $html.="<body>";
	$html.="<br><br><br><br><div align='center'>";
	return $html;
}
function UL_getHTMLfooter(){//returns closing tags for html
	return "</div></body></html>
	";
}
function UL_appendToList($str1,$str2,$delim){
    /*add item to a text variable.
    Returns     $str1+$delim+$str2 if 1 and 2 are non null;
                            $1 if $2 is null
                            $2 if $1 is null
                            "" if both are null
    */
    $text="";
    if (($str1!="") & ($str2!="")){
            $text= $str1.$delim.$str2;
    }else{
            $text= $str1.$str2;
    }
    return $text;
}

function UL_SQLquote( $value )
{
    if( get_magic_quotes_gpc() )
    {
          $value = stripslashes( $value );
    }
    //check if this function exists
    if( function_exists( "mysql_real_escape_string" ) )
    {
          $value = mysql_real_escape_string( $value );
    }
    //for PHP version < 4.3.0 use addslashes
    else
    {
          $value = addslashes( $value );
    }
    return $value;
}

?>
