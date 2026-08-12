// =============================
// MUNCH OWNER DASHBOARD
// Owner dashboard actions.
// Sections used:
// staff, owner, menu, orders, ordermenu, sales, reservation.
// =============================

(function () {
    function money(value) {
        return "RM" + Number(value || 0).toFixed(2);
    }

    function esc(value) {
        return String(value ?? "").replace(/[&<>"']/g, function (m) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            }[m];
        });
    }

    function badge(value) {
        const text = String(value || "-");
        const lower = text.toLowerCase();
        let cls = "ready";

        if (lower.includes("pending") || lower.includes("inactive") || lower.includes("low") || lower.includes("cancel")) cls = "pending";
        if (lower.includes("preparing") || lower.includes("limited") || lower.includes("owner")) cls = "preparing";

        return '<span class="status-badge ' + cls + '">' + esc(text) + '</span>';
    }

    function getOwnerData(section) {
        return fetch("ownerData.php?section=" + encodeURIComponent(section), { cache: "no-store" })
            .then(function (response) { return response.json(); });
    }

    function postOwnerAction(action, data) {
        const formData = new FormData();
        formData.append("action", action);

        Object.keys(data || {}).forEach(function (key) {
            formData.append(key, data[key]);
        });

        return fetch("ownerActions.php", {
            method: "POST",
            body: formData,
            cache: "no-store"
        }).then(function (response) {
            return response.json();
        });
    }

    function ensureOwnerOK(data) {
        if (!data.success) {
            alert(data.message || "Please login as owner first.");
            window.location.href = "owner-login.html";
            return false;
        }

        return true;
    }

    function setStatCards(items) {
        const cards = document.querySelectorAll("#staff-stats-section .staff-stat-card");

        items.forEach(function (item, index) {
            const card = cards[index];
            if (!card) return;

            const value = card.querySelector("h3");
            const label = card.querySelector("p");

            if (value) value.textContent = item.value;
            if (label) label.textContent = item.label;
        });
    }

    function showMessage(id, text) {
        const box = document.getElementById(id);

        if (!box) {
            if (window.MunchPopup) window.MunchPopup(text, "Action Complete");
            else alert(text);
            return;
        }

        box.textContent = text;
        box.classList.add("show");

        if (window.MunchPopup) window.MunchPopup(text, "Action Complete");

        setTimeout(function () {
            box.classList.remove("show");
        }, 4500);
    }


    function ensureBookingActionHeaders() {
        ["#reservation-tracker table", "#catering-tracker table"].forEach(function (selector) {
            const table = document.querySelector(selector);
            if (!table) return;

            const headRow = table.querySelector("thead tr");
            if (!headRow) return;

            const lastHeader = headRow.lastElementChild;
            if (!lastHeader || lastHeader.textContent.trim().toLowerCase() !== "action") {
                const th = document.createElement("th");
                th.textContent = "Action";
                headRow.appendChild(th);
            }
        });
    }

    function bookingActionCell(row) {
        const status = String(row.STATUS || "").toLowerCase();
        const disabled = status.includes("refunded") || status.includes("cancelled") || status.includes("canceled");
        const label = disabled ? "Closed" : "Update Status";
        const title = disabled ? "This booking is already closed" : "Accept or decline this booking";

        return `
            <td>
                <button type="button"
                    class="btn transparent booking-status-btn"
                    data-booking-status="true"
                    data-reserve-id="${esc(row.RESERVEID)}"
                    data-current-status="${esc(row.STATUS || "")}" 
                    title="${esc(title)}"
                    ${disabled ? "disabled" : ""}
                    style="padding:.75rem 1rem; font-size:.9rem; white-space:nowrap; ${disabled ? "opacity:.55; cursor:not-allowed;" : ""}">
                    ${esc(label)}
                </button>
            </td>
        `;
    }

    function closeBookingPopup() {
        const oldPopup = document.querySelector(".booking-status-popup-overlay");
        if (oldPopup) oldPopup.remove();
    }

    function openBookingStatusPopup(reserveId, currentStatus) {
        closeBookingPopup();

        const overlay = document.createElement("div");
        overlay.className = "booking-status-popup-overlay";
        overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;z-index:9999;padding:1rem;";
        overlay.innerHTML = `
            <div style="width:min(42rem,94vw);background:#fff;border-radius:1.4rem;padding:2rem;box-shadow:0 1.5rem 3rem rgba(0,0,0,.18);text-align:center;border:1px solid rgba(0,128,128,.18);">
                <h2 style="font-size:2rem;color:#007c7c;margin-bottom:.65rem;">Update Booking Status</h2>
                <p style="font-size:1.05rem;color:#4b5d5a;margin-bottom:1.4rem;line-height:1.6;">
                    Reservation ID: <strong>${esc(reserveId)}</strong><br>
                    Current Status: <strong>${esc(currentStatus || "Pending")}</strong>
                </p>
                <p style="font-size:.98rem;color:#5d6e6b;margin-bottom:1.5rem;">
                    Choose <strong>Accept</strong> to set the booking as <strong>Confirmed</strong>, or choose <strong>Declined</strong> to set it as <strong>Refunded</strong>.
                </p>
                <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                    <button type="button" class="btn" data-booking-decision="accepted" style="min-width:9rem;">Accept</button>
                    <button type="button" class="btn transparent" data-booking-decision="declined" style="min-width:9rem;border-color:#d9534f;color:#d9534f;">Declined</button>
                    <button type="button" class="btn transparent" data-booking-cancel="true" style="min-width:9rem;">Cancel</button>
                </div>
            </div>
        `;

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay || event.target.dataset.bookingCancel === "true") {
                closeBookingPopup();
                return;
            }

            const decision = event.target.dataset.bookingDecision;
            if (!decision) return;

            event.target.disabled = true;
            event.target.textContent = "Updating...";

            postOwnerAction("updateBookingStatus", {
                RESERVEID: reserveId,
                DECISION: decision
            }).then(function (result) {
                if (!result.success) {
                    if (window.MunchPopup) window.MunchPopup(result.message || "Booking status update failed.", "Update Failed");
                    else alert(result.message || "Booking status update failed.");
                    return;
                }

                closeBookingPopup();
                if (window.MunchPopup) window.MunchPopup(result.message || "Booking status updated successfully.", "Booking Updated");
                else alert(result.message || "Booking status updated successfully.");
                renderBookings();
            }).catch(function () {
                if (window.MunchPopup) window.MunchPopup("Booking status update failed. Please try again.", "Update Failed");
                else alert("Booking status update failed. Please try again.");
            });
        });

        document.body.appendChild(overlay);
    }

    function setupBookingStatusActions() {
        if (document.body.dataset.bookingStatusReady === "true") return;
        document.body.dataset.bookingStatusReady = "true";

        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-booking-status]");
            if (!button) return;

            const reserveId = button.dataset.reserveId;
            const currentStatus = button.dataset.currentStatus || "Pending";
            if (!reserveId) return;

            openBookingStatusPopup(reserveId, currentStatus);
        });
    }

    function renderProgressPanel(keyword, rows, valueKey, labelKey) {
        const panels = Array.from(document.querySelectorAll(".owner-panel-card"));
        const target = panels.find(function (panel) {
            return panel.textContent.toLowerCase().includes(keyword.toLowerCase());
        });

        if (!target || !rows || rows.length === 0) return;

        const header = target.querySelector(".owner-panel-header");
        const max = Math.max.apply(null, rows.map(function (row) {
            return Number(row[valueKey] || 0);
        }).concat([1]));

        let html = header ? header.outerHTML : "";

        html += rows.slice(0, 5).map(function (row, index) {
            const value = Number(row[valueKey] || 0);
            const percent = Math.round(value / max * 100);
            const label = row[labelKey] || row.MENUNAME || row.method || row.category || "Item";

            return `
                <div class="owner-progress-row">
                    <div class="owner-progress-label">
                        <span>#${index + 1} ${esc(label)}</span>
                        <strong>${percent}%</strong>
                    </div>
                    <div class="owner-progress-track"><span style="width:${percent}%;"></span></div>
                    <small>${value} record(s)</small>
                </div>
            `;
        }).join("");

        target.innerHTML = html;
    }

    function renderDashboard() {
        getOwnerData("dashboard").then(function (data) {
            if (!ensureOwnerOK(data)) return;

            setStatCards([
                { value: money(data.todaySales), label: "Today’s Sales" },
                { value: data.todayOrders, label: "Orders Today" },
                { value: data.todayReservations, label: "Today’s Reservations" },
                { value: data.cateringRequests, label: "Catering Requests" },
                { value: data.staffCount, label: "Staff Records" },
                { value: data.menuCount, label: "Menu Items" }
            ]);

            const systemHealth = document.querySelector(".owner-summary-card .owner-mini-kpi strong");
            if (systemHealth) systemHealth.textContent = data.ownerName ? data.ownerName : "Live DB";

            const chart = document.querySelector(".owner-bar-chart");
            if (chart && data.salesTrend) {
                const max = Math.max.apply(null, data.salesTrend.map(function (row) {
                    return Number(row.total || 0);
                }).concat([1]));

                chart.innerHTML = data.salesTrend.map(function (row) {
                    const height = Math.max(8, Number(row.total || 0) / max * 100);
                    return '<div style="height:' + height + '%;"><span>' + esc(row.label) + '</span></div>';
                }).join("");
            }

            renderProgressPanel("category", data.categoryDemand, "qty", "category");
        });
    }

    function renderMenu() {
        getOwnerData("menu").then(function (data) {
            if (!ensureOwnerOK(data)) return;

            const mini = document.querySelector(".owner-summary-card .owner-mini-kpi strong");
            if (mini) mini.textContent = data.items.length + " Items";

            const counts = data.categoryCounts || {};

            setStatCards([
                { value: data.items.length, label: "Total Menu" },
                { value: counts.nasi || 0, label: "Nasi" },
                { value: counts.main || 0, label: "Main Dishes" },
                { value: counts.vegetables || 0, label: "Vegetables" },
                { value: counts.side || 0, label: "Side Dishes" },
                { value: (counts.drinks || 0) + (counts.combo || 0), label: "Drinks + Combo Sets" }
            ]);

            const table = document.getElementById("ownerMenuTable") || document.querySelector("#menu-list table");
            const tbody = table ? table.querySelector("tbody") : null;

            if (tbody) {
                tbody.innerHTML = data.items.map(function (item) {
                    return `
                        <tr>
                            <td>${esc(item.MENUID)}</td>
                            <td>
                                <img src="${esc(item.IMAGE || "img/restaurant-1.png")}" class="owner-menu-thumb" alt="${esc(item.MENUNAME)}">
                            </td>
                            <td>${esc(item.MENUNAME)}</td>
                            <td>${esc(item.MENUCATEGORY)}</td>
                            <td>${money(item.MENUPRICE)}</td>
                            <td>${badge("Available")}</td>
                            <td>DB</td>
                            <td>
                                <button class="owner-icon-btn" data-update-menu="${esc(item.MENUID)}" title="Update price">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="owner-icon-btn" data-delete-menu="${esc(item.MENUID)}" title="Delete menu">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join("");
            }

            populateQuickUpdateSelects(data.items || []);
            setupMenuActions();

            if (window.MunchOwnerSearch) window.MunchOwnerSearch.refresh();
        });
    }

    function populateQuickUpdateSelects(items) {
        document.querySelectorAll('[data-owner-form="quick-update"]').forEach(function (form) {
            const select = form.querySelector('select[data-menu-select], select:first-of-type');
            if (!select) return;

            const selectedValue = select.value;
            select.setAttribute('data-menu-select', 'true');
            select.innerHTML = '<option value="">Choose menu item</option>' + items.map(function (item) {
                return '<option value="' + esc(item.MENUID) + '">' + esc(item.MENUID + ' - ' + item.MENUNAME + ' (' + money(item.MENUPRICE) + ')') + '</option>';
            }).join('');

            if (selectedValue) select.value = selectedValue;
        });
    }

    function setupMenuActions() {
        const addForm = document.querySelector('[data-owner-form="menu-add"]');

        if (addForm && addForm.dataset.dbReady !== "true") {
            addForm.dataset.dbReady = "true";

            addForm.addEventListener("submit", function (event) {
                event.preventDefault();

                const name = document.getElementById("menuName")?.value.trim() || "";
                const category = document.getElementById("menuCategory")?.value || "";
                const price = document.getElementById("menuPrice")?.value || "";
                const desc = document.getElementById("menuDesc")?.value || "";
                const imageInput = document.getElementById("menuImage");
                const selectedImage = imageInput && imageInput.files && imageInput.files[0] ? imageInput.files[0] : "";

                if (!name || !category || Number(price) <= 0) {
                    if (window.MunchPopup) window.MunchPopup("Please enter menu name, category, and a valid price before saving.", "Menu Form Error");
                    else alert("Please enter menu name, category, and a valid price before saving.");
                    return;
                }

                const submitBtn = addForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Saving...";
                }

                postOwnerAction("addMenu", {
                    MENUNAME: name,
                    MENUCATEGORY: category,
                    MENUPRICE: price,
                    MENUDESC: desc,
                    MENUIMAGE: selectedImage
                }).then(function (result) {
                    if (!result.success) {
                        if (window.MunchPopup) window.MunchPopup(result.message || "Could not add menu.", "Menu Not Saved");
                        else alert(result.message || "Could not add menu.");
                        return;
                    }

                    showMessage("menuActionMessage", result.message || ("Menu item " + (result.MENUID || "") + " saved successfully."));
                    addForm.reset();

                    const preview = document.getElementById("menuImagePreview");
                    if (preview) preview.innerHTML = "<span>No image selected yet</span>";

                    renderMenu();
                }).catch(function () {
                    if (window.MunchPopup) window.MunchPopup("Could not connect to ownerActions.php. Please check XAMPP MySQL and owner login session.", "Menu Save Error");
                    else alert("Could not connect to ownerActions.php.");
                }).finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Save Menu Item";
                    }
                });
            });
        }

        document.querySelectorAll('[data-owner-form="quick-update"]').forEach(function (form) {
            if (form.dataset.dbReady === "true") return;
            form.dataset.dbReady = "true";

            form.addEventListener("submit", function (event) {
                event.preventDefault();
                const selects = form.querySelectorAll("select");
                const menuID = selects[0] ? selects[0].value : "";
                const priceInput = form.querySelector('input[type="number"]');
                const statusSelect = selects.length > 1 ? selects[1] : null;

                if (!menuID) {
                    if (window.MunchPopup) window.MunchPopup("Please choose a menu item first.", "Update Menu");
                    else alert("Please choose a menu item first.");
                    return;
                }

                if (priceInput) {
                    const price = priceInput.value;
                    if (Number(price) <= 0) {
                        if (window.MunchPopup) window.MunchPopup("Please enter a valid new price.", "Update Price");
                        else alert("Please enter a valid new price.");
                        return;
                    }

                    postOwnerAction("updateMenuPrice", { MENUID: menuID, MENUPRICE: price }).then(function (result) {
                        if (!result.success) {
                            if (window.MunchPopup) window.MunchPopup(result.message || "Could not update price.", "Update Failed");
                            else alert(result.message || "Could not update price.");
                            return;
                        }
                        showMessage("menuActionMessage", "Menu " + menuID + " price updated successfully.");
                        priceInput.value = "";
                        renderMenu();
                    });
                    return;
                }

                if (statusSelect) {
                    showMessage("menuActionMessage", "Menu " + menuID + " status changed to " + statusSelect.value + " for presentation demo.");
                    return;
                }
            });
        });

        document.querySelectorAll("[data-update-menu]").forEach(function (button) {
            if (button.dataset.dbReady === "true") return;
            button.dataset.dbReady = "true";

            button.addEventListener("click", function () {
                const menuID = button.dataset.updateMenu;
                const price = prompt("Enter new price for " + menuID + ":");

                if (!price) return;

                postOwnerAction("updateMenuPrice", {
                    MENUID: menuID,
                    MENUPRICE: price
                }).then(function (result) {
                    if (!result.success) {
                        if (window.MunchPopup) window.MunchPopup(result.message || "Could not update price.", "Update Failed");
                        else alert(result.message || "Could not update price.");
                        return;
                    }

                    showMessage("menuActionMessage", "Menu " + menuID + " price updated successfully.");
                    renderMenu();
                });
            });
        });

        document.querySelectorAll("[data-delete-menu]").forEach(function (button) {
            if (button.dataset.dbReady === "true") return;
            button.dataset.dbReady = "true";

            button.addEventListener("click", function () {
                const menuID = button.dataset.deleteMenu;

                if (!confirm("Delete menu item " + menuID + "?")) return;

                postOwnerAction("deleteMenu", { MENUID: menuID }).then(function (result) {
                    if (!result.success) {
                        if (window.MunchPopup) window.MunchPopup(result.message || "Could not delete menu.", "Delete Failed");
                        else alert(result.message || "Could not delete menu.");
                        return;
                    }

                    showMessage("menuActionMessage", "Menu item " + menuID + " deleted successfully.");
                    renderMenu();
                });
            });
        });
    }

    function renderEmployees() {
        getOwnerData("employees").then(function (data) {
            if (!ensureOwnerOK(data)) return;

            const roleCounts = data.roleCounts || {};

            setStatCards([
                { value: roleCounts.owner || 0, label: "Owners" },
                { value: roleCounts.operational || 0, label: "Operational Staff" },
                { value: data.operationalCount || 0, label: "Operational Subtype" },
                { value: data.ownerCount || 0, label: "Owner Subtype" },
                { value: data.staff.length, label: "Total Staff" },
                { value: data.staffWithSales || 0, label: "Staff With Sales" }
            ]);

            const tbody = document.querySelector("#employeeTable tbody");

            if (tbody) {
                tbody.innerHTML = data.staff.map(function (staff) {
                    const isOwner = staff.EQUITYTYPE && staff.EQUITYTYPE !== "";
                    const subtypeInfo = isOwner ? staff.EQUITYTYPE : (staff.WORKSTATION || "-");
                    const durationOrSkill = isOwner ? staff.CONTRACTDURATION + " year(s)" : (staff.SKILLEVEL || "-");

                    return `
                        <tr>
                            <td>${esc(staff.STAFFID)}</td>
                            <td>${esc(staff.STAFFNAME)}</td>
                            <td>${esc(staff.STAFFROLE)}</td>
                            <td>${esc(staff.STAFFPHONENO || "-")}</td>
                            <td>${esc(subtypeInfo)}</td>
                            <td>${badge(isOwner ? "Owner" : "Operational")}</td>
                            <td>${esc(durationOrSkill)}</td>
                            <td>
                                <button class="owner-icon-btn" data-delete-staff="${esc(staff.STAFFID)}">
                                    <i class="fa-solid fa-user-minus"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join("");
            }

            setupEmployeeActions();

            if (window.MunchOwnerSearch) window.MunchOwnerSearch.refresh();
        });
    }

    function setupEmployeeActions() {
        document.querySelectorAll("[data-delete-staff]").forEach(function (button) {
            if (button.dataset.dbReady === "true") return;
            button.dataset.dbReady = "true";

            button.addEventListener("click", function () {
                const staffID = button.dataset.deleteStaff;

                if (!confirm("Remove employee " + staffID + "?")) return;

                postOwnerAction("deleteEmployee", { STAFFID: staffID }).then(function (result) {
                    if (!result.success) {
                        alert(result.message || "Could not remove employee.");
                        return;
                    }

                    showMessage("employeeActionMessage", "Employee record removed successfully.");
                    renderEmployees();
                });
            });
        });
    }

    function renderReports() {
        getOwnerData("reports").then(function (data) {
            if (!ensureOwnerOK(data)) return;

            const grid = document.querySelector(".owner-report-grid");

            if (grid) {
                grid.innerHTML = `
                    <article class="owner-report-card"><i class="fa-solid fa-calendar-day"></i><h3>Daily Sales</h3><strong>${money(data.sales.daily)}</strong><p>${data.counts.dailyOrders} orders today</p><span class="status-badge ready">Live DB</span></article>
                    <article class="owner-report-card"><i class="fa-solid fa-calendar-week"></i><h3>Weekly Sales</h3><strong>${money(data.sales.weekly)}</strong><p>${data.counts.weeklyOrders} orders this week</p><span class="status-badge ready">Live DB</span></article>
                    <article class="owner-report-card"><i class="fa-solid fa-calendar-days"></i><h3>Monthly Sales</h3><strong>${money(data.sales.monthly)}</strong><p>${data.counts.monthlyOrders} orders this month</p><span class="status-badge preparing">Live DB</span></article>
                    <article class="owner-report-card"><i class="fa-solid fa-calendar"></i><h3>Yearly Sales</h3><strong>${money(data.sales.yearly)}</strong><p>${data.counts.yearlyOrders} orders this year</p><span class="status-badge ready">Live DB</span></article>
                `;
            }

            const rankingBody = document.querySelector(".owner-ranking-table tbody");

            if (rankingBody) {
                rankingBody.innerHTML = data.itemRanking.map(function (item, index) {
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${esc(item.MENUNAME)}</td>
                            <td>${esc(item.MENUCATEGORY)}</td>
                            <td>${item.qty}</td>
                            <td>${money(item.revenue)}</td>
                            <td>${badge(index < 3 ? "Popular" : "Review")}</td>
                            <td>${index < 3 ? "Promote / keep stock" : "Review demand"}</td>
                        </tr>
                    `;
                }).join("");
            }

            renderProgressPanel("payment", data.paymentMix, "total", "method");
            renderProgressPanel("popular", data.itemRanking, "qty", "MENUNAME");

            if (window.MunchOwnerSearch) window.MunchOwnerSearch.refresh();
        });
    }

    function renderBookings() {
        getOwnerData("bookings").then(function (data) {
            if (!ensureOwnerOK(data)) return;

            setStatCards([
                { value: data.normalReservations.length, label: "Normal Reservations" },
                { value: data.totalReservationPax, label: "Total Reserved Pax" },
                { value: data.cateringEvents.length, label: "Catering Events" },
                { value: data.totalCateringPax, label: "Catering Pax" },
                { value: money(data.depositCollected), label: "Deposit Collected" },
                { value: data.pendingBalance, label: "Pending Balance" }
            ]);

            ensureBookingActionHeaders();
            setupBookingStatusActions();

            const reservationBody = document.querySelector("#reservation-tracker tbody");

            if (reservationBody) {
                reservationBody.innerHTML = data.normalReservations.map(function (row) {
                    return `
                        <tr>
                            <td>${esc(row.RESERVEID)}</td>
                            <td>${esc(row.FULLNAME)}</td>
                            <td>${esc(row.RESERVEDATE)}</td>
                            <td>${esc(row.TIMESLOT)}</td>
                            <td>${esc(row.guestCount)}</td>
                            <td>${esc(row.SEATINGPREF || "-")}</td>
                            <td>${money(row.DEPOSIT)}</td>
                            <td>${badge(row.STATUS)}</td>
                            ${bookingActionCell(row)}
                        </tr>
                    `;
                }).join("");
            }

            const cateringBody = document.querySelector("#catering-tracker tbody");

            if (cateringBody) {
                cateringBody.innerHTML = data.cateringEvents.map(function (row) {
                    return `
                        <tr>
                            <td>${esc(row.RESERVEID)}</td>
                            <td>${esc(row.OCCASION || "Catering")}</td>
                            <td>${esc(row.RESERVEDATE)}</td>
                            <td>${esc(row.guestCount)}</td>
                            <td>${esc(row.SESSION || "Catering")}</td>
                            <td>${esc((row.SPECIALREQ || "-").slice(0, 90))}</td>
                            <td>${money(row.DEPOSIT)}</td>
                            <td>${row.balanceDue ? money(row.balanceDue) : "-"}</td>
                            <td>${badge(row.STATUS)}</td>
                            ${bookingActionCell(row)}
                        </tr>
                    `;
                }).join("");
            }

            renderProgressPanel("date", data.dateDemand, "bookingCount", "RESERVEDATE");

            if (window.MunchOwnerSearch) window.MunchOwnerSearch.refresh();
        });
    }


    function setupMenuImagePreview() {
        const input = document.getElementById("menuImage");
        const preview = document.getElementById("menuImagePreview");

        if (!input || !preview || input.dataset.previewReady === "true") return;

        input.dataset.previewReady = "true";

        input.addEventListener("change", function () {
            const file = input.files && input.files[0];

            if (!file) {
                preview.innerHTML = "<span>No image selected yet</span>";
                return;
            }

            const allowed = ["image/jpeg", "image/png", "image/webp"];

            if (!allowed.includes(file.type)) {
                alert("Please choose JPG, PNG, or WEBP image only.");
                input.value = "";
                preview.innerHTML = "<span>No image selected yet</span>";
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                alert("Menu image must be 3MB or smaller.");
                input.value = "";
                preview.innerHTML = "<span>No image selected yet</span>";
                return;
            }

            preview.innerHTML = `
                <img src="${URL.createObjectURL(file)}" alt="Menu image preview">
                <small>${esc(file.name)}</small>
            `;
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupMenuImagePreview();

        const page = window.location.pathname.toLowerCase();

        if (page.includes("owner-dashboard")) renderDashboard();
        if (page.includes("owner-menu-management")) renderMenu();
        if (page.includes("owner-employee-management")) renderEmployees();
        if (page.includes("owner-reports")) renderReports();
        if (page.includes("owner-booking-tracker")) renderBookings();
    });
})();
