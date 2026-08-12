<?php
session_start();

unset($_SESSION["OWNERID"]);
unset($_SESSION["OWNERSTAFFID"]);
unset($_SESSION["OWNERNAME"]);
unset($_SESSION["OWNEREQUITYTYPE"]);
unset($_SESSION["OWNERCONTRACTDURATION"]);

header("Location: owner-login.html");
exit();
?>