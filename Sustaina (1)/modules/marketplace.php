<?php
/**
 * Sustaina - Surplus Marketplace Feed View Module
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

// Ensure the claims table exists (Fix for missing table crash)
$pdo->exec("
CREATE TABLE IF NOT EXISTS `claims` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inventory_id` INT NOT NULL,
    `buyer_name` VARCHAR(255) NOT NULL,
    `status` VARCHAR(100) DEFAULT 'Pending Pickup',
    `claimed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Determine active user (Fix for hardcoded Bella Grillhouse)
$active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
$active_role = isset($_SESSION['user_role']) && !empty($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Restaurant';

// ==========================================
// 1. FORM HANDLING (CRUD OPERATIONS)
// ==========================================
// Fix for 'Headers already sent' error when using header() inside an included template
function js_redirect($url)
{
    echo "<script>window.location.href = '$url';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // CREATE (Post to Market)
    if ($action === 'post_to_market') {
        $post_type = $_POST['post_type'];
        $price = $_POST['market_price'];
        $desc = trim($_POST['market_desc']);

        if ($post_type === 'inventory') {
            $stmt = $pdo->prepare("UPDATE inventory SET listed_on_market = 1, market_price = :price, market_desc = :desc WHERE id = :id AND seller = :seller");
            $stmt->execute([':price' => $price, ':desc' => $desc, ':id' => $_POST['inventory_id'], ':seller' => $active_user]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO inventory (name, category, qty, expiry_date, listed_on_market, market_price, market_desc, image_url, seller, seller_type, location) 
                VALUES (:name, :category, :qty, :expiry, 1, :price, :desc, :img, :seller, :role, 'Downtown')
            ");
            $stmt->execute([
                ':name' => $_POST['manual_name'],
                ':category' => $_POST['manual_category'],
                ':qty' => $_POST['manual_qty'],
                ':expiry' => $_POST['manual_expiry'],
                ':price' => $price,
                ':desc' => $desc,
                ':img' => !empty($_POST['manual_image_url']) ? $_POST['manual_image_url'] : null,
                ':seller' => $active_user,
                ':role' => $active_role
            ]);
        }
        js_redirect("index.php?page=marketplace&success=posted");
    }

    // UPDATE (Edit Market Post)
    if ($action === 'edit_market_post') {
        $stmt = $pdo->prepare("UPDATE inventory SET market_price = :price, market_desc = :desc WHERE id = :id AND seller = :seller");
        $stmt->execute([
            ':price' => $_POST['edit_price'],
            ':desc' => $_POST['edit_desc'],
            ':id' => $_POST['edit_id'],
            ':seller' => $active_user
        ]);
        js_redirect("index.php?page=marketplace&success=updated");
    }

    // DELETE (Delete Market Post Completely)
    if ($action === 'delete_market_post') {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = :id AND seller = :seller");
        $stmt->execute([':id' => $_POST['delete_id'], ':seller' => $active_user]);
        js_redirect("index.php?page=marketplace&success=deleted");
    }

    // CLAIM SURPLUS
    if ($action === 'claim') {
        $inventory_id = $_POST['id'];
        $buyer_name = isset($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Juan Dela Cruz';

        try {
            $pdo->beginTransaction();

            $stmt_claim = $pdo->prepare("INSERT INTO claims (inventory_id, buyer_name, status) VALUES (:inv_id, :buyer, 'Pending Pickup')");
            $stmt_claim->execute([':inv_id' => $inventory_id, ':buyer' => $buyer_name]);

            $stmt_update = $pdo->prepare("UPDATE inventory SET listed_on_market = 0 WHERE id = :id");
            $stmt_update->execute([':id' => $inventory_id]);

            $pdo->commit();
            js_redirect("index.php?page=messages&item_id=" . $inventory_id . "&chat_with=" . urlencode($_POST['seller_name']));
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error processing claim: " . $e->getMessage());
        }
    }
}

// ==========================================
// 2. DATA RETRIEVAL (GET REQUESTS)
// ==========================================

$cat_filter = isset($_GET['cat']) ? $_GET['cat'] : 'All';

// Fetch unlisted inventory for the "Post" dropdown
$stmt_unlisted = $pdo->prepare("SELECT id, name, category, qty, expiry_date FROM inventory WHERE seller = :seller AND listed_on_market = 0 ORDER BY expiry_date ASC");
$stmt_unlisted->execute([':seller' => $active_user]);
$unlisted_items = $stmt_unlisted->fetchAll();

// Fetch active marketplace items
$query = "SELECT * FROM inventory WHERE listed_on_market = 1";
$params = [];
if ($cat_filter != 'All') {
    if ($cat_filter == 'Free') {
        $query .= " AND market_price = 0.00";
    } else {
        $query .= " AND category = ?";
        $params[] = $cat_filter;
    }
}
$query .= " ORDER BY expiry_date ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$market_items = $stmt->fetchAll();

// Fetch user's sold/donated history
$stmt_history = $pdo->prepare("
    SELECT i.name, i.qty, i.category, i.market_price, c.buyer_name, c.claimed_at, c.status 
    FROM inventory i 
    JOIN claims c ON i.id = c.inventory_id 
    WHERE i.seller = :seller 
    ORDER BY c.claimed_at DESC
");
$stmt_history->execute([':seller' => $active_user]);
$history_items = $stmt_history->fetchAll();

// Category image URLs for premium cards (Fallback)
$category_images = [
    "Meat" => "https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80",
    "Vegetables" => "https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80",
    "Fruits" => "https://images.unsplash.com/photo-1610970881699-44a5587caaec?w=500&auto=format&fit=crop&q=80",
    "Dairy" => "https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500&auto=format&fit=crop&q=80",
    "Bakery" => "https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&auto=format&fit=crop&q=80",
    "Other" => "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80"
];
?>

<!-- ==========================================
// 3. HTML & UI
// ========================================== -->

<style>
    /* Apple/iOS Style UI Extensions - Solid White Theme */
    .apple-modal-bg {
        background: rgba(0, 0, 0, 0.6);
        /* Solid dark overlay */
    }

    .apple-modal-card {
        background: #ffffff;
        /* Solid White Background */
        color: #111827;
        /* Dark text for readability on white */
        border-radius: 24px;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.3);
        border: none;
    }

    .apple-modal-card h3 {
        color: #111827;
    }

    .apple-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #4b5563;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 4px;
    }

    .apple-input {
        background: #f3f4f6;
        /* Solid light gray */
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: 1rem;
        color: #111827;
        transition: all 0.2s ease;
        outline: none;
        width: 100%;
    }

    .apple-input:focus {
        background: #ffffff;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }

    .apple-btn-primary {
        background: #007AFF;
        color: white;
        border: none;
        border-radius: 14px;
        padding: 16px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
        width: 100%;
    }

    .apple-btn-primary:hover {
        opacity: 0.85;
    }

    .apple-btn-danger {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 14px;
        padding: 16px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        width: 100%;
    }

    .apple-btn-danger:hover {
        background: #fca5a5;
    }

    .apple-close-btn {
        background: #f3f4f6;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        cursor: pointer;
        transition: background 0.2s;
    }

    .apple-close-btn:hover {
        background: #e5e7eb;
    }

    .ios-segmented-control {
        display: flex;
        background: #f3f4f6;
        /* Solid light gray */
        border-radius: 10px;
        padding: 3px;
    }

    .ios-segment {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all 0.2s ease;
    }

    .ios-segment.active {
        background: #ffffff;
        color: #111827;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        font-weight: 600;
    }

    .social-edit-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .social-edit-btn:hover {
        background: rgba(120, 120, 128, 0.1);
    }
</style>

<div class="card no-hover" style="background: transparent; border: none; box-shadow: none; padding: 0;">
    <div class="market-feed-header"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="category-tags">
            <a href="index.php?page=marketplace&cat=All"
                class="category-tag <?= $cat_filter == 'All' ? 'active' : '' ?>">All Surplus</a>
            <a href="index.php?page=marketplace&cat=Meat"
                class="category-tag <?= $cat_filter == 'Meat' ? 'active' : '' ?>">Meats</a>
            <a href="index.php?page=marketplace&cat=Vegetables"
                class="category-tag <?= $cat_filter == 'Vegetables' ? 'active' : '' ?>">Vegetables</a>
            <a href="index.php?page=marketplace&cat=Fruits"
                class="category-tag <?= $cat_filter == 'Fruits' ? 'active' : '' ?>">Fruits</a>
            <a href="index.php?page=marketplace&cat=Dairy"
                class="category-tag <?= $cat_filter == 'Dairy' ? 'active' : '' ?>">Dairy</a>
            <a href="index.php?page=marketplace&cat=Free"
                class="category-tag <?= $cat_filter == 'Free' ? 'active' : '' ?>">Free/Donation</a>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button onclick="document.getElementById('history-modal').style.display='flex'" class="btn btn-secondary"
                style="padding: 0.5rem 1rem; border-radius: 8px;">
                <i data-lucide="history"></i> View History
            </button>
            <button onclick="document.getElementById('post-modal').style.display='flex'" class="btn btn-primary"
                style="padding: 0.5rem 1rem; border-radius: 8px;">
                <i data-lucide="plus-circle"></i> Post to Market
            </button>
        </div>
    </div>
</div>

<div class="market-grid" id="market-grid">
    <?php if (empty($market_items)): ?>
        <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 4rem;">
            No food listings available in this category.
        </div>
    <?php else: ?>
        <?php foreach ($market_items as $item):
            // Bug Fix: Check against dynamic active user, not hardcoded string
            $is_own = ($item['seller'] == $active_user);

            $today = new DateTime();
            $expiry = new DateTime($item['expiry_date']);
            $diff = $today->diff($expiry);
            $days_left = (int) $diff->format('%r%a');

            $countdown_class = ($days_left <= 1) ? "" : "safe";
            $countdown_text = ($days_left == 0) ? "Expires Today" : (($days_left < 0) ? "Expired" : "$days_left days left");

            $price_text = (floatval($item['market_price']) == 0) ? "FREE" : "₱" . number_format($item['market_price'], 2);

            $cat_fallback = isset($category_images[$item['category']]) ? $category_images[$item['category']] : $category_images['Other'];
            $item_image = !empty($item['image_url']) ? $item['image_url'] : $cat_fallback;

            $category_colors = [
                "Meat" => "var(--color-meat)",
                "Vegetables" => "var(--color-veg)",
                "Fruits" => "var(--color-fruit)",
                "Dairy" => "var(--color-dairy)",
                "Bakery" => "var(--color-bakery)",
                "Other" => "var(--color-other)"
            ];
            $cat_color = isset($category_colors[$item['category']]) ? $category_colors[$item['category']] : "var(--color-other)";
            ?>
            <div class="market-card">
                <div class="market-img-container"
                    style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url('<?= htmlspecialchars($item_image) ?>');">
                    <span class="market-card-badge"
                        style="color: <?= $cat_color ?>; border-color: <?= $cat_color ?>;"><?= $item['category'] ?></span>
                    <span class="market-expiry-counter <?= $countdown_class ?>"><?= $countdown_text ?></span>
                    <span class="market-price-tag"><?= $price_text ?></span>
                </div>

                <div class="market-body">
                    <!-- Three dots next to title for owners -->
                    <div class="market-title-row"
                        style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h3 class="market-item-title" style="margin: 0; padding-right: 0.5rem;">
                            <?= htmlspecialchars($item['name']) ?></h3>
                        <?php if ($is_own): ?>
                            <button
                                onclick='openEditModal(<?= htmlspecialchars(json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)'
                                class="social-edit-btn" title="Edit Post">
                                <i data-lucide="more-horizontal"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="market-seller-row" style="margin-top: 0.5rem;">
                        <div class="seller-avatar"><?= strtoupper(substr($item['seller'], 0, 2)) ?></div>
                        <span>
                            <?= htmlspecialchars($item['seller']) ?>
                            <span
                                style="font-size:0.65rem; color:var(--text-muted);">(<?= htmlspecialchars($item['seller_type']) ?>)</span>
                        </span>
                    </div>
                    <p class="market-description">
                        <?= htmlspecialchars($item['market_desc'] ? $item['market_desc'] : "No description provided.") ?></p>

                    <div class="market-meta-grid">
                        <div class="market-meta-item">
                            <i data-lucide="package"></i><span>Qty: <?= htmlspecialchars($item['qty']) ?></span>
                        </div>
                        <div class="market-meta-item">
                            <i data-lucide="map-pin"></i><span><?= htmlspecialchars($item['location']) ?></span>
                        </div>
                    </div>

                    <?php if (!$is_own): ?>
                        <div class="market-footer">
                            <button type="button" class="btn btn-primary" style="width: 100%;"
                                onclick='openClaimModal(<?= htmlspecialchars(json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)'>
                                <i data-lucide="gift"></i> Claim Surplus
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- HISTORY MODAL -->
<div id="history-modal" class="apple-modal-bg"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="apple-modal-card"
        style="width: 100%; max-width: 600px; padding: 2rem; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.4rem;">Transaction History</h3>
            <button class="apple-close-btn" onclick="document.getElementById('history-modal').style.display='none'">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <?php if (empty($history_items)): ?>
            <p style="text-align: center; color: #6b7280; padding: 2rem 0;">No transaction history found.</p>
        <?php else: ?>
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.75rem 0; font-size: 0.85rem; color: #6b7280;">Item (Qty)</th>
                        <th style="font-size: 0.85rem; color: #6b7280;">Type</th>
                        <th style="font-size: 0.85rem; color: #6b7280;">Claimed By</th>
                        <th style="font-size: 0.85rem; color: #6b7280;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_items as $h_item):
                        $is_donation = (floatval($h_item['market_price']) == 0);
                        $type_label = $is_donation ? "Donation" : "â‚±" . number_format($h_item['market_price'], 2);
                        $color = $is_donation ? "#059669" : "#007AFF"; // Green/Blue for white background
                        ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 1rem 0;">
                                <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($h_item['name']) ?></div>
                                <small style="color: #6b7280;"><?= htmlspecialchars($h_item['qty']) ?></small>
                            </td>
                            <td style="color: <?= $color ?>; font-weight: 600;"><?= $type_label ?></td>
                            <td style="color: #374151;"><?= htmlspecialchars($h_item['buyer_name']) ?></td>
                            <td style="color: #6b7280; font-size: 0.9rem;">
                                <?= date("M j, Y", strtotime($h_item['claimed_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- POST TO MARKET MODAL (APPLE UX - WHITE) -->
<div id="post-modal" class="apple-modal-bg"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="apple-modal-card"
        style="width: 100%; max-width: 500px; padding: 2rem; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.4rem;">New Post</h3>
            <button class="apple-close-btn" onclick="document.getElementById('post-modal').style.display='none'">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <!-- Segmented control removed: only inventory posting is available -->

        <form action="index.php?page=marketplace" method="POST"
            style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="action" value="post_to_market">
            <input type="hidden" name="post_type" id="post_type" value="inventory">

            <div id="section-inventory" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label class="apple-label">Item from Inventory</label>
                <select name="inventory_id" id="inventory_select" class="apple-input">
                    <option value="" disabled selected>Select an item...</option>
                    <?php foreach ($unlisted_items as $u_item): ?>
                        <option value="<?= $u_item['id'] ?>"><?= htmlspecialchars($u_item['name']) ?>
                            (<?= htmlspecialchars($u_item['qty']) ?>) - Exp: <?= $u_item['expiry_date'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Manual posting section removed: users must select from existing inventory -->

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 0.5rem 0;">

            <div style="display: flex; gap: 1rem;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                    <label class="apple-label">Price</label>
                    <input type="number" step="0.01" min="0" name="market_price" value="0.00" required
                        class="apple-input">
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label class="apple-label">Description & Pickup Notes</label>
                <textarea name="market_desc" rows="3" required class="apple-input" style="resize: vertical;"
                    placeholder="Add details..."></textarea>
            </div>

            <button type="submit" class="apple-btn-primary" style="margin-top: 0.5rem;">Publish Post</button>
        </form>
    </div>
</div>

<!-- EDIT/DELETE MARKET POST MODAL (APPLE UX - WHITE) -->
<div id="edit-modal" class="apple-modal-bg"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="apple-modal-card" style="width: 100%; max-width: 400px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3 style="margin: 0; font-size: 1.4rem;">Edit Post</h3>
            <button class="apple-close-btn" onclick="document.getElementById('edit-modal').style.display='none'">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <form action="index.php?page=marketplace" method="POST"
            style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="action" value="edit_market_post">
            <input type="hidden" name="edit_id" id="edit_id">

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label class="apple-label">Price</label>
                <input type="number" step="0.01" min="0" name="edit_price" id="edit_price" required class="apple-input">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label class="apple-label">Description</label>
                <textarea name="edit_desc" id="edit_desc" rows="4" required class="apple-input"
                    style="resize: vertical;"></textarea>
            </div>

            <button type="submit" class="apple-btn-primary" style="margin-top: 1rem;">Save Changes</button>
        </form>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1.5rem 0;">

        <form action="index.php?page=marketplace" method="POST"
            onsubmit="return confirm('Delete this post and its inventory entirely?');">
            <input type="hidden" name="action" value="delete_market_post">
            <input type="hidden" name="delete_id" id="delete_id">
            <button type="submit" class="apple-btn-danger">
                Delete Post
            </button>
        </form>
    </div>
</div>

<!-- CLAIM SURPLUS CONFIRMATION MODAL -->
<div id="claim-modal" class="apple-modal-bg"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="apple-modal-card" style="width: 100%; max-width: 400px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.4rem;">Confirm Claim</h3>
            <button class="apple-close-btn" onclick="document.getElementById('claim-modal').style.display='none'">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div
            style="background: #fbfbf9; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; text-align: center;">
            <i data-lucide="info" style="color: var(--primary); width: 32px; height: 32px; margin-bottom: 0.5rem;"></i>
            <h4 id="claim_item_name" style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">Item Name</h4>
            <p id="claim_seller_name" style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">From: Seller
                Name</p>
            <p id="claim_location" style="margin: 0.2rem 0 1rem 0; color: var(--text-muted); font-size: 0.85rem;"><i
                    data-lucide="map-pin"
                    style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i> Location</p>

            <div
                style="border-radius: 12px; overflow: hidden; height: 130px; width: 100%; position: relative; background: #e5e7eb; border: 1px solid var(--border-color);">
                <iframe id="claim_map_iframe" width="100%" height="100%" frameborder="0" style="border:0;" src=""
                    allowfullscreen loading="lazy"></iframe>
                <!-- Invisible overlay to prevent accidentally scrolling the map instead of the page if needed -->
                <div
                    style="position: absolute; top:0; left:0; width:100%; height:100%; z-index: 10; pointer-events: none;">
                </div>
            </div>
        </div>

        <form action="index.php?page=marketplace" method="POST"
            style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" name="action" value="claim">
            <input type="hidden" name="id" id="claim_id">
            <input type="hidden" name="seller_name" id="claim_seller_input">

            <button type="submit" class="apple-btn-primary"
                style="display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                <i data-lucide="message-square"></i> Confirm & Message Seller
            </button>
            <button type="button" class="btn btn-secondary" style="width: 100%; padding: 14px; border-radius: 14px;"
                onclick="document.getElementById('claim-modal').style.display='none'">
                Cancel
            </button>
        </form>
    </div>
</div>

<script>
    function switchPostTab(tab) {
        const isManual = (tab === 'manual');
        document.getElementById('post_type').value = tab;

        // Toggle Sections
        document.getElementById('section-inventory').style.display = isManual ? 'none' : 'flex';
        document.getElementById('section-manual').style.display = isManual ? 'flex' : 'none';

        // Toggle Segmented Control Classes
        document.getElementById('btn-tab-manual').className = isManual ? 'ios-segment active' : 'ios-segment';
        document.getElementById('btn-tab-inventory').className = isManual ? 'ios-segment' : 'ios-segment active';

        // Toggle Requires
        document.getElementById('inventory_select').required = !isManual;
        document.getElementById('manual_name').required = isManual;
        document.getElementById('manual_qty').required = isManual;
        document.getElementById('manual_expiry').required = isManual;
    }

    function openEditModal(itemData) {
        document.getElementById('edit_id').value = itemData.id;
        document.getElementById('delete_id').value = itemData.id;
        document.getElementById('edit_price').value = itemData.market_price;
        document.getElementById('edit_desc').value = itemData.market_desc;

        document.getElementById('edit-modal').style.display = 'flex';
    }

    function openClaimModal(itemData) {
        document.getElementById('claim_id').value = itemData.id;
        document.getElementById('claim_seller_input').value = itemData.seller;

        document.getElementById('claim_item_name').innerText = itemData.name;
        document.getElementById('claim_seller_name').innerText = "From: " + itemData.seller;
        document.getElementById('claim_location').innerHTML = '<i data-lucide="map-pin" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i> ' + itemData.location;

        // Update Map Iframe dynamically
        let mapQuery = encodeURIComponent(itemData.location + " City");
        document.getElementById('claim_map_iframe').src = "https://maps.google.com/maps?q=" + mapQuery + "&t=&z=14&ie=UTF8&iwloc=&output=embed";

        document.getElementById('claim-modal').style.display = 'flex';
    }
</script>