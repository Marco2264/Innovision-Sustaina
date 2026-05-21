<?php
/**
 * Sustaina - AI Assistant & Recipe Generator View Module
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

// Determine active user
$active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';

// Fetch active inventory for ingredients context
$stmt = $pdo->prepare("SELECT name, category, qty, expiry_date FROM inventory WHERE seller = ? ORDER BY expiry_date ASC");
$stmt->execute([$active_user]);
$my_items = $stmt->fetchAll();

// Build inventory context string for AI
$inventory_list = [];
foreach ($my_items as $item) {
    $inventory_list[] = $item['name'] . ' (' . $item['category'] . ', ' . $item['qty'] . ')';
}
$inventory_context = !empty($inventory_list) ? implode(', ', $inventory_list) : 'No items in inventory';
?>

<style>
    /* AI Assistant Full Page Chat */
    .ai-page-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 180px);
        max-height: 700px;
        background: var(--bg-card, #f3f4f6);
        border-radius: var(--radius-xl, 24px);
        border: 1px solid var(--border-color, #e5e7eb);
        overflow: hidden;
    }

    /* Chat Header */
    .ai-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        background: #ffffff;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
    }

    .ai-page-header-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .ai-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary, #064e3b);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
    }

    .ai-avatar svg {
        width: 20px;
        height: 20px;
    }

    .ai-header-info h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary, #111827);
        margin: 0;
    }

    .ai-header-info p {
        font-size: 0.75rem;
        color: var(--text-muted, #9ca3af);
        margin: 0;
    }

    .ai-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--success, #16a34a);
        background: rgba(22, 163, 74, 0.08);
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
    }

    .ai-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--success, #16a34a);
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* Chat Body */
    .ai-chat-body-full {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        background: #fafafa;
    }

    .chat-bubble {
        max-width: 80%;
        padding: 1rem 1.25rem;
        border-radius: 18px;
        font-size: 0.875rem;
        line-height: 1.6;
        animation: fadeInUp 0.25s ease;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chat-bubble.assistant {
        background: #ffffff;
        color: var(--text-primary, #111827);
        border: 1px solid var(--border-color, #e5e7eb);
        border-bottom-left-radius: 4px;
        align-self: flex-start;
    }

    .chat-bubble.user {
        background: var(--primary, #064e3b);
        color: white;
        border-bottom-right-radius: 4px;
        align-self: flex-end;
    }

    .chat-bubble-sender {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.4rem;
        opacity: 0.6;
    }

    .chat-bubble.assistant .chat-bubble-sender {
        color: var(--primary, #064e3b);
    }

    .chat-bubble ul {
        margin: 0.5rem 0 0 1.25rem;
        padding: 0;
    }

    .chat-bubble li {
        margin-bottom: 0.25rem;
    }

    /* Inventory Context Card */
    .inventory-context-card {
        background: rgba(6, 78, 59, 0.04);
        border: 1px solid rgba(6, 78, 59, 0.1);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-top: 0.5rem;
    }

    .inventory-context-card h4 {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--primary, #064e3b);
        margin: 0 0 0.4rem 0;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inventory-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .inv-tag {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        background: rgba(6, 78, 59, 0.08);
        color: var(--primary, #064e3b);
        border-radius: 6px;
        font-weight: 500;
    }

    /* Quick Prompts */
    .quick-prompts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .quick-prompt-btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.85rem;
        background: #ffffff;
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 20px;
        color: var(--text-secondary, #4b5563);
        cursor: pointer;
        transition: all 0.15s ease;
        font-weight: 500;
    }

    .quick-prompt-btn:hover {
        border-color: var(--primary, #064e3b);
        color: var(--primary, #064e3b);
        background: rgba(6, 78, 59, 0.04);
    }

    /* Chat Input */
    .ai-chat-input-bar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: #ffffff;
        border-top: 1px solid var(--border-color, #e5e7eb);
    }

    .ai-chat-input {
        flex: 1;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 14px;
        font-size: 0.875rem;
        font-family: inherit;
        color: var(--text-primary, #111827);
        background: #fafafa;
        transition: all 0.15s ease;
        outline: none;
    }

    .ai-chat-input:focus {
        border-color: var(--primary, #064e3b);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(6, 78, 59, 0.08);
    }

    .ai-chat-input::placeholder {
        color: var(--text-muted, #9ca3af);
    }

    .ai-send-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: var(--primary, #064e3b);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }

    .ai-send-btn:hover {
        background: var(--primary-hover, #022c22);
        transform: scale(1.05);
    }

    .ai-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .ai-send-btn svg {
        width: 18px;
        height: 18px;
    }

    /* Typing indicator */
    .typing-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 0.75rem 1rem;
    }

    .typing-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--text-muted, #9ca3af);
        animation: typing-bounce 1.4s infinite;
    }

    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typing-bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-6px); }
    }
</style>

<div class="ai-page-container">
    <!-- Header -->
    <div class="ai-page-header">
        <div class="ai-page-header-left">
            <div class="ai-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a10 10 0 1 0 10 10"/>
                    <path d="M12 6v6l4 2"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="ai-header-info">
                <h3>Sustaina AI Chef</h3>
                <p>Recipe suggestions based on your inventory</p>
            </div>
        </div>
        <div class="ai-status-badge">
            <span class="ai-status-dot"></span>
            Online
        </div>
    </div>

    <!-- Chat Body -->
    <div class="ai-chat-body-full" id="ai-chat-body">
        <!-- Welcome Message -->
        <div class="chat-bubble assistant">
            <div class="chat-bubble-sender">Sustaina AI</div>
            <div>Hi <?= htmlspecialchars($active_user) ?>! 👋 I'm your AI food assistant. I can suggest recipes using the ingredients you currently have in stock, help with food preservation tips, and more.</div>

            <?php if (!empty($my_items)): ?>
            <div class="inventory-context-card">
                <h4>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Your Current Inventory
                </h4>
                <div class="inventory-tags">
                    <?php foreach ($my_items as $item): ?>
                        <span class="inv-tag"><?= htmlspecialchars($item['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="inventory-context-card">
                <h4>📦 Your inventory is empty</h4>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Add items to your inventory first, then come back for personalized recipe ideas!</p>
            </div>
            <?php endif; ?>

            <div class="quick-prompts">
                <button class="quick-prompt-btn" onclick="sendQuickPrompt(this)">🍳 Suggest a recipe</button>
                <button class="quick-prompt-btn" onclick="sendQuickPrompt(this)">🧊 Preservation tips</button>
                <button class="quick-prompt-btn" onclick="sendQuickPrompt(this)">⏰ What's expiring soon?</button>
                <button class="quick-prompt-btn" onclick="sendQuickPrompt(this)">💡 Reduce food waste</button>
            </div>
        </div>
    </div>

    <!-- Input Bar -->
    <div class="ai-chat-input-bar">
        <input type="text" class="ai-chat-input" id="ai-chat-input" placeholder="Ask me for a recipe, food tips, or advice..." autocomplete="off">
        <button class="ai-send-btn" id="ai-send-btn" onclick="sendMessage()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<script>
const chatBody = document.getElementById('ai-chat-body');
const chatInput = document.getElementById('ai-chat-input');
const sendBtn = document.getElementById('ai-send-btn');

// Inventory items passed from PHP for AI context
const inventoryItems = <?= json_encode($inventory_list) ?>;

function sendQuickPrompt(btn) {
    chatInput.value = btn.textContent.trim();
    sendMessage();
}

function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    // Add user bubble
    appendBubble(message, 'user');
    chatInput.value = '';
    sendBtn.disabled = true;

    // Show typing indicator
    const typingEl = document.createElement('div');
    typingEl.className = 'chat-bubble assistant';
    typingEl.id = 'typing-indicator';
    typingEl.innerHTML = '<div class="typing-indicator"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>';
    chatBody.appendChild(typingEl);
    chatBody.scrollTop = chatBody.scrollHeight;

    // Simulate AI response
    setTimeout(() => {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();

        const response = generateAIResponse(message);
        appendBubble(response, 'assistant', true);
        sendBtn.disabled = false;
    }, 800 + Math.random() * 800);
}

function appendBubble(content, sender, isHTML = false) {
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${sender}`;

    if (sender === 'assistant') {
        bubble.innerHTML = `<div class="chat-bubble-sender">Sustaina AI</div><div>${isHTML ? content : escapeHTML(content)}</div>`;
    } else {
        bubble.textContent = content;
    }

    chatBody.appendChild(bubble);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function generateAIResponse(query) {
    const q = query.toLowerCase();
    const items = inventoryItems;
    const hasItems = items.length > 0;

    // Recipe suggestion
    if (q.includes('recipe') || q.includes('cook') || q.includes('make') || q.includes('suggest') || q.includes('🍳')) {
        if (!hasItems) {
            return "You don't have any items in your inventory yet. Add some ingredients first, and I'll suggest delicious recipes to use them up! 🛒";
        }
        const itemNames = items.map(i => i.split(' (')[0]);
        const recipes = [
            `Based on your inventory, here's what I recommend:<br><br><strong>🥘 ${itemNames[0]} Stir-Fry</strong><br>Quick sauté ${itemNames[0].toLowerCase()} with garlic, soy sauce, and sesame oil. ${itemNames.length > 1 ? 'Add ' + itemNames[1].toLowerCase() + ' for extra nutrition.' : 'Serve over rice.'}<br><br><em>⏱ Prep: 15 mins | Serves 2-3</em>`,
            `Here's a recipe idea using your stock:<br><br><strong>🍲 Hearty ${itemNames[0]} Bowl</strong><br>Combine ${itemNames.slice(0, Math.min(3, itemNames.length)).join(', ').toLowerCase()} in a warm bowl. Season with salt, pepper, and olive oil.<br><br><em>⏱ Prep: 20 mins | Serves 2</em>`,
            `Try this tonight:<br><br><strong>🥗 Fresh ${itemNames[0]} Medley</strong><br>Toss ${itemNames.slice(0, Math.min(2, itemNames.length)).join(' and ').toLowerCase()} together. Drizzle with lemon juice and herbs for a refreshing dish.<br><br><em>⏱ Prep: 10 mins | Serves 1-2</em>`
        ];
        return recipes[Math.floor(Math.random() * recipes.length)];
    }

    // Preservation tips
    if (q.includes('preserv') || q.includes('store') || q.includes('fresh') || q.includes('🧊')) {
        return "Here are some key preservation tips:<br><br><strong>🧊 Freezing:</strong> Most meats and cooked dishes freeze well for 3-6 months.<br><br><strong>🫙 Airtight Storage:</strong> Keep vegetables in sealed containers with a paper towel to absorb moisture.<br><br><strong>🍋 Acidic Bath:</strong> Soak cut fruits in lemon water to prevent browning.<br><br><strong>🌡️ Temperature:</strong> Keep your fridge at 1-4°C for optimal freshness.";
    }

    // Expiring items
    if (q.includes('expir') || q.includes('soon') || q.includes('urgent') || q.includes('⏰')) {
        if (!hasItems) {
            return "Your inventory is empty, so nothing is expiring! Add items to track their shelf life. 📦";
        }
        return `Based on your inventory, I'd recommend using items closest to their expiry date first. Check your <a href="index.php?page=inventory" style="color: var(--primary); font-weight: 600;">Inventory page</a> for exact dates.<br><br>💡 <strong>Tip:</strong> Items expiring within 1-2 days can be cooked and frozen to extend their life by months!`;
    }

    // Food waste
    if (q.includes('waste') || q.includes('reduce') || q.includes('💡')) {
        return "Here are ways to reduce food waste:<br><br>🛒 <strong>Plan meals</strong> before shopping to buy only what you need.<br><br>📦 <strong>First In, First Out</strong> — use older items before newer ones.<br><br>🏪 <strong>List surplus</strong> on the <a href='index.php?page=marketplace' style='color: var(--primary); font-weight: 600;'>Marketplace</a> to share with your community.<br><br>🍳 <strong>Cook creatively</strong> — most \"leftover\" ingredients can make great meals!";
    }

    // Default
    return "Great question! I can help you with:<br><br>• 🍳 <strong>Recipe suggestions</strong> based on your inventory<br>• 🧊 <strong>Food preservation</strong> tips<br>• ⏰ <strong>Expiry tracking</strong> advice<br>• 💡 <strong>Waste reduction</strong> strategies<br><br>Try asking something like \"Suggest a recipe\" or \"How do I store meat?\"";
}

// Enter key to send
chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});
</script>
