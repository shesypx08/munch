<?php
session_start();
include "dbconnect.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function categoryId($category) {
    $category = strtolower(trim((string)$category));
    $category = preg_replace("/[^a-z0-9]+/", "-", $category);
    return trim($category, "-");
}

function normalizeCategory($category) {
    $category = strtolower(trim((string)$category));

    if ($category === "nasi") return "Nasi";
    if ($category === "main dishes" || $category === "main dish" || $category === "main") return "Main Dishes";
    if ($category === "vegetables" || $category === "vegetable" || $category === "veggies") return "Vegetables";
    if ($category === "side dishes" || $category === "side dish" || $category === "side") return "Side Dishes";
    if ($category === "drinks" || $category === "drink") return "Drinks";
    if ($category === "combo sets" || $category === "combo set" || $category === "combo") return "Combo Sets";

    return "";
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

$allowedOrder = ["Nasi", "Main Dishes", "Vegetables", "Side Dishes", "Drinks", "Combo Sets"];
$categories = [];

foreach ($allowedOrder as $category) {
    $categories[$category] = [];
}

$sql = "SELECT MENUID, MENUNAME, MENUCATEGORY, MENUPRICE, MENUDESC
        FROM menu
        ORDER BY CASE
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('nasi') THEN 1
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('main dishes', 'main dish', 'main', 'western') THEN 2
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('vegetables', 'vegetable', 'veggies') THEN 3
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('side dishes', 'side dish', 'side', 'addons') THEN 4
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('drinks', 'drink') THEN 5
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('combo sets', 'combo set', 'combo') THEN 6
            ELSE 99
        END,
        CAST(SUBSTRING(MENUID, 2) AS UNSIGNED) ASC,
        MENUID ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categoryName = normalizeCategory($row["MENUCATEGORY"]);

        if ($categoryName === "") {
            continue;
        }

        $categories[$categoryName][] = $row;
    }
}

$totalItems = 0;
foreach ($categories as $categoryItems) {
    $totalItems += count($categoryItems);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | Munch</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="search-extra.css">
    <link rel="stylesheet" href="menu-details.css">

    <style>
        .menu-card-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .menu-card-actions .btn {
            padding: .75rem 1rem;
            font-size: .85rem;
            text-decoration: none;
        }

        .menu-empty-message {
            text-align: center;
            padding: 3rem;
            background: var(--accent-color);
            border-radius: .8rem;
            line-height: 2rem;
        }

        .menu-category-block {
            scroll-margin-top: 8rem;
        }
    </style>
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

        <main id="menu-hero-section">
            <div class="menu-hero-content" data-aos="fade-right">
                <span class="section-highlight">Our Menu</span>
                <h1 class="menu-hero-heading">Explore Our 6 Menu Categories</h1>
                <p class="menu-hero-line">
                    Discover nasi, main dishes, vegetables, side dishes, drinks, and combo sets from our latest menu.
                </p>
            </div>

            <div class="menu-hero-card" data-aos="zoom-in">
                <i class="fa-solid fa-utensils"></i>
                <h2><?= e($totalItems); ?> Items</h2>
                <p>Each category is ready for browsing and ordering.</p>
            </div>
        </main>
    </header>

    <section id="menu-section">
        <div class="menu-title">
            <h1 class="section-title">Munch Menu</h1>
            <p class="section-info">
                Browse all 6 menu categories, including Combo Sets.
            </p>
        </div>

        <?php if ($totalItems === 0): ?>
            <div class="menu-empty-message">
                <h2>No menu items found.</h2>
                <p>Our menu is being updated. Please check again shortly.</p>
            </div>
        <?php else: ?>

            <div class="menu-category-container">
                <?php foreach ($allowedOrder as $categoryName): ?>
                    <a href="#<?= e(categoryId($categoryName)); ?>" class="menu-category-btn">
                        <?= e($categoryName); ?>
                        <small>(<?= count($categories[$categoryName]); ?>)</small>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php foreach ($allowedOrder as $categoryName): ?>
                <div class="menu-category-block" id="<?= e(categoryId($categoryName)); ?>">
                    <h2 class="menu-category-title"><?= e($categoryName); ?></h2>

                    <?php if (count($categories[$categoryName]) === 0): ?>
                        <div class="menu-empty-message">
                            <p>No items found under <?= e($categoryName); ?> yet.</p>
                            <p>This category is being updated. Please check again shortly.</p>
                        </div>
                    <?php else: ?>
                        <div class="menu-grid">
                            <?php foreach ($categories[$categoryName] as $index => $item): ?>
                                <?php
                                    $menuID = $item["MENUID"];
                                    $image = menuImagePath($menuID);
                                    $delay = $index > 0 ? 'data-aos-delay="' . e((string)(($index % 4) * 100)) . '"' : "";
                                ?>

                                <div class="menu-card" data-aos="fade-up" <?= $delay; ?>>
                                    <img src="<?= e($image); ?>" class="menu-img" alt="<?= e($item["MENUNAME"]); ?>">

                                    <div class="menu-card-body">
                                        <div class="menu-card-top">
                                            <h3><?= e($item["MENUNAME"]); ?></h3>
                                            <span>RM<?= number_format((float)$item["MENUPRICE"], 2); ?></span>
                                        </div>

                                        <p><?= e($item["MENUDESC"]); ?></p>

                                        <div class="menu-card-actions">
                                            <a href="menuDetails.php?id=<?= urlencode($menuID); ?>" class="btn transparent">
                                                view details
                                            </a>

                                            <button type="button" class="btn add-cart-btn" data-menu-id="<?= e($menuID); ?>">
                                                add to cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer id="footer">
        <div class="company-info">
            <img src="img/footer-logo.png" class="logo" alt="Munch Logo">

            <div class="social-links">
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
                <a href="#"><i class="fa-brands fa-twitter"></i></a>
            </div>
        </div>

        <div class="useful-links footer-links-container">
            <h5 class="footer-links-title">useful links</h5>
            <a href="profile.html" class="footer-links protected-link" data-protected="true" data-target="profile.html">profile</a>
            <a href="menu.php" class="footer-links">menu</a>
            <a href="customerOrder.html" class="footer-links protected-link" data-protected="true" data-target="customerOrder.html">your orders</a>
            <a href="Reservation.html" class="footer-links require-login-reservation">reservation</a>
        </div>

        <div class="information footer-links-container">
            <h5 class="footer-links-title">information</h5>
            <a href="index.html#about-section" class="footer-links">about us</a>
            <a href="#" class="footer-links">privacy policy</a>
            <a href="#" class="footer-links">terms and conditions</a>
        </div>

        <div class="contact footer-links-container">
            <h5 class="footer-links-title">contact us</h5>
            <p class="footer-text">Our Munch restaurant is located on Jalan 321, Shah Alam</p>
            <p class="footer-text">Phone - 03-24552776</p>
            <p class="footer-text">Email - munchService@email.com</p>
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
