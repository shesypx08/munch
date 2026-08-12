//untuk load menu baru automatically without manually masukkan dat html 

<?php

include 'dbconnect.php';

$query = "SELECT * FROM MENU";
$result = mysqli_query($conn, $query);

while($row = mysqli_fetch_assoc($result))
{
?>

<div class="restaurant">

    <div class="restaurant-body">

        <h3 class="restaurant-name">
            <?php echo $row['MENUNAME']; ?>
        </h3>

        <p class="place">
            RM <?php echo $row['MENUPRICE']; ?>
        </p>

    </div>

</div>

<?php
}
?>