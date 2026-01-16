<?php
// Note: generateCSRF() and validateCSRF() are defined in config/database.php
// Do not redeclare them here
?>

<!-- Verify Tag Modal -->
<div class="modal fade" id="verifyTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-circle text-success me-2"></i>Verify Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="cocktail_tag_id" id="verifyTagId">
                    
                    <div class="mb-4">
                        <p class="text-muted mb-2">Tag Name:</p>
                        <h5 class="fw-bold" id="verifyTagName"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confidence Score (%)</label>
                        <input type="range" class="form-range" name="confidence" value="100" min="0" max="100" id="confidenceRange">
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Low</small>
                            <small id="confidenceValue" class="fw-bold">100%</small>
                            <small class="text-muted">High</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Verification Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Why do you agree with this tag?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Verify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Tag Modal -->
<div class="modal fade" id="rejectTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-times-circle text-danger me-2"></i>Reject Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="cocktail_tag_id" id="rejectTagId">
                    
                    <div class="mb-4">
                        <p class="text-muted mb-2">Tag Name:</p>
                        <h5 class="fw-bold" id="rejectTagName"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason</label>
                        <select class="form-select" name="reason">
                            <option value="incorrect">Incorrect - Doesn't fit this cocktail</option>
                            <option value="duplicate">Duplicate - Same tag already exists</option>
                            <option value="irrelevant">Irrelevant - Not meaningful</option>
                            <option value="ambiguous">Ambiguous - Unclear meaning</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Custom Reason (Optional)</label>
                        <input type="text" class="form-control" name="custom_reason" placeholder="Additional details...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Why do you disagree?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Tag Modal -->
<div class="modal fade" id="removeTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-trash text-danger me-2"></i>Remove Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="cocktail_tag_id" id="removeTagId">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You are about to remove a verified tag. This action is recorded.
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-muted mb-2">Tag Name:</p>
                        <h5 class="fw-bold" id="removeTagName"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Removal</label>
                        <textarea class="form-control" name="notes" rows="3" required placeholder="Why are you removing this tag?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Remove
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add New Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle text-success me-2"></i>Add New Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="selectTab" data-bs-toggle="tab" data-bs-target="#selectPanel" type="button">
                                <i class="fas fa-list me-2"></i>Select from Pool
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="createTab" data-bs-toggle="tab" data-bs-target="#createPanel" type="button">
                                <i class="fas fa-plus me-2"></i>Create New
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Select from Pool -->
                        <div class="tab-pane fade show active" id="selectPanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Search Tags</label>
                                <input type="text" class="form-control" id="tagSearch" placeholder="Search by name or category...">
                            </div>
                            
                            <div id="tagPoolContainer" style="max-height: 300px; overflow-y: auto;">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading tags...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Create New Tag -->
                        <div class="tab-pane fade" id="createPanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tag Name *</label>
                                <input type="text" class="form-control" id="newTagName" name="new_tag" placeholder="e.g., Refreshing, Herbal, Tropical">
                                <small class="text-muted">One word or short phrase</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Category *</label>
                                <select class="form-select" name="category_id" id="tagCategory">
                                    <option value="">Select a category...</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Confidence Score (%)</label>
                                <input type="range" class="form-range" name="confidence" value="85" min="0" max="100" id="newTagConfidence">
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Unsure</small>
                                    <small id="newConfidenceValue" class="fw-bold">85%</small>
                                    <small class="text-muted">Certain</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Add Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Confidence range update
document.getElementById('confidenceRange')?.addEventListener('input', function() {
    document.getElementById('confidenceValue').textContent = this.value + '%';
});

document.getElementById('newTagConfidence')?.addEventListener('input', function() {
    document.getElementById('newConfidenceValue').textContent = this.value + '%';
});

// Modal data handlers
document.addEventListener('DOMContentLoaded', function() {
    ['verifyTagModal', 'rejectTagModal', 'removeTagModal'].forEach(modalId => {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const tagId = button?.getAttribute('data-tag-id');
                const tagName = button?.getAttribute('data-tag-name');
                
                const fieldIdSuffix = modalId.replace('Modal', '').replace('TagModal', 'Tag');
                const idField = document.getElementById(fieldIdSuffix + 'Id');
                const nameField = document.getElementById(fieldIdSuffix + 'Name');
                
                if (idField) idField.value = tagId;
                if (nameField) nameField.textContent = tagName;
            });
        }
    });
});
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}
</style>