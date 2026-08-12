
<!DOCTYPE html>
<html>
<head>
    <title>Munch Customer Homepage</title>
    <link rel="stylesheet" href="customerStyle.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
</head>

<body>
<button class="menu-btn" onclick="toggleSidebar()">
    Menu
</button>

<div id="sidebar" class="sidebar">

    <h3>Category</h3>

    <a href="#">All Menu</a>
    <a href="#">Food</a>
    <a href="#">Drinks</a>
    <a href="#">Dessert</a>

</div>

<div id="main-content">
<div class="navbar">
    <h1>Munch</h1>

    <div class="nav-right">
        <a href="lookatCart.php">Cart</a>
        <a href="profile.html">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="search-box">
    <form method="GET">
        <input type="text" name="search" placeholder="Search Menu">
        <button type="submit">Search</button>
    </form>
</div>

<div class="menu-container">

<?php

session_start();
include 'dbconnect.php';

$query = "SELECT * FROM MENU";

if(isset($_GET['search']))
{
    $search = $_GET['search'];
    $query = "SELECT * FROM MENU WHERE MENUNAME LIKE '%$search%' ";
}

$result = mysqli_query($conn,$query);

while($row = mysqli_fetch_assoc($result))
{
?>

    <div class="menu-card">

        <a href="menuDetails.php?id=<?php echo $row['MENUID']; ?>">

            <img src="img/<?php echo $row['MENUID']; ?>.jpg">

        </a>

        <h3>
            <?php echo $row['MENUNAME']; ?>
        </h3>

        <p>
            RM <?php echo $row['MENUPRICE']; ?>
        </p>

        <p>
            <?php echo $row['MENUCATEGORY']; ?>
        </p>

        <form method="POST" action="addToCart.php">

            <input type="hidden"
            name="MENUID"
            value="<?php echo $row['MENUID']; ?>">

            <button type="submit" name="addCart">
                Add To Cart
            </button>

        </form>

    </div>

<?php
}
?>

</div>

<script>

function toggleSidebar()
{
    document.getElementById("sidebar").classList.toggle("active");
}

</script>
</body>
</html>
