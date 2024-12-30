// Toast configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
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

// GSAP animations for page load
gsap.from(".card", {
    duration: 0.6,
    opacity: 0,
    y: 30,
    ease: "power2.out",
});

// Confirmation dialogs
document.querySelectorAll(".btn-danger").forEach((button) => {
    button.addEventListener("click", function (event) {
        if (!confirm("Are you sure you want to delete this request?")) {
            event.preventDefault();
        }
    });
});
