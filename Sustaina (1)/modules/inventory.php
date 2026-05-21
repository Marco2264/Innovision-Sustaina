<?php
/**
 * Food Saver - Raw Food Inventory View Module (Redesigned Minimal)
 * With Card/Table View Toggle
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

// Get category filter from URL
$cat_filter = isset($_GET['cat']) ? $_GET['cat'] : 'All';

// Get view mode from URL (card or table)
$view_mode = isset($_GET['view']) && in_array($_GET['view'], ['card', 'table']) ? $_GET['view'] : 'table';

// Determine the active user
$active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';

// Construct Query
if ($cat_filter == 'All') {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE seller = ? ORDER BY expiry_date ASC");
    $stmt->execute([$active_user]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE seller = ? AND category = ? ORDER BY expiry_date ASC");
    $stmt->execute([$active_user, $cat_filter]);
}
$my_items = $stmt->fetchAll();
?>

<style>
    /* Minimal Inventory Styles */
    .inventory-container {
        width: 100%;
    }
    
    /* Filter Bar */
    .filter-bar-min {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border, #e9ecef);
    }
    
    .category-tags-min {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .category-tag-min {
        padding: 0.4rem 1rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border, #e2e6ea);
        color: var(--text-secondary, #5a6e7c);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .category-tag-min:hover {
        border-color: #cbd3db;
        background: #f8f9fa;
    }
    
    .category-tag-min.active {
        background: #1a1f2e;
        border-color: #1a1f2e;
        color: white;
    }
    
    /* View Toggle */
    .view-toggle-min {
        display: flex;
        gap: 0.25rem;
        background: var(--border-light, #f0f2f4);
        border-radius: 0.5rem;
        padding: 0.25rem;
    }
    
    .view-btn-min {
        padding: 0.4rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: transparent;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        color: var(--text-secondary, #5a6e7c);
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .view-btn-min.active {
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #1a1f2e);
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    .view-btn-min:hover:not(.active) {
        background: rgba(0,0,0,0.04);
    }
    
    /* Card Grid Layout */
    .card-grid-min {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
        margin-top: 1.25rem;
    }
    
    .inventory-card-min {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border, #e9ecef);
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    
    .inventory-card-min:hover {
        border-color: #d4d8dd;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    
    .card-media-min {
        height: 140px;
        background: #f5f7f9;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
    }
    
    .card-media-min img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s ease;
    }
    
    .card-media-min img:hover {
        transform: scale(1.02);
    }
    
    .card-media-placeholder-min {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #9ca3af;
        font-size: 0.75rem;
    }
    
    .card-body-min {
        padding: 1rem;
    }
    
    .card-title-min {
        font-weight: 600;
        font-size: 1rem;
        margin: 0 0 0.25rem 0;
        color: var(--text-primary, #1a1f2e);
    }
    
    .card-category-min {
        display: inline-block;
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        margin-bottom: 0.75rem;
    }
    
    .card-details-min {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
    }
    
    .card-details-label-min {
        color: var(--text-muted, #8c9aa8);
    }
    
    .card-details-value-min {
        font-weight: 500;
        color: var(--text-primary, #1a1f2e);
    }
    
    .card-status-min {
        margin: 0.75rem 0;
        padding: 0.5rem 0;
        border-top: 1px solid var(--border-light, #f0f2f4);
        border-bottom: 1px solid var(--border-light, #f0f2f4);
    }
    
    .card-actions-min {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        justify-content: flex-end;
    }
    
    .icon-btn-min {
        background: none;
        border: 1px solid var(--border, #e2e6ea);
        border-radius: 0.375rem;
        padding: 0.35rem 0.65rem;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--text-secondary, #5a6e7c);
    }
    
    .icon-btn-min:hover {
        background: #f8f9fa;
        border-color: #cbd3db;
    }
    
    .icon-btn-danger-min:hover {
        color: #c73a2b;
        border-color: #f5c6cb;
        background: #fee8e6;
    }
    
    .icon-btn-success-min:hover {
        color: #2b8c4e;
        border-color: #c3e6cb;
        background: #e6f4ea;
    }
    
    /* Badge styles */
    .badge-status-min {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    .badge-fresh-min {
        background: #e6f4ea;
        color: #2b8c4e;
    }
    
    .badge-warning-min {
        background: #fff3e0;
        color: #cc7b00;
    }
    
    .badge-danger-min {
        background: #fee8e6;
        color: #c73a2b;
    }
    
    .market-badge-min {
        font-size: 0.7rem;
        color: #3a6ea5;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    /* Table styles (existing minimal) */
    .table-min {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }
    
    .table-min th {
        text-align: left;
        padding: 0.75rem 0.5rem 0.5rem 0;
        font-weight: 500;
        color: var(--text-secondary, #5a6e7c);
        border-bottom: 1px solid var(--border, #e9ecef);
    }
    
    .table-min td {
        padding: 0.75rem 0.5rem 0.75rem 0;
        border-bottom: 1px solid var(--border-light, #f0f2f4);
        vertical-align: middle;
    }
    
    .table-min tr:last-child td {
        border-bottom: none;
    }
    
    .food-name-min {
        font-weight: 500;
        color: var(--text-primary, #1a1f2e);
        display: block;
    }
    
    .food-subtext-min {
        font-size: 0.7rem;
        color: var(--text-muted, #8c9aa8);
    }
    
    .action-group-min {
        display: flex;
        gap: 0.4rem;
        justify-content: flex-end;
    }
    
    .empty-state-min {
        text-align: center;
        padding: 3rem;
        color: var(--text-muted, #8c9aa8);
        font-size: 0.8125rem;
        background: var(--card-bg, #ffffff);
        border-radius: 1rem;
        border: 1px solid var(--border, #e9ecef);
    }
</style>

<div class="inventory-container">
    <div class="card-min no-hover" style="background: transparent; border: none; padding: 0;">
        
        <!-- Filter Bar + View Toggle -->
        <div class="filter-bar-min">
            <div class="category-tags-min">
                <a href="index.php?page=inventory&cat=All&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'All' ? 'active' : '' ?>">All</a>
                <a href="index.php?page=inventory&cat=Meat&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Meat' ? 'active' : '' ?>">Meat</a>
                <a href="index.php?page=inventory&cat=Vegetables&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Vegetables' ? 'active' : '' ?>">Vegetables</a>
                <a href="index.php?page=inventory&cat=Fruits&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Fruits' ? 'active' : '' ?>">Fruits</a>
                <a href="index.php?page=inventory&cat=Dairy&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Dairy' ? 'active' : '' ?>">Dairy</a>
                <a href="index.php?page=inventory&cat=Bakery&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Bakery' ? 'active' : '' ?>">Bakery</a>
                <a href="index.php?page=inventory&cat=Other&view=<?= $view_mode ?>" class="category-tag-min <?= $cat_filter == 'Other' ? 'active' : '' ?>">Other</a>
            </div>
            
            <div class="view-toggle-min">
                <a href="index.php?page=inventory&cat=<?= $cat_filter ?>&view=table" class="view-btn-min <?= $view_mode == 'table' ? 'active' : '' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3zM3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
                    Table
                </a>
                <a href="index.php?page=inventory&cat=<?= $cat_filter ?>&view=card" class="view-btn-min <?= $view_mode == 'card' ? 'active' : '' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    Cards
                </a>
            </div>
        </div>

        <!-- Content Area: Table or Cards -->
        <?php if (empty($my_items)): ?>
            <div class="empty-state-min">
                No items found in this category.
            </div>
        <?php else: ?>
            
            <?php if ($view_mode == 'table'): ?>
                <!-- TABLE VIEW -->
                <div class="table-container-min" style="overflow-x: auto;">
                    <table class="table-min">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Bought</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_items as $item): 
                                $today = new DateTime('2026-05-20');
                                $expiry = new DateTime($item['expiry_date']);
                                $diff = $today->diff($expiry);
                                $days_left = $diff->days;
                                if ($expiry < $today) $days_left = -$days_left;
                                
                                $badge_class = "badge-fresh-min";
                                $days_text = "$days_left days left";
                                if ($days_left <= 1) {
                                    $badge_class = "badge-danger-min";
                                    $days_text = ($days_left == 0) ? "Expires today" : (($days_left < 0) ? "Expired" : "Expires tomorrow");
                                } elseif ($days_left <= 3) {
                                    $badge_class = "badge-warning-min";
                                }
                                
                                $cat_color_map = [
                                    "Meat" => "#b83b2e", "Vegetables" => "#2b7a3e", "Fruits" => "#d4770e",
                                    "Dairy" => "#4a6fa5", "Bakery" => "#b87333", "Other" => "#6c757d"
                                ];
                                $cat_color = $cat_color_map[$item['category']] ?? "#6c757d";
                            ?>
                                <tr>
                                    <td>
                                        <span class="food-name-min"><?= htmlspecialchars($item['name']) ?></span>
                                        <?php if ($item['listed_on_market']): ?>
                                            <span class="food-subtext-min">Listed · $<?= number_format($item['market_price'], 2) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-status-min" style="background: <?= $cat_color ?>15; color: <?= $cat_color ?>;">
                                            <?= $item['category'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($item['qty']) ?></td>
                                    <td>
                                        <?php if ($item['bought_date'] && $item['bought_date'] != '0000-00-00'): ?>
                                            <?= date('M d, Y', strtotime($item['bought_date'])) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($item['expiry_date'])) ?></td>
                                    <td><span class="badge-status-min <?= $badge_class ?>"><?= $days_text ?></span></td>
                                    <td style="text-align: right;">
                                        <div class="action-group-min">
                                            <button type="button" class="icon-btn-min" onclick="openImageModal('<?= htmlspecialchars($item['image_url'] ?? '') ?>')" title="View image">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-5 5"/></svg>
                                            </button>
                                            
                                            <?php if ($item['listed_on_market']): ?>
                                                <form action="actions.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delist">
                                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="icon-btn-min" title="Remove from market">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="icon-btn-min icon-btn-success-min" onclick="quickMarketListPrompt('<?= $item['id'] ?>')" title="List on marketplace">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <form action="actions.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="icon-btn-min icon-btn-danger-min" title="Delete">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M8 6V4h8v2"/><rect x="10" y="10" width="4" height="8"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php else: ?>
                <!-- CARD VIEW -->
                <div class="card-grid-min">
                    <?php foreach ($my_items as $item):
                        $today = new DateTime('2026-05-20');
                        $expiry = new DateTime($item['expiry_date']);
                        $diff = $today->diff($expiry);
                        $days_left = $diff->days;
                        if ($expiry < $today) $days_left = -$days_left;
                        
                        $badge_class = "badge-fresh-min";
                        $days_text = "$days_left days left";
                        if ($days_left <= 1) {
                            $badge_class = "badge-danger-min";
                            $days_text = ($days_left == 0) ? "Expires today" : (($days_left < 0) ? "Expired" : "Expires tomorrow");
                        } elseif ($days_left <= 3) {
                            $badge_class = "badge-warning-min";
                        }
                        
                        $cat_color_map = [
                            "Meat" => "#b83b2e", "Vegetables" => "#2b7a3e", "Fruits" => "#d4770e",
                            "Dairy" => "#4a6fa5", "Bakery" => "#b87333", "Other" => "#6c757d"
                        ];
                        $cat_color = $cat_color_map[$item['category']] ?? "#6c757d";
                        $has_image = !empty($item['image_url']);
                    ?>
                        <div class="inventory-card-min">
                            <div class="card-media-min" onclick="openImageModal('<?= htmlspecialchars($item['image_url'] ?? '') ?>')">
                                <?php if ($has_image): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php else: ?>
                                    <div class="card-media-placeholder-min">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="2.18"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-5 5"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body-min">
                                <h3 class="card-title-min"><?= htmlspecialchars($item['name']) ?></h3>
                                <span class="card-category-min" style="background: <?= $cat_color ?>15; color: <?= $cat_color ?>;">
                                    <?= $item['category'] ?>
                                </span>
                                
                                <div class="card-details-min">
                                    <span class="card-details-label-min">Quantity</span>
                                    <span class="card-details-value-min"><?= htmlspecialchars($item['qty']) ?></span>
                                </div>
                                <div class="card-details-min">
                                    <span class="card-details-label-min">Expiry</span>
                                    <span class="card-details-value-min"><?= date('M d, Y', strtotime($item['expiry_date'])) ?></span>
                                </div>
                                
                                <div class="card-status-min">
                                    <span class="badge-status-min <?= $badge_class ?>"><?= $days_text ?></span>
                                    <?php if ($item['listed_on_market']): ?>
                                        <span class="market-badge-min" style="margin-left: 0.5rem;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4 4h16v16H4z"/></svg>
                                            $<?= number_format($item['market_price'], 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-actions-min">
                                    <button type="button" class="icon-btn-min" onclick="openImageModal('<?= htmlspecialchars($item['image_url'] ?? '') ?>')" title="View image">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-5 5"/></svg>
                                    </button>
                                    
                                    <?php if ($item['listed_on_market']): ?>
                                        <form action="actions.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delist">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="icon-btn-min" title="Remove from market">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="icon-btn-min icon-btn-success-min" onclick="quickMarketListPrompt('<?= $item['id'] ?>')" title="List on marketplace">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <form action="actions.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="icon-btn-min icon-btn-danger-min" title="Delete">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M8 6V4h8v2"/><rect x="10" y="10" width="4" height="8"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1050; justify-content: center; align-items: center; padding: 1rem;">
    <div style="position: relative; max-width: 900px; width: 100%;">
        <span onclick="closeImageModal()" style="position: absolute; top: -35px; right: 0; color: white; font-size: 28px; cursor: pointer; font-weight: bold; line-height: 1;">&times;</span>
        <img id="modalImage" src="" alt="Raw Food Image" style="width: 100%; max-height: 85vh; object-fit: contain; border-radius: var(--radius-md); box-shadow: 0 10px 30px rgba(0,0,0,0.3);" />
    </div>
</div>

<script>
function openImageModal(imgUrl) {
    const imgEl = document.getElementById('modalImage');
    if (!imgUrl) {
        imgEl.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="%239ca3af">No Image Provided</text></svg>';
    } else {
        imgEl.src = imgUrl;
    }
    document.getElementById('imageModal').style.display = 'flex';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.getElementById('modalImage').src = '';
}

// Close on clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});
</script>
