<?php
/*
 $Id: lst_delown.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
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
 Original Author of file: Sylvain Louis.
 Purpose of file: Delete one or several owners of the mailing-list
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if(!$r=$sympa->get_ml($id)) {
  $error=$err->errstr();
} 


$a=$sympa->del_owner($del,$id);
if (!$a) {
	$error=$err->errstr();
	include("lst_owner.php");
	exit();
} else {
	$error=_("The owner(s) has been successfully deleted"); 
	include("lst_owner.php");
	exit();
}

include("head.php");
?>
</head>
<body>
<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>
<?php 
	if ($error) {
		echo "<br><font color=red>$error</font><br>";
		echo $count;
	}
?>
</body>
</html>