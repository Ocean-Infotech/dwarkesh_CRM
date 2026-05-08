<?php
$pageTitle = "My Profile";
$currentPage = "profile";
$headerTitle = "Profile Settings";

include 'include/header.php';

// Fetch current user data
$adminData = $ai_db->aiGetQuery("SELECT * FROM " . DB_PREFIX . "admin WHERE id=" . $_SESSION['aid'])[0];
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row g-4">
                <!-- Profile Info Card -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-body p-5 text-center">
                            <div class="position-relative d-inline-block mb-4">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminData['username']) ?>&background=c5a059&color=fff&size=128" 
                                     class="rounded-circle shadow" width="120" height="120">
                                <div class="position-absolute bottom-0 end-0 bg-success border border-white border-3 rounded-circle p-2" title="Online"></div>
                            </div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($adminData['username']) ?></h4>
                            <p class="text-muted mb-4">Super Administrator</p>
                            
                            <div class="text-start mt-4 border-top pt-4">
                                <div class="mb-3">
                                    <label class="small text-uppercase fw-bold text-muted opacity-75">Email Address</label>
                                    <div class="fw-semibold"><?= htmlspecialchars($adminData['email']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-uppercase fw-bold text-muted opacity-75">Username</label>
                                    <div class="fw-semibold">@<?= htmlspecialchars($adminData['username']) ?></div>
                                </div>
                                <div class="mb-0">
                                    <label class="small text-uppercase fw-bold text-muted opacity-75">Account Status</label>
                                    <div><span class="badge bg-success-subtle text-success rounded-pill px-3">Active</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security / Change Password -->
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-transparent border-0 p-4 pb-0">
                            <h5 class="fw-bold m-0"><i class="bi bi-shield-lock me-2 text-gold"></i> Security Settings</h5>
                            <p class="text-muted small m-0">Update your account password and security preferences</p>
                        </div>
                        <div class="card-body p-4">
                            <form id="changePasswordForm">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-key"></i></span>
                                        <input type="password" name="old_password" class="form-control bg-light border-0" required placeholder="Enter current password">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-lock"></i></span>
                                        <input type="password" name="new_password" id="new_password" class="form-control bg-light border-0" required minlength="6" placeholder="Enter new password">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-check2-circle"></i></span>
                                        <input type="password" name="confirm_password" class="form-control bg-light border-0" required placeholder="Repeat new password">
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-gold py-3 fw-bold rounded-3 shadow-sm">
                                        <i class="bi bi-save me-2"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const newPass = formData.get('new_password');
            const confirmPass = formData.get('confirm_password');
            
            if(newPass !== confirmPass) {
                showToast('Error', 'New passwords do not match!', 'danger');
                return;
            }
            
            formData.append('action', 'update-password');
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Updating...';
            
            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast('Success', data.message, 'success');
                    form.reset();
                } else {
                    showToast('Error', data.message, 'danger');
                }
            })
            .catch(err => {
                showToast('Error', 'Something went wrong connection to server', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
});
</script>

<?php include 'include/footer.php'; ?>
