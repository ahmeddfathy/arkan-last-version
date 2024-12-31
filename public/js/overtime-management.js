// Form Validation
function setupFormValidation() {
    const forms = document.querySelectorAll(".needs-validation");
    Array.from(forms).forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });
}

// Registration Type Handler
function registrationTypeHandler() {
    const registrationType = document.querySelectorAll(
        'input[name="registration_type"]'
    );
    const employeeSelect = document.getElementById("employee_select_container");
    const hiddenUserId = document.getElementById("hidden_user_id");
    const userIdSelect = document.getElementById("user_id");

    if (!registrationType) return;

    registrationType.forEach((input) => {
        input.addEventListener("change", function () {
            if (this.value === "other") {
                employeeSelect.classList.remove("d-none");
                hiddenUserId.disabled = true;
                userIdSelect.required = true;
            } else {
                employeeSelect.classList.add("d-none");
                hiddenUserId.disabled = false;
                userIdSelect.required = false;
            }
        });
    });
}

// Status Change Handler
function setupStatusHandler(statusInputs, reasonContainer, reasonInput) {
    if (!statusInputs) return;

    statusInputs.forEach((input) => {
        input.addEventListener("change", function () {
            if (this.value === "rejected") {
                reasonContainer.classList.remove("d-none");
                reasonInput.required = true;
            } else {
                reasonContainer.classList.add("d-none");
                reasonInput.required = false;
            }
        });
    });
}

// Edit Modal Handler
function setupEditModal() {
    const editButtons = document.querySelectorAll(".edit-btn");
    editButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const request = JSON.parse(this.dataset.request);
            const form = document.getElementById("editOvertimeForm");
            form.action = `/overtime-requests/${request.id}`;

            document.getElementById("edit_overtime_date").value =
                request.overtime_date;
            document.getElementById("edit_start_time").value =
                request.start_time;
            document.getElementById("edit_end_time").value = request.end_time;
            document.getElementById("edit_reason").value = request.reason;
        });
    });
}

// Response Modal Handler
function setupResponseModal() {
    const responseButtons = document.querySelectorAll(".respond-btn");
    responseButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById("respondOvertimeForm");

            form.action = `/overtime-requests/${requestId}/${responseType}-status`;
            document.getElementById("response_type").value = responseType;
        });
    });
}

// Initialize All
document.addEventListener("DOMContentLoaded", function () {
    setupFormValidation();
    registrationTypeHandler();
    setupEditModal();
    setupResponseModal();

    // Setup Status Handlers
    setupStatusHandler(
        document.querySelectorAll('input[name="status"]'),
        document.getElementById("rejection_reason_container"),
        document.getElementById("rejection_reason")
    );

    setupStatusHandler(
        document.querySelectorAll('input[name="modify_status"]'),
        document.getElementById("modify_rejection_reason_container"),
        document.getElementById("modify_rejection_reason")
    );
});
