// MUNCH INDEX PRODUCT PREVIEW
// Loads product preview cards but keeps the original index hero/food images unchanged.

(function () {
    function formatRM(value) {
        return "RM" + Number(value || 0).toFixed(2);
    }

    function postForm(url, data) {
        const formData = new FormData();
        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        return fetch(url, {
            method: "POST",
            body: formData,
            cache: "no-store"
        }).then(function (response) {
            return response.json();
        });
    }

    function addToCart(menuID) {
        postForm("addToCart.php", {
            MENUID: menuID,
            QUANTITY: 1
        })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || "Could not add item to cart.");
                    return;
                }

                alert("Product added to cart!");
                window.location.href = "order.html";
            })
            .catch(function (error) {
                console.error("Index add to cart error:", error);
                alert("Could not add product to cart. Please check addToCart.php.");
            });
    }

    function keepFiftyProductsLabel() {
        const productCount = document.getElementById("index-product-count");
        if (productCount) {
            productCount.textContent = "50+ Products";
        }

        document.querySelectorAll(".service-name").forEach(function (title) {
            if (title.textContent.trim().includes("20+") || title.textContent.trim().includes("Munch Products")) {
                title.textContent = "50+ Products";
            }
        });
    }

    function renderProductCards(items) {
        const container = document.getElementById("index-product-preview");
        if (!container) return;

        if (!items || items.length === 0) {
            container.innerHTML = `
                <div class="menu-empty-message">
                    <h2>No products found.</h2>
                    <p>Please check your menu items.</p>
                    <a href="menu.php" class="btn">Open Menu</a>
                </div>
            `;
            return;
        }

        container.innerHTML = "";

        items.slice(0, 6).forEach(function (item, index) {
            const card = document.createElement("div");
            card.className = "restaurant index-product-card";
            card.dataset.menuId = item.MENUID;

            const badge = index === 0 ? '<p class="discount">Popular</p>' : "";

            card.innerHTML = `
                ${badge}
                <div class="restaurant-thumbnail">
                    <img src="${item.IMAGE || 'img/restaurant-1.png'}" class="restaurant-img" alt="${item.MENUNAME}">
                </div>

                <div class="restaurant-body">
                    <h3 class="restaurant-name">${item.MENUNAME}</h3>
                    <p class="menu">${item.MENUDESC || "Freshly prepared Munch menu item."}</p>
                    <p class="place">${item.MENUCATEGORY || "Munch Menu"} • ${formatRM(item.MENUPRICE)}</p>
                    <span class="rating">5.0 <i class="fa-solid fa-star"></i></span>

                    <div class="index-product-actions">
                        <button class="btn index-add-cart-btn" type="button" data-menu-id="${item.MENUID}">
                            add to cart
                        </button>
                        <a href="menu.php" class="btn transparent index-view-menu-btn">view menu</a>
                    </div>
                </div>
            `;

            container.appendChild(card);
        });

        document.querySelectorAll(".index-add-cart-btn").forEach(function (button) {
            button.addEventListener("click", function () {
                addToCart(button.dataset.menuId);
            });
        });
    }

    function loadIndexProducts() {
        keepFiftyProductsLabel();

        const container = document.getElementById("index-product-preview");
        if (!container) return;

        fetch("displayMenu.php", {
            method: "GET",
            cache: "no-store"
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    container.innerHTML = `
                        <div class="menu-empty-message">
                            <h2>Could not load products.</h2>
                            <p>${data.message || "Please check displayMenu.php."}</p>
                            <a href="menu.php" class="btn">Open Menu</a>
                        </div>
                    `;
                    return;
                }

                renderProductCards(data.items || []);
                keepFiftyProductsLabel();

                if (typeof AOS !== "undefined") {
                    AOS.refresh();
                }
            })
            .catch(function (error) {
                console.error("Index product preview error:", error);
                container.innerHTML = `
                    <div class="menu-empty-message">
                        <h2>Product preview error.</h2>
                        <p>Please make sure displayMenu.php is inside your project folder.</p>
                        <a href="menu.php" class="btn">Open Menu</a>
                    </div>
                `;
            });
    }

    document.addEventListener("DOMContentLoaded", loadIndexProducts);
})();
