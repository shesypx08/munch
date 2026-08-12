<?php
session_start();
include "dbconnect.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function menuImagePath($menuID) {
    $menuID = trim((string)$menuID);
    $extensions = ["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"];

    foreach ($extensions as $ext) {
        $imagePath = "img/" . $menuID . "." . $ext;

        if (file_exists(__DIR__ . "/" . $imagePath)) {
            return $imagePath;
        }
    }

    return "img/restaurant-1.png";
}

$menuID = isset($_GET["id"]) ? trim($_GET["id"]) : "";

if ($menuID === "") {
    header("Location: menu.php");
    exit();
}

$sql = "SELECT MENUID, MENUNAME, MENUCATEGORY, MENUPRICE, MENUDESC
        FROM menu
        WHERE MENUID = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Menu details are not available right now. Please try again later.");
}

mysqli_stmt_bind_param($stmt, "s", $menuID);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

if (!$item) {
    echo "<script>alert('Menu item not found.'); window.location.href='menu.php';</script>";
    exit();
}

$image = menuImagePath($item["MENUID"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($item["MENUNAME"]); ?> | Munch Menu Details</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="search-extra.css">
    <link rel="stylesheet" href="menu-details.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <a href="index.html"><img src="img/footer-logo.png" class="logo" alt="Munch Logo"></a>

            <ul class="links-container">
                <li class="link-items"><a href="index.html" class="links">Home</a></li>
                <li class="link-items"><a href="profile.html" class="links protected-link" data-protected="true" data-target="profile.html">profile</a></li>
                <li class="link-items"><a href="menu.php" class="links">menu</a></li>
                <li class="link-items"><a href="customerOrder.html" class="links protected-link" data-protected="true" data-target="customerOrder.html">order</a></li>
                <li class="link-items"><a href="Reservation.html" class="links require-login-reservation">Reservation</a></li>
            </ul>

            <div class="nav-extras">
                <div class="search">
                    <input type="text" class="search-box" placeholder="Search Menu.....">
                    <button class="search-btn" type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>

                <a href="order.html" class="cart protected-link" data-protected="true" data-target="order.html">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count-badge" id="cart-count-badge">0</span>
                </a>

                <div class="nav-toggler">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>

        <main id="menu-detail-hero-section">
            <div class="menu-detail-image-box" data-aos="zoom-in">
                <img src="<?= e($image); ?>" alt="<?= e($item["MENUNAME"]); ?>">
            </div>

            <div class="menu-detail-content" data-aos="fade-left">
                <span class="section-highlight"><?= e($item["MENUCATEGORY"]); ?></span>
                <h1><?= e($item["MENUNAME"]); ?></h1>
                <p><?= e($item["MENUDESC"]); ?></p>

                <div class="menu-detail-price">
                    RM<?= number_format((float)$item["MENUPRICE"], 2); ?>
                </div>

                <div class="menu-detail-meta">
                    <p><i class="fa-solid fa-tag"></i> Menu ID: <?= e($item["MENUID"]); ?></p>
                    <p><i class="fa-solid fa-bowl-food"></i> Category: <?= e($item["MENUCATEGORY"]); ?></p>
                    <p><i class="fa-solid fa-circle-check"></i> Available on the latest Munch menu</p>
                </div>

                <div class="menu-detail-actions">
                    <a href="menu.php" class="btn transparent">
                        <i class="fa-solid fa-arrow-left"></i> back to menu
                    </a>

                    <button type="button" class="btn add-cart-btn" data-menu-id="<?= e($item["MENUID"]); ?>">
                        <i class="fa-solid fa-cart-plus"></i> add to cart
                    </button>
                </div>
            </div>
        </main>
    </header>

    <section id="menu-detail-info-section">
        <div class="menu-detail-info-card" data-aos="fade-up">
            <i class="fa-solid fa-circle-info"></i>
            <h2>About this item</h2>
            <p>This item is part of the 6-category Munch menu catalogue.</p>
        </div>

        <div class="menu-detail-info-card" data-aos="fade-up" data-aos-delay="100">
            <i class="fa-solid fa-list"></i>
            <h2>Menu categories</h2>
            <p>Nasi, Main Dishes, Vegetables, Side Dishes, Drinks, and Combo Sets.</p>
        </div>

        <div class="menu-detail-info-card" data-aos="fade-up" data-aos-delay="200">
            <i class="fa-solid fa-cart-shopping"></i>
            <h2>Cart flow</h2>
            <p>Add this item to cart and continue payment on the order page.</p>
        </div>
    </section>

    <footer id="footer">
        <div class="company-info">
            <img src="img/footer-logo.png" class="logo" alt="Munch Logo">
        </div>
        <p class="copyright">© 2026 Munch Food Ordering System. All Rights Reserved.</p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="app.js"></script>
    <script src="auth.js"></script>
    <script src="cart.js"></script>
    <script src="search.js"></script>
    <script src="munch-footer-popups.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
