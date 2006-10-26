#!/bin/sh
#
if [ $# -ne 1 ]; then 
  echo
  echo "Usage: $0 confname"
  echo
  echo "  where confname is the conference short name, e.g. xyz2007, etc."
  echo 
  echo "confname must consists of only alphanumeric characters. It would be"
  echo "used also as the database name and a suffix for the directory names."
  echo
  exit
fi
# abort this script on first error
set -e
#
# Aliases for generating random strings: choose whatever works on your system
# ---------------------------------------------------------------------------
# alias makepasswd="head -c9 /dev/urandom | b64encode - | tail -2 | head -1"
alias makepasswd="head -c8 /dev/urandom | od -t x8 | awk '{ print \$2 }'"
# alias makepasswd='echo $RANDOM$RANDOM$RANDOM'
#
# Default values for the directories: modify these
# ------------------------------------------------
defBaseDir="/var/www/secure/websubrev/0.6"
defUpldDir="/var/www/data/0.6"
defBaseURL="ornavella.watson.ibm.com/websubrev/0.6/"
echo
echo "Creating a New Submission-and-Review Site:"
echo "============================================================="
echo "To successfully run this script you must be able to create new"
echo "directories in the web tree and elsewhere, and also to chgrp"
echo "files to the web-server group. You must also know the MySQL root"
echo "password."
echo "============================================================="
echo
echo "You need to specify a directory in the web-tree where the new site"
echo "will reside (called the BASE directory, e.g., /var/www/html/MyConf),"
echo "as well as a local directory where the submissions will be uploaded"
echo "(called the UPLOAD directory, e.g., /var/www/data/MyConf). Note that"
echo "the web server must be able to write to the UPLOAD directory."
echo 
echo "Make sure that the directory names are absolute (i.e., they must"
echo "start with '/') and that they DO NOT end with '/'."
echo
read -p "BASE directory[$defBaseDir]: " baseDir
if [ -z $baseDir ]; then
  baseDir=$defBaseDir
fi
baseDir=$baseDir/$1
read -p "UPLOAD directory [$defUpldDir]: " subDir
if [ -z $subDir ]; then
  subDir=$defUpldDir
fi
subDir=$subDir/$1
echo
echo
echo "Specify now the URL of the base directory where the new site will"
echo "be available (caled the BASE URL, e.g., www.eaxmple.com/myConf/)."
echo "This should NOT include the protocol (http or https) and should end"
echo "with '/'"
echo 
read -p "BASE URL [$defBaseURL]: " baseURL
if [ -z $baseURL ]; then
  baseURL=$defBaseURL
fi
baseURL=${baseURL}$1/
echo
echo
echo "Now you need to specify some other system parameters, specifically"
echo "the name and password of the MySQL administrator, the group name of"
echo "the web-server, and the administrator's email address (i.e., who"
echo "should get the angry emails when there are problems with the site)."
echo 
read -p "MySQL username of an administrator [root]: " rootName
if [ -z $rootName ]; then
  rootName="root"
fi
read -s -p "MySQL password for that administrator: " rootPwd
if [ -z $rootPwd ]; then
  echo
  echo "You must specify the MySQL administrator password"
  exit
fi
echo
read -p "The web-server group name [apache]: " webSrv
if [ -z $webSrv ]; then
  webSrv="apache"
fi
me=$(whoami)
node=$(uname -n)
read -p "Administrator email [$me@$node]: " adm
if [ -z $adm ]; then
  adm="$me@$node"
fi

echo
echo
echo "Finally, please provide some names to use for the new site."
echo "The chair email address that you specify below can be later changed"
echo "by the program chair from the web interface."
echo

read -p "A name for the conference database (e.g., stoc08) [$1]: " dbName
if [ -z $dbName ]; then
  dbName=$1
fi
read -p "The program chair's email address [$adm]: " chrEml
if [ -z $chrEml ]; then
  chrEml="$adm"
fi

echo "================================================="
echo "PLEASE CONFIRM YOUR SELECTIONS BEFORE WE CONTINUE"
echo "================================================="
echo "BASE directory:        $baseDir" 
echo "UPLOAD directory:      $subDir" 
echo "BASE URL:              $baseURL" 
echo "MySQL admin usrname:   $rootName"
echo "MySQL database:        $dbName"
echo "Web-servre group name: $webSrv"
echo "Administrator email:   $adm"
echo "Chair email address:   $chrEml"
echo
read -p "Continue with this installation? (Y/N) [Y]: " yesno
if [ -z $yesno ]; then
  yesno="Y"
fi
if [ $yesno != "Y" -a $yesno != "y" ]; then
  exit
fi

# get some passwords: an initial pssword for the chair, a pasword for
# the database, the log file name and a "salt" value
chrPwd=$(makepasswd | head -c8)
dbPwd=$(makepasswd | head -c16)
logFile=$(makepasswd | head -c4)
salt=$(makepasswd)

# ===========================================================================
# We now have all the info that we need, let's create the new site
# ===========================================================================
echo 
echo -n "Creating/populating directory $baseDir in the web tree ... "
mkdir -p $baseDir
cp -r webtree/* $baseDir
echo "done"
echo
echo -n "Creating/populating database for the new conference ... "
mysql -u $rootName --password=$rootPwd -e "DROP DATABASE IF EXISTS $dbName"
mysql -u $rootName --password=$rootPwd -e "CREATE DATABASE IF NOT EXISTS $dbName"
mysql -u $rootName --password=$rootPwd -e "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbName.* TO '$dbName'@'localhost' IDENTIFIED BY '$dbPwd'";
mysql -u $rootName --password=$rootPwd $dbName < ./tools/database.sql
mysql -u $dbName --password=$dbPwd $dbName -e "UPDATE committee SET revPwd='$chrPwd', email='$chrEml' WHERE revId=1";
echo "done"
echo 
echo -n "Writing parameters to file confParams.php ... "
if [ -e $baseDir/init/confParams.php ]; then
  rm -f $baseDir/init/confParams.php
fi
# ---------------
echo "<?php"                                 > $baseDir/init/confParams.php
echo "/* Parameters for a new installation: this file is formatted as a PHP"  >> $baseDir/init/confParams.php
echo " * file to ensure that accessing it directly by mistake does not cause" >> $baseDir/init/confParams.php
echo " the server to send this information to a client."                      >> $baseDir/init/confParams.php
echo "MYSQL_HOST=localhost"                 >> $baseDir/init/confParams.php
echo "MYSQL_DB=$dbName"                     >> $baseDir/init/confParams.php
echo "MYSQL_USR=$dbName"                    >> $baseDir/init/confParams.php
echo "MYSQL_PWD=$dbPwd"                     >> $baseDir/init/confParams.php
echo "SUBMIT_DIR=$subDir"                   >> $baseDir/init/confParams.php
echo "LOG_FILE=$subDir/log$logFile"         >> $baseDir/init/confParams.php
echo "ADMIN_EMAIL=$adm"                     >> $baseDir/init/confParams.php
echo "CONF_SALT=$salt"                      >> $baseDir/init/confParams.php
echo "BASE_URL=$baseURL"                    >> $baseDir/init/confParams.php
echo " ********************************************************************/" >> $baseDir/init/confParams.php
echo "?>"                                   >> $baseDir/init/confParams.php
# ---------------
chgrp $webSrv $baseDir/init/confParams.php
chmod 640 $baseDir/init/confParams.php
echo "done"
echo
echo -n "Preparing the UPLOAD directory ... "
mkdir -p $subDir/backup
mkdir -p $subDir/final
cp $baseDir/init/.htaccess  $subDir
cp $baseDir/init/index.html $subDir
cp $baseDir/init/index.html $subDir/backup
cp $baseDir/init/index.html $subDir/final
now=$(date)
echo "$now, Log file created" >> $subDir/log$logFile
chgrp -R $webSrv $subDir
chmod -R g+w $subDir
echo "done"
echo
echo "************************************************************************"
echo "                     NEW SITE CREATED SUCCESSFULLY"
echo "************************************************************************"
echo "To complete the installation, direct the chair to the web-page at"
echo
echo "  ${baseURL}chair/customize.php."
echo 
echo "To access that page use username $chrEml and password $chrPwd"
echo "                        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
echo "                         NOTICE    NOTICE    NOTICE    NOTICE    NOTICE"
echo 
echo "You may want to consider modifying ownership/permissions on the BASE"
echo "and UPLOAD directories, to comply with the security policy of your site."
echo "(Currently you own everything in the both directories and $webSrv"
echo "group-owns everything in the UPLOAD directory.)"
echo 
