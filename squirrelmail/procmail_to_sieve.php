<?php
/*
  AlternC - Web Hosting System
  Copyright (C) 2002 by the AlternC Development Team.
  http://alternc.org/
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
  Purpose of file: Based on procmail-builder module, converts 
  the procmail generated by AlternC<=1.0.3 to AlternC>=3.0
  ----------------------------------------------------------------------
*/

/* ----------------------------------------------------------------- */
/**
   MAIN FUNCTION 
   Read all the mail folders and search for .procmailrc's
*/
function procmail2sieve() {
  global $ROOT;
  $d=opendir($ROOT);
  if ($d) {
    while ($c=readdir($d)) {
      if (substr($c,0,1)==".") continue; // skip hidden files
      if (is_dir($ROOT."/".$c)) {
	// Go to level 2.
	$e=opendir($ROOT."/".$c);
	if ($e) {
	  while ($f=readdir($e)) {
	    if (substr($f,0,1)==".") continue; // skip hidden files
	    if (is_file($ROOT."/".$c."/".$f."/.procmailrc")) {
	      // We found one .procmailrc, let's parse it on behalf of his user...
	      parseOneProcmail($f);  /* ################## SUB FUNCTION ###################### */
	    }
	  }
	  closedir($e);
	}
	
      }
    }
    closedir($d);
  }
} /* procmail2sieve */



/* ----------------------------------------------------------------- */
/** Parse ONE procmailrc, and write its sieve rules
 */
function parseOneProcmail($user) {
  global $SIEVEROOT;
  if ($rules=readrules($user)) {   /* ################## SUB FUNCTION ###################### */
    for($i=0; $i<count($rules); $i++) {
      list($rules[$i]["conds"],$rules[$i]["actionparam"])=describe($rules[$i]);   /* ################## SUB FUNCTION ###################### */
    }

    // Now we have $rule["type"] = the ACTION to accomplish + (if 1 or 4) $actionparam
    // and a list of $rule["conds"][0]=condition type & $rule["conds"][1]=condition parameter (if not 5)
    // Let's write a sieve script :) 
    $f=fopen($SIEVEROOT."/".$user,"wb");
    if (!$f) {
      echo "ERROR: Can't open '$user' in '$SIEVEROOT' for writing\n";
    } else {
      echo "OK: writing sieve script for $user (".count($rules)." rules)\n";
      
      // FIXME: DO IT :) 
      
      fclose($f);
    }
  } else {
    echo "ERROR: can't read rules for $user\n";
  }

} /* parseOneProcmauil */



/* ----------------------------------------------------------------- */
/** Read rules, fill an array 
    from m_procmail.php original file
    yeah I know, ereg() is deprecated ;)
*/
function readrules($user="") {
  if (!$user) $user=$this->user;
  $u=substr($user,0,1);
  if (!file_exists("/var/alternc/mail/$u/$user/.procmailrc")) {
    return false;
  }
  $f=fopen("/var/alternc/mail/$u/$user/.procmailrc","rb");
  $state=0;	$rulenum=0;	$ligne=0;
  $res=array();
  while (!feof($f)) {
    $found=false; // found allow us to know if we found something for each loop
    $s=fgets($f,1024);
    $s=trim($s);
    if ($state==1 && !ereg("^# RuleEnd$",$s)) {
      $res[$rulenum]["rule"][$res[$rulenum]["count"]++]=$s;
      $found=true;
    }
    if ($state==1 && ereg("^# RuleEnd$",$s)) { 
      $state=0;
      $rulenum++;
      $found=true;
    }
    if ($state==0 && ereg("^# RuleType ([0-9][0-9]) -- (.*)?$",$s,$r)) {
      $state=1;
      $res[$rulenum]["type"]=$r[1];
      $res[$rulenum]["name"]=$r[2];
      $res[$rulenum]["count"]=0;
      $found=true;
    }
    if (!$found && $state!=0) {
      return false;
    }
    $ligne++;
  }
  fclose($f);
  return $res;
}


/* ----------------------------------------------------------------- */
/** Take ONE rule array, extract properly 
    returns one array with the conditions (which are arrays with Condition Type and Value)
    and the action parameter
*/
function describe($rule) {

  // Lecture des conditions : 
  $cond=array();
  switch ($rule["type"]) {
  case 5: 
    $i=1;
    while ($rule["rule"][$i]!="* !^FROM_DAEMON" && $rule["rule"][$i]!="") {
      $cond[]=$rule["rule"][$i];
      $i++;
    }
    break;
  default:
    $i=1;
    while (substr($rule["rule"][$i],0,1)=="*") {
      $cond[]=$rule["rule"][$i];
      $i++;
    }
    break;
  }

  // $cond is an array of conditions
  // let's parse the condition : (see arrays at the top of this file)
  $conds=array();

  for($i=0;$i<count($cond);$i++) {
    if (ereg("^\\* \\^Subject\\.\\*(.*)$",$cond[$i],$t)) {
      $conds[]=array( 0, $t[1] );
    }
    if (ereg("^\\* \\^From\\.\\*(.*)$",$cond[$i],$t)) {
      $conds[]=array( 1, $t[1] );
    }
    if (ereg("^\\* \\^TO_(.*)$",$cond[$i],$t)) {
      $conds[]=array( 2, $t[1] );
    }
    if (ereg("^\\* \\^List-Post: (.*)$",$cond[$i],$t)) {
      $conds[]=array( 3, $t[1] );
    }
    if (ereg("^\\* \\^List-Id: (.*)$",$cond[$i],$t)) {
      $conds[]=array( 4, $t[1] );
    }
    if (ereg("^\\* \\^X-Spam-Status: Yes$",$cond[$i])) {
      $conds[]=array( 5 );
    }
    if (ereg("^\\* \\^Delivered-To:\\.\\*(.*)$",$cond[$i],$t)) {
      $conds[]=array( 6, $t[1] );
    }
  }
    
  // Action :
  $actionparam=false;
  switch ($rule["type"]) {
  case 1: 
    $actionparam=$rule["rule"][count($rule["rule"])-2]; /* Folder */
    break;
  case 4:
    $actionparam = substr($rule["rule"][count($rule["rule"])-2],0,15); /* Recipient */
    break;
  }
  return array($conds,$actionparam);
}








/* Help for humans ... */

$acriteria=array(
		 0 => "Le sujet du message contient ...",
		 1 => "L'exp�diteur du message est contient ...", 
		 2 => "L'un des destinataires du message contient ...", 
		 3 => "L'en-tete 'List-Post' du message est ...", 
		 4 => "L'en-tete 'List-Id' du message est ...", 
		 5 => "SpamAssassin consid�re qu'il s'agit d'un Spam", 
		 6 => "L'en-tete 'Delivered-To' du message contient ...", 
		 );
$aactions=array(
		1 => "Move the message to this folder",
		2 => "Filter the message through SpamAssassin",
		3 => "Discard the message (for good !)",
		4 => "Forward the mail to",
		5 => "Auto-reply",
		);


/* ----------------------------------------------------------------- */
// CONFIGURATION : 
$ROOT="/var/alternc/mail";
$SIEVEROOT="/var/lib/dovecot/sieve";
// GO !
procmail2sieve();

