import "./bootstrap";
import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.start();

// Toast configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 20000,
    extendedTimeOut: 1000,
    preventDuplicates: true,
    newestOnTop: true,
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
};

// Duplicate request check
function checkDuplicateRequest(formData, type) {
    const existingRequests = document.querySelectorAll(".request-row");
    let isDuplicate = false;

    existingRequests.forEach((row) => {
        if (type === "absence") {
            const date = row.children[0].textContent;
            if (
                date === formData.get("absence_date") &&
                row.children[2].textContent.includes("pending")
            ) {
                isDuplicate = true;
            }
        } else if (type === "permission") {
            const datetime = row.children[0].textContent;
            if (
                datetime === formData.get("request_datetime") &&
                row.children[3].textContent.includes("pending")
            ) {
                isDuplicate = true;
            }
        }
    });

    return isDuplicate;
}

// Form submission handling
document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function (event) {
        const formData = new FormData(this);

        // Check for duplicates on create forms
        if (
            this.id === "createAbsenceForm" &&
            checkDuplicateRequest(formData, "absence")
        ) {
            event.preventDefault();
            toastr.error("You already have a pending request for this date.");
            return;
        }

        if (
            this.id === "createPermissionForm" &&
            checkDuplicateRequest(formData, "permission")
        ) {
            event.preventDefault();
            toastr.error(
                "You already have a pending request for this date and time."
            );
            return;
        }

        // Validate permission duration
        if (
            this.id === "createPermissionForm" ||
            this.id === "editPermissionForm"
        ) {
            const duration = parseInt(formData.get("duration_minutes"));
            if (duration > 180) {
                event.preventDefault();
                toastr.error("Permission duration cannot exceed 180 minutes.");
                return;
            }
        }

        // Show loading spinner
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<div class="loading-spinner"></div>';
        submitBtn.disabled = true;

        // Reset button after submission
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 1000);
    });
});

// Date and time input restrictions
document
    .querySelectorAll('input[type="date"], input[type="datetime-local"]')
    .forEach((input) => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate());
        input.min = tomorrow.toISOString().split("T")[0];
    });

// Confirmation dialogs
document.querySelectorAll(".btn-danger").forEach((button) => {
    button.addEventListener("click", function (event) {
        if (!confirm("Are you sure you want to delete this request?")) {
            event.preventDefault();
        }
    });
});

// Initialize tooltips
const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
