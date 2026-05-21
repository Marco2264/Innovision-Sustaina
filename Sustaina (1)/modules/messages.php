<?php
/**
 * Sustaina - Messages Module
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

$active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';

// Ensure the messages table exists
$pdo->exec("
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `sender` VARCHAR(255) NOT NULL,
    `receiver` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Handle POST request to send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $item_id = $_POST['item_id'];
    $receiver = $_POST['receiver'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (item_id, sender, receiver, message) VALUES (:item_id, :sender, :receiver, :message)");
        $stmt->execute([
            ':item_id' => $item_id,
            ':sender' => $active_user,
            ':receiver' => $receiver,
            ':message' => $message
        ]);
    }
    // Redirect back to the same chat
    echo "<script>window.location.href = 'index.php?page=messages&item_id=$item_id&chat_with=" . urlencode($receiver) . "';</script>";
    exit;
}

// Get list of conversations involving the active user (either as buyer or seller)
// A conversation is uniquely identified by the item_id and the other person
$stmt_convos = $pdo->prepare("
    SELECT DISTINCT 
        m.item_id, 
        i.name as item_name, 
        i.image_url,
        CASE WHEN m.sender = :active_user1 THEN m.receiver ELSE m.sender END as contact_name
    FROM messages m
    JOIN inventory i ON m.item_id = i.id
    WHERE m.sender = :active_user2 OR m.receiver = :active_user3
    ORDER BY m.created_at DESC
");
$stmt_convos->execute([
    ':active_user1' => $active_user,
    ':active_user2' => $active_user,
    ':active_user3' => $active_user
]);
$conversations = $stmt_convos->fetchAll();

// Determine which chat is currently active
$active_item_id = isset($_GET['item_id']) ? $_GET['item_id'] : null;
$active_contact = isset($_GET['chat_with']) ? $_GET['chat_with'] : null;

$active_messages = [];
$item_details = null;

if ($active_item_id && $active_contact) {
    // Fetch messages for this specific conversation
    $stmt_msgs = $pdo->prepare("
        SELECT * FROM messages 
        WHERE item_id = :item_id 
        AND ((sender = :active_user1 AND receiver = :contact1) OR (sender = :contact2 AND receiver = :active_user2))
        ORDER BY created_at ASC
    ");
    $stmt_msgs->execute([
        ':item_id' => $active_item_id,
        ':active_user1' => $active_user,
        ':contact1' => $active_contact,
        ':contact2' => $active_contact,
        ':active_user2' => $active_user
    ]);
    $active_messages = $stmt_msgs->fetchAll();

    // Fetch item details for header
    $stmt_item = $pdo->prepare("SELECT * FROM inventory WHERE id = :item_id");
    $stmt_item->execute([':item_id' => $active_item_id]);
    $item_details = $stmt_item->fetch();
}
?>

<style>
    .messages-container {
        display: flex;
        height: calc(100vh - 180px);
        /* Fill available space */
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .messages-sidebar {
        width: 320px;
        border-right: 1px solid var(--border-color);
        background: #fbfbf9;
        display: flex;
        flex-direction: column;
    }

    .messages-sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .messages-sidebar-header h3 {
        margin: 0;
        font-size: 1.2rem;
        color: var(--text-primary);
    }

    .convo-list {
        flex: 1;
        overflow-y: auto;
    }

    .convo-item {
        display: flex;
        padding: 1rem 1.5rem;
        gap: 1rem;
        border-bottom: 1px solid var(--border-color);
        text-decoration: none;
        color: inherit;
        transition: background 0.2s;
        align-items: center;
    }

    .convo-item:hover {
        background: rgba(0, 0, 0, 0.02);
    }

    .convo-item.active {
        background: white;
        border-left: 4px solid var(--primary);
    }

    .convo-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .convo-details {
        flex: 1;
        overflow: hidden;
    }

    .convo-name {
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .convo-item-name {
        font-size: 0.85rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
    }

    .chat-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .chat-history {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: #fafafa;
    }

    .chat-bubble {
        max-width: 70%;
        padding: 1rem 1.25rem;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.4;
        position: relative;
    }

    .chat-bubble.sent {
        align-self: flex-end;
        background: var(--primary);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .chat-bubble.received {
        align-self: flex-start;
        background: #e5e7eb;
        color: var(--text-primary);
        border-bottom-left-radius: 4px;
    }

    .chat-bubble-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 0.5rem;
        display: block;
        text-align: right;
    }

    .chat-input-area {
        padding: 1.5rem;
        border-top: 1px solid var(--border-color);
        background: white;
    }

    .chat-input-form {
        display: flex;
        gap: 1rem;
    }

    .chat-input {
        flex: 1;
        padding: 1rem 1.25rem;
        border: 1px solid var(--border-color);
        border-radius: 24px;
        outline: none;
        font-size: 1rem;
        background: #fbfbf9;
        transition: all 0.2s;
    }

    .chat-input:focus {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(6, 78, 59, 0.1);
    }

    .chat-send-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .chat-send-btn:hover {
        background: var(--primary-hover);
        transform: scale(1.05);
    }

    .empty-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        text-align: center;
        padding: 2rem;
    }

    .empty-chat i {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>

<div class="messages-container">
    <!-- Sidebar -->
    <div class="messages-sidebar">
        <div class="messages-sidebar-header">
            <h3>Conversations</h3>
        </div>
        <div class="convo-list">
            <?php if (empty($conversations)): ?>
                <div style="padding: 2rem 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    No messages yet.<br>Claim an item to start a chat!
                </div>
            <?php else: ?>
                <?php
                $shown_pairs = [];
                foreach ($conversations as $convo):
                    // Prevent duplicate conversation links for the same item+user pair
                    $pair_id = $convo['item_id'] . '-' . $convo['contact_name'];
                    if (in_array($pair_id, $shown_pairs))
                        continue;
                    $shown_pairs[] = $pair_id;

                    $is_active = ($active_item_id == $convo['item_id'] && $active_contact == $convo['contact_name']);
                    ?>
                    <a href="index.php?page=messages&item_id=<?= $convo['item_id'] ?>&chat_with=<?= urlencode($convo['contact_name']) ?>"
                        class="convo-item <?= $is_active ? 'active' : '' ?>">
                        <div class="convo-avatar">
                            <?= strtoupper(substr($convo['contact_name'], 0, 1)) ?>
                        </div>
                        <div class="convo-details">
                            <div class="convo-name"><?= htmlspecialchars($convo['contact_name']) ?></div>
                            <div class="convo-item-name"><i data-lucide="package"
                                    style="width:12px;height:12px;display:inline-block;vertical-align:-2px;margin-right:4px;"></i><?= htmlspecialchars($convo['item_name']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-area">
        <?php if ($active_item_id && $active_contact && $item_details): ?>
            <div class="chat-header">
                <div class="convo-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                    <?= strtoupper(substr($active_contact, 0, 1)) ?>
                </div>
                <div>
                    <h3 style="margin:0; font-size: 1.1rem; color: var(--text-primary);">
                        <?= htmlspecialchars($active_contact) ?></h3>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.2rem;">
                        Discussing: <strong><?= htmlspecialchars($item_details['name']) ?></strong>
                        (₱<?= number_format($item_details['market_price'], 2) ?>)
                    </div>
                </div>
            </div>

            <div class="chat-history" id="chat-history-box">
                <?php if (empty($active_messages)): ?>
                    <div style="text-align: center; color: var(--text-muted); margin-top: auto; margin-bottom: auto;">
                        Start the conversation about picking up this item!
                    </div>
                <?php else: ?>
                    <?php foreach ($active_messages as $msg):
                        $is_sent = ($msg['sender'] == $active_user);
                        ?>
                        <div class="chat-bubble <?= $is_sent ? 'sent' : 'received' ?>">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            <span class="chat-bubble-time"
                                style="<?= $is_sent ? 'color: rgba(255,255,255,0.7);' : 'color: rgba(0,0,0,0.4);' ?>">
                                <?= date('M j, g:i A', strtotime($msg['created_at'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <form method="POST" action="index.php?page=messages" class="chat-input-form">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($active_item_id) ?>">
                    <input type="hidden" name="receiver" value="<?= htmlspecialchars($active_contact) ?>">

                    <input type="text" name="message" class="chat-input" placeholder="Type a message..." required autofocus
                        autocomplete="off">
                    <button type="submit" class="chat-send-btn">
                        <i data-lucide="send"></i>
                    </button>
                </form>
            </div>

            <script>
                // Scroll to bottom of chat automatically
                const chatBox = document.getElementById('chat-history-box');
                chatBox.scrollTop = chatBox.scrollHeight;
            </script>
        <?php else: ?>
            <div class="empty-chat">
                <i data-lucide="message-square"></i>
                <h2>Your Messages</h2>
                <p>Select a conversation from the sidebar or claim an item on the marketplace to start chatting.</p>
            </div>
        <?php endif; ?>
    </div>
</div>