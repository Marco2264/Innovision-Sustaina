<?php
/**
 * Sustaina - Form Action Controller
 */
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

    // New action handlers for authentication
    // case 'register' will create a new user
    // case 'login' will authenticate a user
    // case 'logout' will end the session

switch ($action) {
    case 'add_item':
        $name = trim($_POST['item-name']);
        $category = $_POST['item-category'];
        $qty = trim($_POST['item-qty']);
        $expiry_date = $_POST['item-expiry'];
        $bought_date = isset($_POST['item-bought-date']) ? $_POST['item-bought-date'] : null;
        
        $list_on_market = isset($_POST['list-on-market']) ? 1 : 0;
        $market_price = $list_on_market ? floatval($_POST['market-price']) : 0.00;
        $market_desc = $list_on_market ? trim($_POST['market-description']) : null;
        $image_url = null;
        if (isset($_FILES['item-image']) && $_FILES['item-image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES['item-image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('img_') . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['item-image']['tmp_name'], $destination)) {
                $image_url = $destination;
            }
        }

        // Validation
        if (empty($name) || empty($category) || empty($qty) || empty($expiry_date)) {
            $_SESSION['toast'] = [
                'title' => 'Input Error',
                'message' => 'Please fill in all required fields.',
                'type' => 'danger'
            ];
            header("Location: index.php");
            exit();
        }

        // Meat constraint validation
        if ($category === 'Meat') {
            if (empty($bought_date)) {
                $_SESSION['toast'] = [
                    'title' => 'Validation Error',
                    'message' => 'Date bought is mandatory for meat products.',
                    'type' => 'danger'
                ];
                header("Location: index.php");
                exit();
            }
            if (strtotime($bought_date) > strtotime($expiry_date)) {
                $_SESSION['toast'] = [
                    'title' => 'Date Error',
                    'message' => 'Purchased date cannot be later than expiry date.',
                    'type' => 'danger'
                ];
                header("Location: index.php");
                exit();
            }
        } else {
            $bought_date = null; // Clear if not meat
        }

        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory (name, category, qty, bought_date, expiry_date, listed_on_market, market_price, market_desc, image_url, seller, seller_type, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Restaurant', 'Downtown')");
            $stmt->execute([$name, $category, $qty, $bought_date, $expiry_date, $list_on_market, $market_price, $market_desc, $image_url, $active_user]);
            
            $msg = "\"$name\" has been successfully added to raw inventory.";
            if ($list_on_market) {
                $msg .= " Also posted to Marketplace feed.";
            }

            $_SESSION['toast'] = [
                'title' => 'Item Posted',
                'message' => $msg,
                'type' => 'success'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to save item. Details: ' . $e->getMessage(),
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=inventory");
        exit();

    case 'edit_item':
        $id = intval($_POST['edit-item-id']);
        $name = trim($_POST['edit-item-name']);
        $category = $_POST['edit-item-category'];
        $qty = trim($_POST['edit-item-qty']);
        $expiry_date = $_POST['edit-item-expiry'];
        $bought_date = isset($_POST['edit-item-bought-date']) ? $_POST['edit-item-bought-date'] : null;

        if ($category !== 'Meat') {
            $bought_date = null;
        }

        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';

        try {
            $stmt = $pdo->prepare("UPDATE inventory SET name = ?, category = ?, qty = ?, bought_date = ?, expiry_date = ? WHERE id = ? AND seller = ?");
            $stmt->execute([$name, $category, $qty, $bought_date, $expiry_date, $id, $active_user]);
            
            $_SESSION['toast'] = [
                'title' => 'Item Updated',
                'message' => "\"$name\" has been successfully updated.",
                'type' => 'success'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to update item. Details: ' . $e->getMessage(),
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=inventory");
        exit();

    case 'delete':
        $id = intval($_POST['id']);
        
        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            // Get item name first for notification details
            $stmt = $pdo->prepare("SELECT name FROM inventory WHERE id = ? AND seller = ?");
            $stmt->execute([$id, $active_user]);
            $item = $stmt->fetch();

            if ($item) {
                $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ? AND seller = ?");
                $stmt->execute([$id, $active_user]);

                $_SESSION['toast'] = [
                    'title' => 'Item Discarded',
                    'message' => "\"{$item['name']}\" has been removed from raw inventory.",
                    'type' => 'warning'
                ];
            }
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to delete item.',
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=inventory");
        exit();

    case 'delist':
        $id = intval($_POST['id']);
        
        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET listed_on_market = 0 WHERE id = ? AND seller = ?");
            $stmt->execute([$id, $active_user]);

            $_SESSION['toast'] = [
                'title' => 'Post Removed',
                'message' => 'Listing successfully withdrawn from surplus feed.',
                'type' => 'info'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to update marketplace status.',
                'type' => 'danger'
            ];
        }

        // Redirect back to page it came from
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: " . $referer);
        exit();

    case 'quick_list':
        $id = intval($_POST['id']);
        $price = floatval($_POST['price']);
        
        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET listed_on_market = 1, market_price = ?, market_desc = 'Urgent listing: listed to prevent food waste. Store refrigerated.' WHERE id = ? AND seller = ?");
            $stmt->execute([$price, $id, $active_user]);

            $_SESSION['toast'] = [
                'title' => 'Listed on Market',
                'message' => 'Surplus item is now visible on the local feed.',
                'type' => 'success'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to publish to marketplace.',
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=marketplace");
        exit();

    case 'discount_item':
        $id = intval($_POST['id']);
        
        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            // Apply 25% discount (multiply by 0.75)
            $stmt = $pdo->prepare("UPDATE inventory SET market_price = market_price * 0.75 WHERE id = ? AND seller = ?");
            $stmt->execute([$id, $active_user]);

            $_SESSION['toast'] = [
                'title' => 'Discount Applied',
                'message' => 'Listing price was reduced by 25% to accelerate matching.',
                'type' => 'success'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to apply discount.',
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=marketplace");
        exit();

    case 'claim':
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        
        $active_user = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
        try {
            // For others' items (not current user), we simulate claim by deleting from market feed
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ? AND seller != ?");
            $stmt->execute([$id, $active_user]);

            $_SESSION['toast'] = [
                'title' => 'Claim Confirmed',
                'message' => "Successfully reserved \"$name\". Contact details sent to notifications tab.",
                'type' => 'success'
            ];
        } catch (\PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to claim item.',
                'type' => 'danger'
            ];
        }

        header("Location: index.php?page=marketplace");
        exit();

    case 'register':
        $full_name = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'Member');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($full_name) || empty($role) || empty($email) || empty($password) || empty($confirm)) {
            $_SESSION['toast'] = [
                'title' => 'Input Error',
                'message' => 'All fields are required.',
                'type' => 'danger'
            ];
            header('Location: signup.php');
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['toast'] = [
                'title' => 'Invalid Email',
                'message' => 'Please provide a valid email address.',
                'type' => 'danger'
            ];
            header('Location: signup.php');
            exit();
        }
        if ($password !== $confirm) {
            $_SESSION['toast'] = [
                'title' => 'Password Mismatch',
                'message' => 'Passwords do not match.',
                'type' => 'danger'
            ];
            header('Location: signup.php');
            exit();
        }
        // Insert new user
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $full_name, $role, $phone]);
            $_SESSION['toast'] = [
                'title' => 'Registration Successful',
                'message' => 'You can now log in.',
                'type' => 'success'
            ];
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Unable to register. Email may already exist.',
                'type' => 'danger'
            ];
            header('Location: signup.php');
            exit();
        }
        break;

    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $_SESSION['toast'] = [
                'title' => 'Input Error',
                'message' => 'Both email and password are required.',
                'type' => 'danger'
            ];
            header('Location: login.php');
            exit();
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                // Auth success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['toast'] = [
                    'title' => 'Login Successful',
                    'message' => 'Welcome back!',
                    'type' => 'success'
                ];
                header('Location: index.php');
                exit();
            } else {
                $_SESSION['toast'] = [
                    'title' => 'Login Failed',
                    'message' => 'Invalid credentials.',
                    'type' => 'danger'
                ];
                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Unable to process login.',
                'type' => 'danger'
            ];
            header('Location: login.php');
            exit();
        }
        break;

    case 'update_profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'Member');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($full_name)) {
            $_SESSION['toast'] = [
                'title' => 'Validation Error',
                'message' => 'Full Name cannot be empty.',
                'type' => 'danger'
            ];
            header('Location: index.php?page=profile');
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $role, $phone, $_SESSION['user_id']]);

            // Update session values so UI reflects changes immediately
            $_SESSION['user_full_name'] = $full_name;
            $_SESSION['user_role'] = $role;

            $_SESSION['toast'] = [
                'title' => 'Profile Updated',
                'message' => 'Your profile information has been saved successfully.',
                'type' => 'success'
            ];
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'title' => 'Database Error',
                'message' => 'Failed to update profile.',
                'type' => 'danger'
            ];
        }
        header('Location: index.php?page=profile');
        exit();
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
        break;

    default:
        header('Location: index.php');
        exit();
}
?>
