// =============================
// MUNCH PROFILE.JS - FINAL FOLLOW DESIGN FIX
// Works with profileFollow design.
// Left edit button removed. Click profile card / pen icon to edit.
// Loads real DB data from displayUserInfo.php and saves to updateProfile.php.
// =============================

(function () {
    function qs(selector) {
        return document.querySelector(selector);
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function setText(id, value) {
        const el = byId(id);
        if (el) el.textContent = value && String(value).trim() !== "" ? value : "-";
    }

    function formatRM(value) {
        const amount = Number(value || 0);
        return amount > 0 ? "RM" + amount.toFixed(2) : "-";
    }

    function safePic(path) {
        return path && String(path).trim() !== "" ? path : "img/user-1.png";
    }

    function setImages(path) {
        const src = safePic(path) + "?v=" + Date.now();
        const main = byId("profile-picture");
        const preview = byId("edit-profile-preview");

        if (main) main.src = src;
        if (preview) preview.src = src;
    }

    let currentCustomer = {};

    function openPopup() {
        const overlay = byId("profileEditOverlay");
        if (!overlay) return;

        overlay.classList.add("active");
        overlay.setAttribute("aria-hidden", "false");
    }

    function closePopup() {
        const overlay = byId("profileEditOverlay");
        const msg = byId("profileEditMessage");

        if (!overlay) return;

        overlay.classList.remove("active");
        overlay.setAttribute("aria-hidden", "true");

        if (msg) msg.classList.remove("active");
    }

    function loadProfileSummary() {
        fetch("displayUserInfo.php", { cache: "no-store" })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || "Please login first.");
                    window.location.href = "customer-login.html";
                    return;
                }

                currentCustomer = data.customer || {};

                setText("profile-name", currentCustomer.CUSTNAME);
                setText("profile-username", currentCustomer.CUSTUSERNAME);
                setText("profile-phone", currentCustomer.PHONENO);
                setImages(currentCustomer.CUSTPROFILEPIC);

                const nameInput = byId("profileNameInput");
                const phoneInput = byId("profilePhoneInput");

                if (nameInput) nameInput.value = currentCustomer.CUSTNAME || "";
                if (phoneInput) phoneInput.value = currentCustomer.PHONENO || "";

                if (data.recentOrder) {
                    setText("recent-order-id", data.recentOrder.ORDERID);
                    setText("recent-order-meal", data.recentOrder.MEALS || "Order recorded");
                    setText("recent-order-status", data.recentOrder.ORDERSTATUS);
                    setText("recent-order-total", formatRM(data.recentOrder.TOTAL));
                } else {
                    setText("recent-order-id", "No order yet");
                    setText("recent-order-meal", "-");
                    setText("recent-order-status", "-");
                    setText("recent-order-total", "-");
                }

                if (data.upcomingReservation) {
                    setText("reservation-id", data.upcomingReservation.RESERVEID);
                    setText("reservation-date", data.upcomingReservation.RESERVEDATE);
                    setText("reservation-time", data.upcomingReservation.TIMESLOT);
                    setText("reservation-pax", data.upcomingReservation.guestCount + " people");
                } else {
                    setText("reservation-id", "No upcoming reservation");
                    setText("reservation-date", "-");
                    setText("reservation-time", "-");
                    setText("reservation-pax", "-");
                }

                if (data.favouriteMeal) {
                    setText("favourite-dish", data.favouriteMeal.MENUNAME);
                    setText("favourite-category", data.favouriteMeal.MENUCATEGORY);
                    setText("favourite-count", data.favouriteMeal.TOTALQTY + " times");
                    setText("favourite-menu-id", data.favouriteMeal.MENUID);
                } else {
                    setText("favourite-dish", "No favourite meal yet");
                    setText("favourite-category", "-");
                    setText("favourite-count", "-");
                    setText("favourite-menu-id", "-");
                }

                if (data.paymentDetails) {
                    setText("payment-method", data.paymentDetails.PREFERREDMETHOD);
                    setText("last-payment", formatRM(data.paymentDetails.LASTPAYMENT));
                    setText("payment-sales-id", data.paymentDetails.SALESID);
                    setText("payment-status", "Paid");
                } else {
                    setText("payment-method", "No payment yet");
                    setText("last-payment", "-");
                    setText("payment-sales-id", "-");
                    setText("payment-status", "-");
                }
            })
            .catch(function (error) {
                console.error("Profile loading error:", error);
                alert("Could not load profile data. Please check displayUserInfo.php.");
            });
    }

    function setupCardClick() {
        const card = byId("profileCardTrigger");
        const pen = qs(".profile-card-edit-btn");

        if (card) {
            card.addEventListener("click", function () {
                openPopup();
            });

            card.addEventListener("keydown", function (event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openPopup();
                }
            });
        }

        if (pen) {
            pen.addEventListener("click", function (event) {
                event.stopPropagation();
                openPopup();
            });
        }
    }

    function setupCloseButtons() {
        const close = byId("closeProfileEditBtn");
        const cancel = byId("cancelProfileEditBtn");
        const overlay = byId("profileEditOverlay");

        if (close) close.addEventListener("click", closePopup);
        if (cancel) cancel.addEventListener("click", closePopup);

        if (overlay) {
            overlay.classList.remove("active");
            overlay.setAttribute("aria-hidden", "true");

            overlay.addEventListener("click", function (event) {
                if (event.target === overlay) {
                    closePopup();
                }
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closePopup();
            }
        });
    }

    function setupImagePreview() {
        const file = byId("profilePictureInput");
        const preview = byId("edit-profile-preview");

        if (!file || !preview) return;

        file.addEventListener("change", function () {
            const chosen = file.files && file.files[0];

            if (!chosen) {
                setImages(currentCustomer.CUSTPROFILEPIC);
                return;
            }

            if (!["image/jpeg", "image/png", "image/webp"].includes(chosen.type)) {
                alert("Please choose JPG, PNG, or WEBP image only.");
                file.value = "";
                return;
            }

            if (chosen.size > 2 * 1024 * 1024) {
                alert("Profile picture must be 2MB or smaller.");
                file.value = "";
                return;
            }

            preview.src = URL.createObjectURL(chosen);
        });
    }

    function setupFormSubmit() {
        const form = byId("profileEditForm");
        const msg = byId("profileEditMessage");

        if (!form) return;

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch("updateProfile.php", {
                method: "POST",
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || "Could not update profile.");
                        return;
                    }

                    if (msg) msg.classList.add("active");

                    const password = byId("profilePasswordInput");
                    const file = byId("profilePictureInput");

                    if (password) password.value = "";
                    if (file) file.value = "";

                    loadProfileSummary();

                    setTimeout(function () {
                        closePopup();
                    }, 800);
                })
                .catch(function (error) {
                    console.error("Update profile error:", error);
                    alert("Could not update profile. Please check updateProfile.php.");
                });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadProfileSummary();
        setupCardClick();
        setupCloseButtons();
        setupImagePreview();
        setupFormSubmit();

        if (typeof AOS !== "undefined") {
            AOS.init({ duration: 800, once: true });
        }
    });
})();
