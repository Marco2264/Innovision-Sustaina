<?php
/**
 * Sustaina - Homepage View Module (Redesigned)
 * No analytics, clean dashboard with module shortcuts and inventory overview
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

// Determine the active user
$active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';

// Fetch user's inventory
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE seller = ? ORDER BY expiry_date ASC");
$stmt->execute([$active_user]);
$my_items = $stmt->fetchAll();

// Calculations for stats
$co2_factors = [
    "Meat" => 27.0,
    "Vegetables" => 2.0,
    "Fruits" => 2.5,
    "Dairy" => 8.0,
    "Bakery" => 3.0,
    "Other" => 4.5
];

function parse_qty_to_kg_php($qty_str)
{
    $val = parseFloat_php($qty_str);
    $qty_lower = strtolower($qty_str);
    if (strpos($qty_lower, 'kg') !== false || strpos($qty_lower, 'kilogram') !== false) {
        return $val;
    }
    if (strpos($qty_lower, 'liter') !== false || strpos($qty_lower, 'l') !== false) {
        return $val;
    }
    return $val * 0.15;
}

function parseFloat_php($str)
{
    preg_match('/[+-]?([0-9]*[.])?[0-9]+/', $str, $matches);
    return isset($matches[0]) ? floatval($matches[0]) : 1.0;
}

$today = new DateTime('2026-05-20');
$expiring_count = 0;
$active_listings = 0;
$total_co2_saved = 80.0;
$total_items = count($my_items);
$total_weight = 0;
$expiring_items = [];

foreach ($my_items as $item) {
    $expiry = new DateTime($item['expiry_date']);
    $diff = $today->diff($expiry);
    $days_left = $diff->days;
    if ($expiry < $today) {
        $days_left = -$days_left;
    }

    if ($days_left >= 0 && $days_left <= 3) {
        $expiring_count++;
        $expiring_items[] = [
            'item' => $item,
            'days_left' => $days_left
        ];
    }

    if ($item['listed_on_market']) {
        $active_listings++;
    }

    $weight = parse_qty_to_kg_php($item['qty']);
    $total_weight += $weight;
    $factor = isset($co2_factors[$item['category']]) ? $co2_factors[$item['category']] : 4.5;
    $total_co2_saved += ($weight * $factor);
}

// Get recent marketplace activity
$stmt_market = $pdo->prepare("SELECT * FROM inventory WHERE listed_on_market = 1 AND seller = ? ORDER BY expiry_date ASC LIMIT 4");
$stmt_market->execute([$active_user]);
$recent_listings = $stmt_market->fetchAll();

// Get recent claims (using buyer_id joined with users table)
$recent_claims = [];
try {
    $stmt_claims = $pdo->prepare("
        SELECT i.name, i.qty, u.full_name AS buyer_name, c.claimed_at 
        FROM claims c 
        JOIN inventory i ON c.inventory_id = i.id 
        JOIN users u ON c.buyer_id = u.id
        WHERE i.seller = ? 
        ORDER BY c.claimed_at DESC 
        LIMIT 4
    ");
    $stmt_claims->execute([$active_user]);
    $recent_claims = $stmt_claims->fetchAll();
} catch (PDOException $e) {
    // Claims table may not exist yet or have different structure
    $recent_claims = [];
}
?>

<style>
    /* Homepage Specific Styles */
    .welcome-section {
        margin-bottom: 2rem;
    }
    
    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary, #1a2a22);
        margin-bottom: 0.25rem;
    }
    
    .welcome-subtitle {
        font-size: 0.85rem;
        color: var(--text-muted, #5a6e64);
    }
    
    /* Stats Row - Compact */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-block {
        background: var(--surface, #ffffff);
        border-radius: 16px;
        padding: 1rem;
        border: 1px solid var(--border, #e2e8e4);
    }
    
    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary, #2c5a3e);
        line-height: 1.2;
    }
    
    .stat-label-stat {
        font-size: 0.7rem;
        color: var(--text-muted, #5a6e64);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.25rem;
    }
    
    /* Module Cards Grid */
    .modules-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    
    .module-card {
        background: var(--surface, #ffffff);
        border-radius: 20px;
        padding: 1.25rem;
        border: 1px solid var(--border, #e2e8e4);
        text-decoration: none;
        transition: all 0.2s ease;
        display: block;
    }
    
    .module-card:hover {
        border-color: var(--primary, #2c5a3e);
        transform: translateY(-2px);
    }
    
    .module-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-light, #e8f0ec);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: var(--primary, #2c5a3e);
    }
    
    .module-icon svg {
        width: 24px;
        height: 24px;
        stroke-width: 1.7;
    }
    
    .module-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary, #1a2a22);
        margin-bottom: 0.25rem;
    }
    
    .module-desc {
        font-size: 0.75rem;
        color: var(--text-muted, #5a6e64);
        line-height: 1.4;
    }
    
    .module-badge {
        display: inline-block;
        margin-top: 0.75rem;
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--primary, #2c5a3e);
    }
    
    /* Two Column Layout */
    .two-col-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .info-card {
        background: var(--surface, #ffffff);
        border-radius: 20px;
        border: 1px solid var(--border, #e2e8e4);
        overflow: hidden;
    }
    
    .info-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border, #e2e8e4);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .info-card-header h3 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-primary, #1a2a22);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-card-header svg {
        width: 16px;
        height: 16px;
        color: var(--primary, #2c5a3e);
    }
    
    .info-card-body {
        padding: 0.75rem 1.25rem;
    }
    
    .expiring-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-light, #f0f2f0);
    }
    
    .expiring-item:last-child {
        border-bottom: none;
    }
    
    .expiring-name {
        font-weight: 500;
        font-size: 0.85rem;
        color: var(--text-primary, #1a2a22);
    }
    
    .expiring-qty {
        font-size: 0.7rem;
        color: var(--text-muted, #5a6e64);
    }
    
    .expiring-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        background: #fee8e6;
        color: #c73a2b;
    }
    
    .expiring-badge.warning {
        background: #fff3e0;
        color: #cc7b00;
    }
    
    .listing-item, .claim-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.7rem 0;
        border-bottom: 1px solid var(--border-light, #f0f2f0);
    }
    
    .listing-item:last-child, .claim-item:last-child {
        border-bottom: none;
    }
    
    .listing-name, .claim-name {
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .listing-price {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary, #2c5a3e);
    }
    
    .claim-buyer {
        font-size: 0.7rem;
        color: var(--text-muted, #5a6e64);
    }
    
    .claim-date {
        font-size: 0.7rem;
        color: var(--text-muted, #5a6e64);
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted, #5a6e64);
        font-size: 0.8rem;
    }
    
    .view-all-link {
        font-size: 0.7rem;
        color: var(--primary, #2c5a3e);
        text-decoration: none;
    }
    
    .view-all-link:hover {
        text-decoration: underline;
    }
    
    /* Quick Action Buttons */
    .quick-actions {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
    }
    
    .quick-action-btn {
        background: var(--surface, #ffffff);
        border: 1px solid var(--border, #e2e8e4);
        border-radius: 40px;
        padding: 0.6rem 1.2rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-primary, #1a2a22);
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .quick-action-btn:hover {
        border-color: var(--primary, #2c5a3e);
        background: var(--primary-light, #e8f0ec);
    }
    
    .quick-action-btn svg {
        width: 16px;
        height: 16px;
        color: var(--primary, #2c5a3e);
    }
    
    @media (max-width: 1024px) {
        .stats-row, .modules-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .two-col-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 640px) {
        .stats-row, .modules-grid {
            grid-template-columns: 1fr;
        }
        .quick-actions {
            flex-wrap: wrap;
        }
    }
</style>

<!-- Welcome Section -->
<div class="welcome-section">
    <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($active_user) ?></h1>
    <p class="welcome-subtitle">Track your inventory, reduce waste, and connect with your local community.</p>
</div>


<!-- Quick Actions -->
<div class="quick-actions">
    <button class="quick-action-btn" onclick="document.getElementById('open-add-item-modal')?.click() || window.location.href='index.php?page=inventory'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Item
    </button>
    <button class="quick-action-btn" onclick="window.location.href='index.php?page=marketplace'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9h18M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"/></svg>
        Browse Marketplace
    </button>
    <button class="quick-action-btn" onclick="window.location.href='index.php?page=ai-assistant'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/></svg>
        Ask AI Chef
    </button>
</div>

<!-- Module Cards (Sneak Peek) -->
<div class="modules-grid">
    <a href="index.php?page=inventory" class="module-card">
        <div class="module-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
        </div>
        <h3 class="module-title">Inventory</h3>
        <p class="module-desc">Manage your raw ingredients, track expiry dates, and organize stock.</p>
        <span class="module-badge"><?= $total_items ?> items →</span>
    </a>
    
    <a href="index.php?page=marketplace" class="module-card">
        <div class="module-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M3 9h18M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
            </svg>
        </div>
        <h3 class="module-title">Marketplace</h3>
        <p class="module-desc">List surplus items, set prices, and connect with local buyers.</p>
        <span class="module-badge"><?= $active_listings ?> active →</span>
    </a>
    
    <a href="index.php?page=ai-assistant" class="module-card">
        <div class="module-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 2a10 10 0 1 0 10 10"/>
                <path d="M12 6v6l4 2"/>
                <circle cx="12" cy="12" r="10"/>
            </svg>
        </div>
        <h3 class="module-title">AI Assistant</h3>
        <p class="module-desc">Get recipe suggestions, preservation tips, and inventory insights.</p>
        <span class="module-badge">Try now →</span>
    </a>
    
    <a href="#" class="module-card" onclick="return false;">
        <div class="module-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4l3 3"/>
            </svg>
        </div>
        <h3 class="module-title">Analytics</h3>
        <p class="module-desc">View your impact, savings, and waste reduction metrics.</p>
        <span class="module-badge">Coming soon →</span>
    </a>
</div>

<!-- Two Column: Expiring Items + Recent Listings -->
<div class="two-col-grid">
    <!-- Expiring Items Card -->
    <div class="info-card">
        <div class="info-card-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Expiring Soon
            </h3>
            <a href="index.php?page=inventory" class="view-all-link">View all</a>
        </div>
        <div class="info-card-body">
            <?php if (empty($expiring_items)): ?>
                <div class="empty-state">No items expiring soon. Great job!</div>
            <?php else: ?>
                <?php foreach (array_slice($expiring_items, 0, 5) as $expire):
                    $item = $expire['item'];
                    $days = $expire['days_left'];
                    $badge_class = ($days <= 1) ? "expiring-badge" : "expiring-badge warning";
                    $badge_text = ($days == 0) ? "Today" : (($days < 0) ? "Expired" : "$days days");
                ?>
                    <div class="expiring-item">
                        <div>
                            <div class="expiring-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="expiring-qty"><?= htmlspecialchars($item['qty']) ?></div>
                        </div>
                        <span class="<?= $badge_class ?>"><?= $badge_text ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Listings Card -->
    <div class="info-card">
        <div class="info-card-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9h18M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"/>
                </svg>
                Your Active Listings
            </h3>
            <a href="index.php?page=marketplace" class="view-all-link">View all</a>
        </div>
        <div class="info-card-body">
            <?php if (empty($recent_listings)): ?>
                <div class="empty-state">No active listings. Post an item to get started!</div>
            <?php else: ?>
                <?php foreach ($recent_listings as $listing): ?>
                    <div class="listing-item">
                        <div>
                            <div class="listing-name"><?= htmlspecialchars($listing['name']) ?></div>
                            <div class="claim-buyer"><?= htmlspecialchars($listing['qty']) ?></div>
                        </div>
                        <div class="listing-price">
                            <?= (floatval($listing['market_price']) == 0) ? "Free" : "₱" . number_format($listing['market_price'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Claims/Donations Card -->
<div class="info-card" style="margin-top: 0;">
    <div class="info-card-header">
        <h3>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 12V8H4v8h16v-4"/>
                <circle cx="12" cy="12" r="2"/>
                <path d="M4 8V6h16v2"/>
            </svg>
            Recent Activity
        </h3>
    </div>
    <div class="info-card-body">
        <?php if (empty($recent_claims)): ?>
            <div class="empty-state">No recent activity yet. List items to see claims and donations.</div>
        <?php else: ?>
            <?php foreach ($recent_claims as $claim): ?>
                <div class="claim-item">
                    <div>
                        <div class="listing-name"><?= htmlspecialchars($claim['name']) ?></div>
                        <div class="claim-buyer">Claimed by <?= htmlspecialchars($claim['buyer_name']) ?></div>
                    </div>
                    <div class="claim-date"><?= date("M d", strtotime($claim['claimed_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Helper function for quick market list prompt (if needed)
function quickMarketListPrompt(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'actions.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="quick_list">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="price" value="0.00">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
