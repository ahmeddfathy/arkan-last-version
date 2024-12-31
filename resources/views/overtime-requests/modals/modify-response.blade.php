<div class="modal fade" id="modifyResponseModal" tabindex="-1" aria-labelledby="modifyResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyResponseModalLabel">Modify Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modifyResponseForm" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="modify_response_type" name="response_type">

                    <div class="mb-3">
                        <label class="form-label d-block">New Response</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status" id="modify_approve" value="approved" required>
                            <label class="btn btn-outline-success" for="modify_approve">
                                <i class="fas fa-check"></i> Approve
                            </label>

                            <input type="radio" class="btn-check" name="status" id="modify_reject" value="rejected" required>
                            <label class="btn btn-outline-danger" for="modify_reject">
                                <i class="fas fa-times"></i> Reject
                            </label>
                        </div>
                        <div class="invalid-feedback">Please select a response.</div>
                    </div>

                    <div class="mb-3 d-none" id="modify_rejection_reason_container">
                        <label for="modify_rejection_reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="modify_rejection_reason" name="rejection_reason" rows="3"></textarea>
                        <div class="invalid-feedback">Please provide a reason for rejection.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Response</button>
                </div>
            </form>
        </div>
    </div>
</div>
