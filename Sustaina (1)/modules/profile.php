<?php
// User Profile Module
if (!isset($_SESSION['user_id'])) {
    echo "Access Denied.";
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<div class="card view-section" style="position: relative;">
    <button type="button" onclick="window.openEditProfileModal()" class="btn btn-secondary" style="position: absolute; top: 1.5rem; right: 1.5rem; padding: 0.5rem 0.8rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: white; cursor: pointer; color: var(--text-primary); box-shadow: 0 2px 8px rgba(29, 51, 44, 0.08); transition: var(--transition-fast); z-index: 50;">
        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Profile
    </button>
    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; box-shadow: 0 4px 12px rgba(6,78,59,0.2);">
            <?= strtoupper(substr($user['full_name'] ?? $user['email'], 0, 1)) ?>
        </div>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem; color: var(--text-primary);">
                <?= htmlspecialchars($user['full_name'] ?? explode('@', $user['email'])[0]) ?>
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                <?= htmlspecialchars($user['email']) ?> 
                &bull; 
                <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($user['role'] ?? 'Member') ?></span>
            </p>
            <span class="badge badge-fresh">Active Account</span>
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">

    <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-primary);">Account Information</h4>
    <div class="form-grid">
        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" value="<?= htmlspecialchars($user['phone'] ?? 'Not Provided') ?>" disabled style="background: #f3f4f6;">
        </div>
        <div class="form-group">
            <label>Account ID</label>
            <input type="text" value="#<?= str_pad($user['id'], 5, '0', STR_PAD_LEFT) ?>" disabled style="background: #f3f4f6;">
        </div>
        <div class="form-group full-width">
            <label>Member Since</label>
            <input type="text" value="<?= date('F j, Y, g:i A', strtotime($user['created_at'])) ?>" disabled style="background: #f3f4f6;">
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; padding: 1rem;">
    <div class="modal-content card" style="max-width: 500px; width: 100%; background: var(--bg-card); border-radius: var(--radius-xl); padding: 2rem; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin: 0;">Edit Profile</h3>
            <button type="button" onclick="window.closeEditProfileModal()" style="background: none; border: none; cursor: pointer; color: var(--text-muted); transition: var(--transition-fast);">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form action="actions.php" method="POST">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name or Business Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label>Account Role</label>
                    <div style="position: relative;">
                        <select name="role" required style="width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #fbfbf9; color: var(--text-primary); font-size: 0.9rem; font-weight: 500; appearance: none; outline: none; cursor: pointer;">
                            <option value="Restaurant" <?= ($user['role'] == 'Restaurant') ? 'selected' : '' ?>>Restaurant</option>
                            <option value="Supermarket" <?= ($user['role'] == 'Supermarket') ? 'selected' : '' ?>>Supermarket</option>
                            <option value="Cafe" <?= ($user['role'] == 'Cafe') ? 'selected' : '' ?>>Cafe / Bakery</option>
                            <option value="Household" <?= ($user['role'] == 'Household') ? 'selected' : '' ?>>Household</option>
                            <option value="Charity" <?= ($user['role'] == 'Charity') ? 'selected' : '' ?>>Charity / Food Bank</option>
                            <option value="Member" <?= ($user['role'] == 'Member' || empty($user['role'])) ? 'selected' : '' ?>>Member</option>
                        </select>
                        <i data-lucide="chevron-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; width: 16px; height: 16px;"></i>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Contact Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                    <label>Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f3f4f6; cursor: not-allowed; color: var(--text-muted);" title="Email address cannot be changed">
                </div>
            </div>
            
            <div class="modal-actions" style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditProfileModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
window.openEditProfileModal = function() {
    const modal = document.getElementById('editProfileModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.closeEditProfileModal = function() {
    const modal = document.getElementById('editProfileModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Close on outside click
const modalEl = document.getElementById('editProfileModal');
if (modalEl) {
    modalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            window.closeEditProfileModal();
        }
    });
}

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
