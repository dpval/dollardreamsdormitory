/* =========================================================
   DOLLAR DREAMS DORMITORY - CORE CLIENT SCRIPT
   ========================================================= */

// Global State
let allUnitsData = {};

/* =========================================================
   1. AUTHENTICATION & ADMIN HELPERS
   ========================================================= */

function isAdmin() {
    return localStorage.getItem("adminLogged") === "true";
}

function openAdminLogin() {
    const adminPopup = document.getElementById("adminPopup");
    if (adminPopup) adminPopup.style.display = "flex";
}

function closeAdminLogin() {
    const adminPopup = document.getElementById("adminPopup");
    if (adminPopup) adminPopup.style.display = "none";
}

function adminLogin() {
    const username = document.getElementById("adminUser")?.value;
    const password = document.getElementById("adminPass")?.value;

    if (username === "admin" && password === "admin123") {
        localStorage.setItem("adminLogged", "true");
        alert("Admin Login Successful");
        closeAdminLogin();
        loadUnits();
    } else {
        alert("Invalid credentials.");
    }
}

function logoutAdmin() {
    localStorage.removeItem("adminLogged");
    location.reload();
}

/* =========================================================
   2. DATA LOADING & RENDERING (DATABASE SYNC)
   ========================================================= */

function loadUnits() {
    fetch("load_units.php")
        .then(res => res.json())
        .then(data => {
            allUnitsData = data.units || {};
            renderUnits(allUnitsData);
        })
        .catch(err => {
            console.error("Failed to load units from database:", err);
        });
}

function renderUnits(data) {
    const container = document.getElementById("unitsContainer") || document.getElementById("units");
    if (!container) return;

    // Admin Bar Controls Toggle
    const logoutBtn = document.getElementById("logoutBtn");
    const createBtn = document.getElementById("createUnitBtn");
    if (logoutBtn) logoutBtn.style.display = isAdmin() ? "block" : "none";
    if (createBtn) createBtn.style.display = isAdmin() ? "block" : "none";

    let html = '<div class="unit-grid">';

    for (let unitName in data) {
        let groups = Object.values(data[unitName]);
        
        // Find default display image (prioritize available items)
        let firstItem = null;
        for (let g of groups) {
            if (Array.isArray(g.items)) {
                firstItem = g.items.find(i => i.status !== "booked") || g.items[0];
                if (firstItem) break;
            }
        }
        if (!firstItem && groups.length > 0) {
            firstItem = groups[0].items?.[0];
        }

        // Calculate Availability Counts
        let available = 0;
        let booked = 0;

        for (let g of groups) {
            let items = g.items || [];
            items.forEach(i => {
                if (g.is_bedspace) {
                    let match = i.description?.match(/-(\d+)$/);
                    let capacity = match ? parseInt(match[1]) : 1;
                    available += capacity;
                    if (i.status === "booked") {
                        booked += 1;
                        available -= 1;
                    }
                } else {
                    if (i.status === "booked") booked++;
                    else available++;
                }
            });
        }

        let isFullyBooked = available <= 0;
        let roomLabel = groups[0]?.is_bedspace ? "" : (firstItem?.room_type || "");

        // Build HTML Card Structure
        html += `
        <div class="unit-card unit" id="card_${unitName}">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <h3 class="unit-id">${unitName}</h3>
                <div style="font-weight:bold; font-size: 14px;">
                    <span style="color:#2ecc71;">Available: ${available}</span>
                    <span style="color:#ff4d4d; margin-left:8px;">Booked: ${booked}</span>
                </div>
            </div>

            <img class="main-image unit-front" id="img_${unitName}" 
                 src="${firstItem?.image || ''}" 
                 onclick="openImage(this)">

            <h4>${roomLabel}</h4>

            <div class="thumbnails type-carousel" id="carousel_${unitName}">
        `;

        // Render Item Thumbnails
        groups.forEach(g => {
            (g.items || []).forEach((item, index) => {
                html += `
                <img src="${item.image}" 
                     onclick="setMainImage(this)"
                     data-id="${item.id}"
                     data-type="${item.room_type || ''}"
                     data-description="${item.description || ''}"
                     class="${index === 0 ? 'active selected' : ''}">
                `;
            });
        });

        // Action Buttons Depending on User Role
        html += `
            </div>
            <div class="action-buttons" style="margin-top:15px;">
        `;

        if (isAdmin()) {
            html += `
                <button class="unit-btn" onclick="editUnit('${firstItem?.id}')">Edit</button>
                <button class="unit-btn delete-btn" onclick="deleteUnit('${firstItem?.id}')">Delete</button>
            `;
        } else {
            html += `
                <button class="unit-btn" onclick="openBook(this)" ${isFullyBooked ? 'disabled' : ''}>
                    ${isFullyBooked ? 'Fully Booked' : 'Book'}
                </button>
            `;
        }

        html += `
            </div>
        </div>
        `;
    }

    html += "</div>";
    container.innerHTML = html;
}


/* =========================================================
   3. GALLERY & IMAGE INTERACTION
   ========================================================= */

function setMainImage(img) {
    let u = img.closest(".unit-card") || img.closest(".unit");
    let mainImg = u.querySelector(".main-image") || u.querySelector(".unit-front img");
    
    if (mainImg) mainImg.src = img.src;

    let unitName = u.querySelector("h3, .unit-id").innerText.toLowerCase();
    let label = u.querySelector("h4");
    
    if (label) {
        label.innerText = unitName.includes("bed") ? "" : (img.dataset.type || "");
    }

    // Save Selection Metadata to Card Data Set
    u.dataset.sel = JSON.stringify({
        id: img.dataset.id,
        type: img.dataset.type,
        description: img.dataset.description
    });

    // Update Visual Highlights
    u.querySelectorAll(".thumbnails img").forEach(i => {
        i.classList.remove("active", "selected");
    });
    img.classList.add("active", "selected");
}

function openImage(img) {
    const zoomImg = document.getElementById("zoomImg");
    const imageModal = document.getElementById("imageModal");
    if (zoomImg && imageModal) {
        zoomImg.src = img.src;
        imageModal.style.display = "flex";
    }
}

function closeImageModal() {
    const imageModal = document.getElementById("imageModal");
    if (imageModal) imageModal.style.display = "none";
}

/* =========================================================
   4. MODALS & BOOKING HANDLERS
   ========================================================= */

function openBook(btn) {
    let u = btn.closest(".unit-card") || btn.closest(".unit");
    let sel = JSON.parse(u.dataset.sel || "{}");
    let firstThumb = u.querySelector(".thumbnails img");

    let unitTitle = u.querySelector("h3, .unit-id").innerText;
    let roomType = sel.type || firstThumb?.dataset.type || "";
    let desc = sel.description || firstThumb?.dataset.description || "";
    let id = sel.id || firstThumb?.dataset.id || "";

    if (document.getElementById("book_unit")) document.getElementById("book_unit").value = unitTitle;
    if (document.getElementById("book_type")) document.getElementById("book_type").value = roomType;
    if (document.getElementById("description")) document.getElementById("description").value = desc;
    if (document.getElementById("unit_id")) document.getElementById("unit_id").value = id;

    calcDays();

    const bookingModal = document.getElementById("bookingModal") || document.getElementById("popup");
    if (bookingModal) bookingModal.style.display = "flex";
}

function closeModal() {
    const bookingModal = document.getElementById("bookingModal") || document.getElementById("popup");
    if (bookingModal) bookingModal.style.display = "none";
}

function calcDays() {
    let inD = document.querySelector("[name=move_in]") || document.getElementById("move_in");
    let outD = document.querySelector("[name=move_out]") || document.getElementById("move_out");
    let t = document.getElementById("total_days") || document.getElementById("term");

    if (!inD || !outD || !t) return;

    function update() {
        if (inD.value) {
            let s = new Date(inD.value);
            let e = outD.value ? new Date(outD.value) : s;
            let diffDays = Math.floor((e - s) / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays < 1) diffDays = 1;
            t.value = diffDays + " day(s)";
        }
    }

    inD.onchange = update;
    outD.onchange = update;
}

/* =========================================================
   5. VIEWING SCHEDULES
   ========================================================= */

function loadSchedules() {
    let select = document.getElementById("scheduleTop");
    if (!select) return;

    fetch("load_schedule.php")
        .then(res => res.json())
        .then(data => {
            select.innerHTML = `<option value="">Select Viewing Schedule</option>`;
            data.forEach(s => {
                let formatted = s.day + ", " +
                    new Date(s.date).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' }) +
                    " / " + formatTime(s.time_start) + " - " + formatTime(s.time_end);

                let option = document.createElement("option");
                option.value = formatted;
                option.textContent = formatted;
                select.appendChild(option);
            });
        })
        .catch(err => console.error("Schedule error:", err));
}

function formatTime(time) {
    if (!time) return "";
    let [h, m] = time.split(":");
    let hour = parseInt(h);
    let ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;
    return `${hour}:${m} ${ampm}`;
}

function closeViewing() {
    const viewingModal = document.getElementById("viewingModal");
    if (viewingModal) viewingModal.style.display = "none";
}

/* =========================================================
   6. ADMIN MANAGEMENT ACTIONS (CRUD)
   ========================================================= */

function deleteUnit(id) {
    if (confirm("Are you sure you want to delete this unit?")) {
        fetch(`delete_unit.php?id=${id}`)
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "deleted") {
                    alert("Unit deleted successfully.");
                    loadUnits();
                } else {
                    alert("Delete failed: " + data);
                }
            })
            .catch(err => console.error(err));
    }
}

function editUnit(id) {
    window.location.href = `edit_unit.php?id=${id}`;
}

function openCreateUnit() {
    const popup = document.getElementById("adminUnitPopup");
    if (popup) popup.style.display = "flex";
}

function closeAdminUnitPopup() {
    const popup = document.getElementById("adminUnitPopup");
    if (popup) popup.style.display = "none";
}

/* =========================================================
   7. INITIALIZATION & FORM SUBMISSIONS
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    loadUnits();
    loadSchedules();

    // Event Delegation: Close Modals Outside Click
    window.onclick = function (e) {
        let bookingModal = document.getElementById("bookingModal") || document.getElementById("popup");
        let viewingModal = document.getElementById("viewingModal");
        if (e.target === bookingModal) closeModal();
        if (e.target === viewingModal) closeViewing();
    };

    // Viewing Schedule Select Event Listener
    const scheduleTop = document.getElementById("scheduleTop");
    if (scheduleTop) {
        scheduleTop.onchange = function () {
            if (this.value) {
                let selInput = document.getElementById("selectedSchedule");
                let viewingModal = document.getElementById("viewingModal");
                if (selInput) selInput.value = this.value;
                if (viewingModal) viewingModal.style.display = "flex";
            }
        };
    }

    // Booking Form Submission Handler
    const bookingForm = document.getElementById("bookingForm");
    if (bookingForm) {
        bookingForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch("save_booking.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("Booking saved!");
                    closeModal();
                    loadUnits();
                } else {
                    alert("Error: " + data);
                }
            })
            .catch(err => alert("Server Error"));
        });
    }

    // Viewing Form Submission Handler
    const viewingForm = document.getElementById("viewingForm");
    if (viewingForm) {
        viewingForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch("save_viewing.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("Viewing request saved!");
                    closeViewing();
                    this.reset();
                } else {
                    alert("Error: " + data);
                }
            })
            .catch(err => alert("Server Error"));
        });
    }
});