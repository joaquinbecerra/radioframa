;<?php exit();?>

;Conf file
;All values are in name = "value" format
;These are the values that can't be stored in the DB. 
;You can make this readable by only the www server user if you want to prevent other users from seeing this user/password.


;Mysql user/password/db.  You must set up a user that can admin a db in mysql.  Enter the user/pass/db(schema) below.  Your web users for tcj are handled in the application.
username = "radioframa"
password = "radioframa"
defaultDB = "radioframa"



;The rest of these are optional and don't usually need to be set.



;host for mysql if different from local (local is defaulted)
;mysqlHost = '[host]'

;Web site address (this is the root address, without any paths -ie 'http://www.mydomain.com')
;Leave commented out to have this config auto detected (recommended).  If that doesn't work on your system for some reason, you can override here.
;siteAddress = "http://www.[YOURWEBSITE].com"

;Web site address to the root directory for this site, ie http://www.mydomin.com/tincanjukebox
;Don't include the trailing slash.
;Leave commented out to have this config auto detected (recommended).  If that doesn't work on your system for some reason, you can override here.
;fullSiteAddress = "http://www.[YOURWEBSITE].com/[ANY FOLDER PATH]"

;Auto login a user, bypassing all security checks.  This is to have your music server open to the entire internet (like if you want to demo your
;music or are on an intranet).  You need to login as an administrator first so you can create a user to be the 'auto-logged in user' and
;then copy that ID(listed in the user admin) and enter that below.
;If you want to admin the site, you'll have to comment out below and then log in as admin.  
;autoLoginUser=10;

;Rename this file to conf.php after you are done.


