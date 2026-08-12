<?php
session_start();
unset($_SESSION["STAFFID"]);
unset($_SESSION["STAFFNAME"]);
unset($_SESSION["STAFFROLE"]);
header("Location: staff-login.html");
exit();
?>