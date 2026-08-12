<?php

session_start();

if(!isset($_SESSION['USERNAME']))
{
    header("Location: login.html");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="munch-clean-ui.css">
</head>
<body>

<h1>Welcome <?php echo $_SESSION['USERNAME']; ?> </h1>

<a href="logout.php">
    Logout
</a>

</body>
</html>