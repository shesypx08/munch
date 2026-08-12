document.addEventListener("DOMContentLoaded", function () {
    loadMenuItems();
});

function categoryToId(category) {
    const map = {
        "Nasi": "nasi",
        "Main Dishes": "main-dishes",
        "Vegetables": "vegetables",
        "Side Dishes": "side-dishes",
        "Drinks": "drinks",
        "Combo Sets": "combo-sets"
    };

    return map[category] || "";
}

async function loadMenuItems() {
    try {
        const response = await fetch("getMenu.php");
        const menuItems = await response.json();

        // Clear existing static cards first
        document.querySelectorAll(".menu-grid").forEach(grid => {
            grid.innerHTML = "";
        });

        menuItems.forEach(item => {
            const categoryId = categoryToId(item.MENUCATEGORY);
            const categoryBlock = document.querySelector(`#${categoryId} .menu-grid`);

            if (!categoryBlock) return;

            const card = document.createElement("div");
            card.className = "menu-card";
            card.setAttribute("data-aos", "fade-up");

            card.innerHTML = `
                <img src="${item.MENUIMAGE}" class="menu-img" alt="${item.MENUNAME}">
                <div class="menu-card-body">
                    <div class="menu-card-top">
                        <h3>${item.MENUNAME}</h3>
                        <span>RM${parseFloat(item.MENUPRICE).toFixed(2)}</span>
                    </div>
                    <p>${item.MENUDESCRIPTION}</p>
                </div>
            `;

            categoryBlock.appendChild(card);
        });

        if (typeof AOS !== "undefined") {
            AOS.refresh();
        }

    } catch (error) {
        console.error("Error loading menu items:", error);
    }
}