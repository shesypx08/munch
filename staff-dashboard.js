// =============================
// MUNCH STAFF DASHBOARD SCRIPT
// Shows all active orders.
// Completed/served/cancelled orders are hidden by displayStaffDashboard.php.
// =============================

(function () {
    let allOrders = [];

    function formatRM(value) {
        const amount = Number(value || 0);
        return "RM" + amount.toFixed(2);
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    }

    function normalOrderStatus(status) {
        status = String(status || "").toLowerCase().trim();

        if (status.includes("completed") || status.includes("served")) return "Completed";
        if (status.includes("ready")) return "Ready";
        if (status.includes("preparing")) return "Preparing";

        // Orders that come from payment as "Paid" still start at Pending in staff workflow.
        return "Pending";
    }

    function statusClass(status) {
        const normal = normalOrderStatus(status).toLowerCase();

        if (normal === "pending") return "pending";
        if (normal === "preparing") return "preparing";
        if (normal === "ready" || normal === "completed") return "ready";

        return "pending";
    }

    function nextStatus(status) {
        const normal = normalOrderStatus(status);

        if (normal === "Pending") return "Preparing";
        if (normal === "Preparing") return "Ready";
        if (normal === "Ready") return "Completed";

        return "";
    }

    function nextButtonLabel(status) {
        const next = nextStatus(status);

        // Button shows the NEXT status, not the current status.
        return next || "Completed";
    }

    function renderStats(stats) {
        setText("pending-count", stats.pending || 0);
        setText("preparing-count", stats.preparing || 0);
        setText("ready-served-count", Number(stats.ready || 0) + Number(stats.completed || 0));
        setText("today-sales", formatRM(stats.todaySales || 0));

        setText("kitchen-pending-count", stats.pending || 0);
        setText("kitchen-preparing-count", stats.preparing || 0);
        setText("kitchen-ready-count", stats.ready || 0);
        setText("kitchen-served-count", stats.completed || 0);
    }

    function renderOrders(orders) {
        const grid = document.getElementById("staff-order-grid");

        if (!grid) return;

        if (!orders || orders.length === 0) {
            grid.innerHTML = `
                <div class="menu-empty-message">
                    <h2>No active orders found.</h2>
                    <p>Completed and cancelled orders are hidden from this board.</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = "";

        orders.forEach(function (order) {
            const card = document.createElement("article");
            card.className = "staff-order-card";
            card.dataset.searchText = [
                order.ORDERID,
                order.CUSTNAME,
                order.PHONENO,
                order.ORDERTYPE,
                order.ORDERSTATUS,
                order.ITEMS,
                order.PAYMENTMETHOD
            ].join(" ").toLowerCase();

            const currentStatus = normalOrderStatus(order.ORDERSTATUS);
            const next = nextStatus(order.ORDERSTATUS);
            const label = nextButtonLabel(order.ORDERSTATUS);
            const canUpdate = next !== "";

            card.innerHTML = `
                <div class="order-card-top">
                    <div>
                        <h3>Order #${order.ORDERID}</h3>
                        <p>${order.CUSTNAME || "Unknown Customer"} • ${order.ORDERTYPE || "-"}</p>
                    </div>
                    <span class="status-badge ${statusClass(order.ORDERSTATUS)}">${currentStatus}</span>
                </div>

                <ul>
                    ${(order.ITEMS || "No menu item recorded").split(", ").map(function (item) {
                        return `<li>${item}</li>`;
                    }).join("")}
                </ul>

                <p><strong>Phone:</strong> ${order.PHONENO || "-"}</p>
                <p><strong>Payment:</strong> ${order.PAYMENTMETHOD || "-"}</p>
                <p><strong>Total:</strong> ${formatRM(order.TOTALAMOUNT || 0)}</p>

                <div class="order-action-row">
                    <button class="btn update-order-btn" type="button" data-order-id="${order.ORDERID}" data-current-status="${currentStatus}" data-next-status="${next}" ${canUpdate ? "" : "disabled"} title="Update status to ${next || currentStatus}">
                        ${label}
                    </button>
                    <button class="btn transparent view-order-btn" type="button" data-order-id="${order.ORDERID}">
                        View Details
                    </button>
                </div>
            `;

            grid.appendChild(card);
        });

        setupOrderButtons();
    }

    function renderPayments(payments) {
        const tbody = document.getElementById("staff-payment-table");

        if (!tbody) return;

        if (!payments || payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5">No payment records found.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = "";

        payments.forEach(function (payment) {
            const tr = document.createElement("tr");

            tr.innerHTML = `
                <td>#${payment.ORDERID}</td>
                <td>${payment.CUSTNAME || "Unknown Customer"}</td>
                <td>${payment.SALESPAYMETHOD || "-"}</td>
                <td>${formatRM(payment.SALESTOTAL || 0)}</td>
                <td><span class="status-badge ready">Paid</span></td>
            `;

            tbody.appendChild(tr);
        });
    }

    function setupOrderButtons() {
        document.querySelectorAll(".update-order-btn").forEach(function (button) {
            if (button.dataset.ready === "true") return;
            button.dataset.ready = "true";

            button.addEventListener("click", function () {
                const orderID = button.dataset.orderId;
                const next = button.dataset.nextStatus;

                if (!orderID || !next) return;

                updateOrderStatus(orderID, next);
            });
        });

        document.querySelectorAll(".view-order-btn").forEach(function (button) {
            if (button.dataset.ready === "true") return;
            button.dataset.ready = "true";

            button.addEventListener("click", function () {
                const orderID = button.dataset.orderId;

                const order = allOrders.find(function (item) {
                    return item.ORDERID === orderID;
                });

                if (!order) return;

                alert(
                    "Order ID: " + order.ORDERID +
                    "\nCustomer: " + (order.CUSTNAME || "-") +
                    "\nPhone: " + (order.PHONENO || "-") +
                    "\nType: " + (order.ORDERTYPE || "-") +
                    "\nStatus: " + (order.ORDERSTATUS || "-") +
                    "\nItems: " + (order.ITEMS || "-") +
                    "\nPayment: " + (order.PAYMENTMETHOD || "-") +
                    "\nTotal: " + formatRM(order.TOTALAMOUNT || 0)
                );
            });
        });
    }

    function updateOrderStatus(orderID, status) {
        const formData = new FormData();
        formData.append("ORDERID", orderID);
        formData.append("STATUS", status);

        fetch("updateStaffOrderStatus.php", {
            method: "POST",
            body: formData,
            cache: "no-store"
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || "Could not update order status.");
                    return;
                }

                alert("Order status updated to " + status + ".");

                // If the order is completed, remove it from the Active Orders board immediately.
                if (String(status).toLowerCase() === "completed") {
                    allOrders = allOrders.filter(function (order) {
                        return String(order.ORDERID) !== String(orderID);
                    });
                    renderOrders(allOrders);
                }

                // Reload the dashboard so counts and payment table stay updated.
                loadDashboard();
            })
            .catch(function (error) {
                console.error("Update status error:", error);
                alert("Could not update order status. Please check updateStaffOrderStatus.php.");
            });
    }

    function loadDashboard() {
        fetch("displayStaffDashboard.php", {
            method: "GET",
            cache: "no-store"
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || "Please login as staff first.");
                    window.location.href = "staff-login.html";
                    return;
                }

                setText("staff-name-display", data.staff.STAFFNAME || "Staff Mode");
                setText("staff-role-display", "Logged in as " + (data.staff.STAFFROLE || "restaurant staff"));

                renderStats(data.stats || {});
                allOrders = data.orders || [];
                renderOrders(allOrders);
                renderPayments(data.payments || []);
            })
            .catch(function (error) {
                console.error("Staff dashboard loading error:", error);
                alert("Could not load staff dashboard. Please check displayStaffDashboard.php.");
            });
    }

    function setupSearch() {
        const input = document.getElementById("staff-search-box");
        const button = document.getElementById("staff-search-btn");

        if (!input) return;

        function applySearch() {
            const query = input.value.toLowerCase().trim();

            document.querySelectorAll(".staff-order-card").forEach(function (card) {
                const text = card.dataset.searchText || card.textContent.toLowerCase();
                card.style.display = query === "" || text.includes(query) ? "" : "none";
            });
        }

        input.addEventListener("input", applySearch);

        if (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                applySearch();
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadDashboard();
        setupSearch();

        if (typeof AOS !== "undefined") {
            AOS.init({ duration: 800, once: true });
        }
    });
})();
