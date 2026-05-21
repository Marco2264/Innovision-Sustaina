<?php
/**
 * Sustaina - Main Application Entry Point (Single Consolidated PHP Shell)
 */
session_start();
require_once 'db.php';
// Redirect unauthenticated users to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: landing.html');
    exit();
}

define('Sustaina_ENTRY', true);

// Set page routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'inventory', 'marketplace', 'ai-assistant', 'geofence', 'profile', 'messages'];
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

$page_titles = [
    'dashboard' => ['title' => 'Homepage', 'icon' => 'Home'],
    'inventory' => ['title' => 'Master Raw Inventory', 'icon' => 'database'],
    'marketplace' => ['title' => 'Surplus Marketplace', 'icon' => 'store'],
    'ai-assistant' => ['title' => 'AI Recipe Assitant', 'icon' => 'chef-hat'],
    'messages' => ['title' => 'Messages', 'icon' => 'message-square'],
    'geofence' => ['title' => 'Geofence Map', 'icon' => 'map-pin'],
    'profile' => ['title' => 'Profile', 'icon' => 'user']
];
$current_meta = $page_titles[$page];

// Fetch stats for header warnings
$active_user_main = isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : 'Bella Grillhouse';
$stmt_exp = $pdo->prepare("SELECT COUNT(*) as count FROM inventory WHERE expiry_date <= DATE_ADD('2026-05-20', INTERVAL 3 DAY) AND expiry_date >= '2026-05-20' AND seller = ?");
$stmt_exp->execute([$active_user_main]);
$exp_count = $stmt_exp->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Sustaina - AI-Powered Food Waste Prevention & Raw Food Marketplace for Homes and Restaurants.">
    <title>Sustaina - Prevent Food Waste with AI</title>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js CDN for beautiful interactive charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Embedded CSS Stylesheet (Almond Cream & Leaf Green theme) -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            /* Modern Soft UI Palette */
            --bg-sidebar: #f3f4f6;
            /* Light grey sidebar */
            --border-color: #e5e7eb;
            /* Soft border */

            /* Surface Backgrounds */
            --bg-surface: #fafafa;
            /* Main app background */
            --bg-card: #f3f4f6;
            /* Grey card background for stats/chart */
            --bg-surface-solid: #ffffff;

            /* Brand Colors */
            --primary: #064e3b;
            /* Very Dark Forest Green */
            --primary-hover: #022c22;
            --primary-glow: rgba(6, 78, 59, 0.1);
            --secondary: #d97706;
            /* Amber Gold */
            --secondary-glow: rgba(217, 119, 6, 0.1);

            /* Semantic Colors - Fresh Organic Badges */
            --color-meat: #dc2626;
            /* Coral Red */
            --color-meat-bg: #fee2e2;
            --color-veg: #16a34a;
            /* Fresh Green */
            --color-veg-bg: #dcfce7;
            --color-fruit: #ea580c;
            /* Orange */
            --color-fruit-bg: #ffedd5;
            --color-dairy: #0284c7;
            /* Sky Blue */
            --color-dairy-bg: #e0f2fe;
            --color-bakery: #b45309;
            /* Warm Terracotta */
            --color-bakery-bg: #fef3c7;
            --color-other: #7c3aed;
            /* Soft Purple */
            --color-other-bg: #f3e8ff;

            --text-primary: #111827;
            /* Dark text */
            --text-secondary: #4b5563;
            /* Muted dark text */
            --text-muted: #9ca3af;
            /* Gray Muted */

            --success: #16a34a;
            --warning: #ea580c;
            /* Orange warning like in screenshot */
            --warning-bg: #fff7ed;
            /* Warning panel background */
            --danger: #dc2626;
            --info: #0284c7;

            /* Soft shadows */
            --glass-blur: blur(0px);
            --glass-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);

            /* Transitions */
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);

            /* Border Radius - Very Rounded */
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 20px;
            --radius-xl: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-surface);
            color: var(--text-primary);
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-surface);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(34, 63, 53, 0.15);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(34, 63, 53, 0.25);
        }

        /* ==========================================================================
           LAYOUT STRUCTURE
           ========================================================================== */

        .app-container {
            display: flex;
            flex-direction: column;
            width: 100vw;
            height: 100vh;
            background-color: var(--bg-surface);
            overflow: hidden;
        }

        /* Top Navbar Layout */
        /* Sidebar Branding (Logo) */
        .sidebar-branding {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 0.5rem;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            background: var(--primary);
            width: 38px;
            height: 38px;
            color: #faf9f6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-text h1 {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin: 0 !important;
        }

        .logo-text span {
            color: var(--text-muted);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 !important;
        }

        /* Sidebar Footer (User Profile) */
        .sidebar-footer {
            margin-top: auto;
            padding-top: 1.5rem;
        }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            padding: 0.75rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.06);
            transition: var(--transition-fast);
            cursor: pointer;
        }

        .user-profile-badge:hover {
            box-shadow: 0 4px 12px rgba(29, 51, 44, 0.1);
        }

        .user-avatar {
            background: var(--primary);
            color: #faf9f6;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .user-name {
            color: var(--text-primary);
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            color: var(--text-muted);
            font-size: 0.7rem;
            line-height: 1.2;
            margin: 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* App Body layout splitting Sidebar & Main */
        .app-body {
            display: grid;
            grid-template-columns: 280px 1fr;
            height: 100%;
            width: 100%;
            overflow: hidden;
        }

        /* Sidebar navigation */
        aside {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
            position: relative;
            z-index: 10;
        }

        .sidebar-header-box {
            background: var(--primary);
            color: #faf9f6;
            border-radius: var(--radius-lg);
            padding: 1.15rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(34, 63, 53, 0.1);
        }

        .sidebar-header-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #faf9f6;
            margin-bottom: 0.25rem;
        }

        .sidebar-header-desc {
            font-size: 0.75rem;
            color: #d1d5db;
            line-height: 1.4;
        }

        .sidebar-nav-cards {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .sidebar-card-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            background: transparent;
            border: 1px solid transparent;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition-fast);
        }

        .sidebar-card-link:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .sidebar-card-link.active {
            background: #ffffff;
            border: 1px solid var(--border-color);
            box-shadow: var(--glass-shadow);
        }

        .sidebar-card-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--bg-sidebar);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            flex-shrink: 0;
            transition: var(--transition-fast);
            border: 1px solid var(--border-color);
        }

        .sidebar-card-link.active .sidebar-card-icon {
            background: var(--text-primary);
            color: white;
            border-color: var(--text-primary);
        }
        }

        .sidebar-card-icon i,
        .sidebar-card-icon svg {
            width: 18px;
            height: 18px;
        }

        .sidebar-card-link.active .sidebar-card-icon {
            background: var(--primary);
            color: #faf9f6;
        }

        .sidebar-card-details {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .sidebar-card-desc {
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        /* Main Content Area */
        main {
            padding: 2.5rem;
            height: 100%;
            overflow-y: auto;
            background: var(--bg-surface);
            display: flex;
            flex-direction: column;
            gap: 2.25rem;
        }

        /* Page header layouts */
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            width: 100%;
        }

        .page-title-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: rgba(34, 63, 53, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .page-title-icon i,
        .page-title-icon svg {
            width: 20px;
            height: 20px;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .page-controls-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-controls-container .search-bar-container {
            width: 260px;
        }

        .category-select {
            background: white;
            border: none;
            padding: 0.72rem 1rem;
            border-radius: var(--radius-xl);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.08);
        }

        .category-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 63, 53, 0.15);
        }

        /* Header bar */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .search-bar-container {
            position: relative;
            max-width: 400px;
            width: 100%;
        }

        .search-bar-container svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 16px;
            height: 16px;
        }

        .search-input {
            width: 100%;
            background: var(--bg-card);
            border: none;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: var(--radius-xl);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: var(--transition-fast);
        }

        .search-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition-fast);
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(6, 78, 59, 0.1);
            padding: 0.75rem 1.5rem;
            min-width: 160px;
            height: 45px;
            justify-content: center;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(6, 78, 59, 0.15);
        }

        .btn-secondary {
            background: white;
            color: var(--text-primary);
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.08);
        }

        .btn-secondary:hover {
            background: #fbfbf9;
            box-shadow: 0 4px 12px rgba(29, 51, 44, 0.12);
        }

        .btn-logout {
            background: transparent;
            color: var(--color-meat);
            border: 1px solid rgba(220, 38, 38, 0.2);
            display: flex;
            justify-content: center;
            box-shadow: none;
        }

        .btn-logout:hover {
            background: rgba(220, 38, 38, 0.05);
            border-color: rgba(220, 38, 38, 0.4);
            transform: translateY(-1px);
        }

        .icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            position: relative;
            transition: var(--transition-fast);
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.08);
        }

        .icon-btn:hover {
            background: #fbfbf9;
            color: var(--text-primary);
        }

        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--color-meat);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 6px rgba(220, 38, 38, 0.4);
        }

        /* ==========================================================================
           CARDS & STATS
           ========================================================================= */

        .view-section {
            animation: fadeIn var(--transition-normal);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--bg-card);
            border: none;
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(29, 51, 44, 0.06);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px -8px rgba(29, 51, 44, 0.12);
        }

        .card.no-hover:hover {
            transform: none;
            box-shadow: 0 2px 12px rgba(29, 51, 44, 0.06);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            cursor: default;
        }

        .stat-card:hover .stat-icon {
            transform: translateY(-2px);
        }

        .stat-card:hover .stat-value {
            color: var(--primary);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-icon.green {
            background: var(--color-veg-bg);
            color: var(--primary);
        }

        .stat-icon.indigo {
            background: var(--color-other-bg);
            color: var(--color-other);
        }

        .stat-icon.orange {
            background: var(--color-bakery-bg);
            color: var(--warning);
        }

        .stat-icon.cyan {
            background: var(--color-dairy-bg);
            color: var(--info);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-primary);
            transition: color 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-trend.up {
            color: var(--success);
        }

        .stat-trend.down {
            color: var(--danger);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 1.75rem;
            margin-top: 1.5rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .section-title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }

        .section-title svg {
            color: var(--primary);
            width: 20px;
            height: 20px;
        }

        .ai-insights-panel {
            background: #ffffff;
        }

        .warning-card {
            background: var(--warning-bg);
            border: 1px solid rgba(234, 88, 12, 0.15);
        }

        .ai-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .ai-header svg {
            color: var(--primary);
        }

        .ai-header h3 {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .ai-badge {
            background: var(--primary);
            color: white;
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ai-tips-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ai-tip-card {
            background: white;
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            gap: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            cursor: default;
        }

        .ai-tip-card:hover {
            transform: translateX(6px);
            box-shadow: 0 6px 16px rgba(21, 128, 61, 0.1);
        }

        .ai-tip-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .ai-tip-content h4 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: var(--text-primary);
        }

        .ai-tip-content p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .ai-tip-action {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border: none;
            background: none;
        }

        .ai-tip-action:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* ==========================================================================
           INVENTORY TABLE
           ========================================================================== */

        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .category-tags {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .category-tag {
            background: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition-fast);
            white-space: nowrap;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(29, 51, 44, 0.06);
        }

        .category-tag:hover,
        .category-tag.active {
            background: var(--text-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(29, 51, 44, 0.15);
        }

        .category-tag[data-cat="Meat"].active {
            background: var(--color-meat);
            color: white;
        }

        .category-tag[data-cat="Vegetables"].active {
            background: var(--color-veg);
            color: white;
        }

        .category-tag[data-cat="Fruits"].active {
            background: var(--color-fruit);
            color: white;
        }

        .category-tag[data-cat="Dairy"].active {
            background: var(--color-dairy);
            color: white;
        }

        .category-tag[data-cat="Bakery"].active {
            background: var(--color-bakery);
            color: white;
        }

        .category-tag[data-cat="Other"].active {
            background: var(--color-other);
            color: white;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th,
        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        td {
            font-size: 0.9rem;
            color: var(--text-primary);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .food-item-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .food-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .food-details {
            display: flex;
            flex-direction: column;
        }

        .food-name {
            font-weight: 700;
            color: var(--text-primary);
        }

        .food-subtext {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-fresh {
            background: var(--color-veg-bg);
            color: var(--primary);
            border: 1px solid rgba(22, 163, 74, 0.2);
        }

        .badge-warning {
            background: var(--color-bakery-bg);
            color: var(--warning);
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .badge-danger {
            background: var(--color-meat-bg);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .table-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition-fast);
            box-shadow: 0 2px 6px rgba(29, 51, 44, 0.06);
        }

        .table-btn:hover {
            color: var(--text-primary);
            background: #fbfbf9;
            box-shadow: 0 4px 10px rgba(29, 51, 44, 0.1);
        }

        .table-btn.sell:hover {
            background: var(--color-other-bg);
            color: var(--color-other);
            box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15);
        }

        .table-btn.delete:hover {
            background: var(--color-meat-bg);
            color: var(--color-meat);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.15);
        }

        /* ==========================================================================
           MARKETPLACE VIEW
           ========================================================================== */

        .market-feed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            box-shadow: 0 1px 4px rgba(29, 51, 44, 0.06);
            padding-bottom: 1rem;
        }

        .market-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .market-card {
            background: white;
            border: none;
            border-radius: var(--radius-xl);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 12px rgba(29, 51, 44, 0.06);
            transition: var(--transition-normal);
        }

        .market-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(21, 128, 61, 0.1);
        }

        .market-img-container {
            position: relative;
            height: 180px;
            background-size: cover;
            background-position: center;
            background-color: #fbfbf9;
        }

        .market-card-badge {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            background: white;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .market-expiry-counter {
            position: absolute;
            bottom: 0.75rem;
            right: 0.75rem;
            background: var(--color-meat);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }

        .market-expiry-counter.safe {
            background: var(--primary);
            box-shadow: 0 2px 8px rgba(21, 128, 61, 0.3);
        }

        .market-price-tag {
            position: absolute;
            bottom: 0.75rem;
            left: 0.75rem;
            background: #1e293b;
            color: white;
            font-weight: 800;
            font-size: 0.95rem;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-sm);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .market-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex-grow: 1;
        }

        .market-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .market-item-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .market-seller-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .seller-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .market-description {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.9em;
        }

        .market-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.75rem;
            background: #fbfbf9;
            padding: 0.5rem;
            border-radius: var(--radius-md);
            border: none;
            box-shadow: 0 1px 4px rgba(29, 51, 44, 0.06);
        }

        .market-meta-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--text-secondary);
        }

        .market-meta-item svg {
            width: 12px;
            height: 12px;
            color: var(--text-muted);
        }

        .market-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .market-footer button,
        .market-footer form {
            flex-grow: 1;
        }

        /* ==========================================================================
           MODALS
           ========================================================================== */

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(28, 25, 23, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--transition-normal);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-container {
            background: white;
            border: none;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 550px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transform: translateY(20px) scale(0.95);
            transition: transform var(--transition-normal);
        }

        .modal-overlay.active .modal-container {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: none;
            box-shadow: 0 1px 4px rgba(29, 51, 44, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }

        .modal-title svg {
            color: var(--primary);
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition-fast);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            background: #fbfbf9;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required-star {
            color: var(--color-meat);
        }

        input,
        select,
        textarea {
            background: white;
            border: none;
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: var(--transition-fast);
            width: 100%;
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.08);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 63, 53, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .conditional-field-wrapper {
            grid-column: span 2;
            display: none;
            animation: slideDown var(--transition-normal);
            border-left: 3px solid var(--color-meat);
            padding-left: 1rem;
            background: #fdf2f2;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            margin-bottom: 0.5rem;
        }

        .conditional-field-wrapper.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ai-helper-preview {
            background: #f0fdf4;
            border: 1px dashed rgba(22, 163, 74, 0.3);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.25rem;
            grid-column: span 2;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .ai-preview-icon {
            font-size: 1.5rem;
            color: var(--primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                filter: drop-shadow(0 0 1px var(--border-glow));
            }

            50% {
                transform: scale(1.05);
                filter: drop-shadow(0 0 4px rgba(22, 163, 74, 0.3));
            }

            100% {
                transform: scale(1);
                filter: drop-shadow(0 0 1px var(--border-glow));
            }
        }

        .ai-preview-content {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .ai-preview-title {
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .ai-preview-text {
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .toggle-switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fbfbf9;
            border: none;
            padding: 1rem;
            border-radius: var(--radius-md);
            grid-column: span 2;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(29, 51, 44, 0.06);
        }

        .toggle-info {
            display: flex;
            flex-direction: column;
        }

        .toggle-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .toggle-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e2db;
            transition: .4s;
            border-radius: 34px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:checked+.slider {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }

        .marketplace-form-fields {
            grid-column: span 2;
            display: none;
            border-top: none;
            box-shadow: 0 -1px 4px rgba(29, 51, 44, 0.04);
            padding-top: 1.25rem;
            margin-top: 0.5rem;
        }

        .marketplace-form-fields.active {
            display: grid;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: none;
            box-shadow: 0 -1px 4px rgba(29, 51, 44, 0.06);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* ==========================================================================
           TOAST NOTIFICATIONS
           ========================================================================== */

        #toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .toast {
            background: white;
            border: none;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            max-width: 400px;
            animation: slideInRight var(--transition-normal);
            transition: opacity var(--transition-fast), transform var(--transition-fast);
        }

        .toast.success {
            border-left: 4px solid var(--success);
        }

        .toast.info {
            border-left: 4px solid var(--info);
        }

        .toast.warning {
            border-left: 4px solid var(--warning);
        }

        .toast.danger {
            border-left: 4px solid var(--danger);
        }

        .toast-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            color: var(--success);
        }

        .toast.info .toast-icon {
            color: var(--info);
        }

        .toast.warning .toast-icon {
            color: var(--warning);
        }

        .toast.danger .toast-icon {
            color: var(--danger);
        }

        .toast-body {
            flex-grow: 1;
        }

        .toast-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .toast-message {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.1rem;
        }

        .toast-close {
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
            transition: var(--transition-fast);
        }

        .toast-close:hover {
            color: var(--text-primary);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ==========================================================================
           AI INTERACTION
           ========================================================================== */
        .ai-assistant-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .ai-assistant-container {
                grid-template-columns: 1fr;
            }
        }

        .chat-card {
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-history {
            flex-grow: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 0.5rem;
            margin-bottom: 1rem;
        }

        .chat-msg {
            display: flex;
            flex-direction: column;
            max-width: 85%;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .chat-msg.assistant {
            background: #f0fdf4;
            border: none;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.08);
            align-self: flex-start;
            border-top-left-radius: var(--radius-sm);
            color: var(--text-primary);
        }

        .chat-msg.user {
            background: #fbfbf9;
            border: none;
            box-shadow: 0 2px 6px rgba(29, 51, 44, 0.06);
            align-self: flex-end;
            border-top-right-radius: var(--radius-sm);
            color: var(--text-primary);
        }

        .chat-msg-sender {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .chat-msg.assistant .chat-msg-sender {
            color: var(--primary);
        }

        .chat-msg.user .chat-msg-sender {
            color: var(--text-secondary);
        }

        .chat-input-wrapper {
            display: flex;
            gap: 0.5rem;
        }

        .recipe-suggestion-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            overflow-y: auto;
            max-height: 400px;
        }

        .recipe-card {
            background: white;
            border: none;
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: var(--transition-fast);
            box-shadow: 0 2px 8px rgba(29, 51, 44, 0.06);
        }

        .recipe-card:hover {
            box-shadow: 0 4px 16px rgba(22, 163, 74, 0.1);
        }

        .recipe-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .recipe-title {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .recipe-match-badge {
            background: var(--color-veg-bg);
            color: var(--primary);
            border: 1px solid rgba(22, 163, 74, 0.2);
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: 700;
        }

        .recipe-ingredients {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .ingredient-tag {
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            background: #f5f5f4;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
        }

        .ingredient-tag.matched {
            background: var(--color-veg-bg);
            color: var(--primary);
            border: 1px solid rgba(22, 163, 74, 0.1);
        }

        .recipe-instruction {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        /* ==========================================================================
           MOBILE RESPONSIVENESS TWEAKS
           ========================================================================== */
        @media (max-width: 768px) {
            .app-container {
                height: 100vh;
                border-radius: 0;
                border: none;
                max-width: 100%;
            }

            .top-navbar {
                height: 60px;
                padding: 0 1rem;
            }

            .navbar-center {
                display: none;
            }

            .app-body {
                grid-template-columns: 1fr;
                height: calc(100% - 60px);
            }

            aside {
                display: none;
            }

            main {
                padding: 1rem;
            }

            .page-header-row {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .page-controls-container {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                gap: 0.75rem;
            }

            .page-controls-container .search-bar-container {
                width: 100% !important;
            }

            .search-bar-container {
                max-width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>

<body>

    <div class="app-container">
        <!-- TOP NAVBAR REMOVED -->

        <div class="app-body">
            <!-- SIDEBAR NAVIGATION -->
            <aside>
                <div class="sidebar-branding">
                    <div class="logo-container">
                        <div class="logo-icon">
                            <i data-lucide="leaf"></i>
                        </div>
                        <div class="logo-text">
                            <h1>Sustaina</h1>
                            <span>Prevent Food Waste With Ai</span>
                        </div>
                    </div>
                </div>

                <div class="sidebar-header-box">
                    <h3 class="sidebar-header-title">Logistics Center</h3>
                    <p class="sidebar-header-desc">Manage the food stocks and track local surplus donations.</p>
                </div>

                <div class="sidebar-nav-cards">
                    <a href="index.php?page=dashboard"
                        class="sidebar-card-link <?= $page == 'dashboard' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="home"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">Homepage</span>
                            <span class="sidebar-card-desc">Where Everthing Starts</span>
                        </div>
                    </a>

                    <a href="index.php?page=inventory"
                        class="sidebar-card-link <?= $page == 'inventory' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="box"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">Raw Inventory</span>
                            <span class="sidebar-card-desc">Internal Food Stock Database</span>
                        </div>
                    </a>

                    <a href="index.php?page=ai-assistant"
                        class="sidebar-card-link <?= $page == 'ai-assistant' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="chef-hat"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">AI Recipe Assistant</span>
                            <span class="sidebar-card-desc">Smart Recipe Gen</span>
                        </div>
                    </a>
                    
                    <a href="index.php?page=marketplace"
                        class="sidebar-card-link <?= $page == 'marketplace' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="store"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">Marketplace Feed</span>
                            <span class="sidebar-card-desc">Surplus Raw Market Listings</span>
                        </div>
                    </a>

                    

                    <a href="index.php?page=messages"
                        class="sidebar-card-link <?= $page == 'messages' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="message-square"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">Messages</span>
                            <span class="sidebar-card-desc">Chat with Sellers</span>
                        </div>
                    </a>

                    <a href="index.php?page=geofence"
                        class="sidebar-card-link <?= $page == 'geofence' ? 'active' : '' ?>">
                        <div class="sidebar-card-icon">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="sidebar-card-details">
                            <span class="sidebar-card-title">Geofence Map</span>
                            <span class="sidebar-card-desc">Track Location & Surplus</span>
                        </div>
                    </a>
                </div>

                <div class="sidebar-footer">
                    <a href="index.php?page=profile" class="user-profile-badge"
                        style="text-decoration: none; cursor: pointer; border-radius: var(--radius-md); transition: var(--transition-fast); display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; margin: -0.5rem;">
                        <div class="user-avatar">
                            <?= isset($_SESSION['user_full_name']) && $_SESSION['user_full_name'] ? strtoupper(substr($_SESSION['user_full_name'], 0, 1)) : (isset($_SESSION['user_email']) ? strtoupper(substr($_SESSION['user_email'], 0, 1)) : 'U') ?>
                        </div>
                        <div class="user-info">
                            <span
                                class="user-name"><?= htmlspecialchars($_SESSION['user_full_name'] ?? explode('@', $_SESSION['user_email'] ?? 'Guest')[0]) ?></span>
                            <span
                                class="user-role"><?= htmlspecialchars($_SESSION['user_role'] ?? $_SESSION['user_email'] ?? 'No Email') ?></span>
                        </div>
                    </a>
                    <!-- Logout Button -->
                    <div class="profile-logout" style="margin-top: 1.5rem; display: flex; justify-content: center;">
                        <form method="POST" action="actions.php" style="margin: 0; width: 100%;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-logout"
                                style="width: 100%; border-radius: var(--radius-sm); padding: 0.6rem; font-size: 0.8rem; gap: 0.4rem;">
                                <i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT AREA -->
            <main>
                <!-- DYNAMIC PAGE HEADER ROW (AS SEEN IN SCREENSHOT) -->
                <div class="page-header-row">
                    <div class="page-title-container">
                        <div class="page-title-icon">
                            <i data-lucide="<?= $current_meta['icon'] ?>"></i>
                        </div>
                        <h2 class="page-title"><?= $current_meta['title'] ?></h2>
                    </div>

                    <?php if ($page !== 'profile'): ?>
                        <div class="page-controls-container">
                            

                            <?php if ($page === 'marketplace'): ?>
                                <?php
                                $active_cat = isset($_GET['cat']) ? $_GET['cat'] : 'All';
                                ?>

                            <?php endif; ?>

                            <button class="btn btn-primary" id="open-add-item-modal">
                                <i data-lucide="plus"></i>
                                Post Raw Item
                            </button>

                        </div>
                    <?php endif; ?>
                </div>

                <!-- Page view rendered from separate module files -->
                <div class="view-section active">
                    <?php include "modules/{$page}.php"; ?>
                </div>
            </main>
        </div>

        <!-- ==========================================
         ADD NEW RAW ITEM MODAL (SUBMITS TO actions.php)
         ========================================== -->
        <div class="modal-overlay" id="add-item-modal">
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i data-lucide="plus-circle"></i>
                        Post Raw Food Item
                    </h3>
                    <button class="modal-close" id="close-add-item-modal">&times;</button>
                </div>
                <form id="add-item-form" method="POST" action="actions.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_item">
                    <div class="modal-body">
                        <div class="form-grid">
                            <!-- Item Name -->
                            <div class="form-group full-width">
                                <label for="item-name">Raw Item Name <span class="required-star">*</span></label>
                                <input type="text" id="item-name" name="item-name"
                                    placeholder="e.g. Ribeye Steak, Organic Tomatoes" required>
                            </div>

                            <!-- Category Selection -->
                            <div class="form-group">
                                <label for="item-category">Category <span class="required-star">*</span></label>
                                <select id="item-category" name="item-category" required>
                                    <option value="" disabled selected>Select category</option>
                                    <option value="Meat">Meat & Poultry</option>
                                    <option value="Vegetables">Vegetables</option>
                                    <option value="Fruits">Fruits</option>
                                    <option value="Dairy">Dairy & Eggs</option>
                                    <option value="Bakery">Bakery / Flour</option>
                                    <option value="Other">Other Raw Item</option>
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div class="form-group">
                                <label for="item-qty">Quantity / Weight <span class="required-star">*</span></label>
                                <input type="text" id="item-qty" name="item-qty" placeholder="e.g. 2.5 kg, 6 pieces"
                                    required>
                            </div>

                            <!-- CONDITIONAL FIELD: MEAT PURCHASING DATE -->
                            <div class="conditional-field-wrapper" id="meat-conditional-fields">
                                <div class="form-group full-width" style="margin-bottom: 0;">
                                    <label for="item-bought-date" style="color: var(--color-meat);">
                                        <i data-lucide="calendar"></i> Date Bought / Purchased <span
                                            class="required-star">*</span>
                                    </label>
                                    <input type="date" id="item-bought-date" name="item-bought-date">
                                    <span
                                        style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem; display: block;">
                                        Meat items require a purchase date to model bacterial growth and freshness
                                        profiles.
                                    </span>
                                </div>
                            </div>

                            <!-- Expiry Date -->
                            <div class="form-group full-width">
                                <label for="item-expiry">Expiry Date <span class="required-star">*</span></label>
                                <input type="date" id="item-expiry" name="item-expiry" required>
                            </div>

                            <!-- Image Upload -->
                            <div class="form-group full-width">
                                <label for="item-image">Upload Image (Optional)</label>
                                <input type="file" id="item-image" name="item-image" accept="image/*"
                                    style="background: white; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                            </div>

                            <!-- AI Freshness Estimator Preview Box -->
                            <div class="ai-helper-preview" id="ai-helper-box">
                                <div class="ai-preview-icon">
                                    <i data-lucide="cpu"></i>
                                </div>
                                <div class="ai-preview-content">
                                    <div class="ai-preview-title">AI Freshness Estimator</div>
                                    <div class="ai-preview-text" id="ai-estimator-text">
                                        Fill in the category and expiry date. Sustaina's AI will forecast the item's
                                        safety timeline and conservation value.
                                    </div>
                                </div>
                            </div>

                            <!-- Facebook Marketplace Listing Toggle -->
                            <div class="toggle-switch-container">
                                <div class="toggle-info">
                                    <span class="toggle-label">List on Surplus Marketplace</span>
                                    <span class="toggle-sub">Make it visible to nearby buyers on the feed</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="list-on-market" name="list-on-market">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- Conditional Marketplace Details -->
                            <div class="marketplace-form-fields" id="market-details-fields">
                                <div class="form-group">
                                    <label for="market-price">Listing Price ($) <span
                                            class="required-star">*</span></label>
                                    <input type="text" id="market-price" name="market-price"
                                        placeholder="e.g. 5.00 (Enter 0 for Free)">
                                </div>
                                <div class="form-group">
                                    <label for="market-location">Your Location <span
                                            class="required-star">*</span></label>
                                    <input type="text" id="market-location" name="market-location"
                                        value="Bella Grillhouse, Downtown">
                                </div>
                                <div class="form-group full-width">
                                    <label for="market-description">Surplus Description</label>
                                    <textarea id="market-description" name="market-description"
                                        placeholder="Specify storage instructions or pickup times..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancel-add-item">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Raw Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TOAST NOTIFICATION CONTAINER -->
        <div id="toast-container"></div>

        <!-- Embedded Javascript Controller -->
        <script>
            // Global state variables
            let chartInstance = null;

            // Carbon impact values per kg by category (AI metrics)
            const CO2_FACTORS = {
                "Meat": 27.0,       // High impact
                "Vegetables": 2.0,  // Low impact
                "Fruits": 2.5,
                "Dairy": 8.0,       // Medium impact
                "Bakery": 3.0,
                "Other": 4.5
            };

            // ==========================================================================
            // APP INITIALIZATION
            // ==========================================================================
            function initApp() {
                setupModalListeners();
                setupSearchFilter();
                setupChatListeners();
                initChart();

                // Lucide Icons initialization
                if (window.lucide) {
                    lucide.createIcons();
                }
            }

            // ==========================================================================
            // TOAST NOTIFICATIONS
            // ==========================================================================
            function showToast(title, message, type = "success") {
                const container = document.getElementById("toast-container");
                if (!container) return;

                const toast = document.createElement("div");
                toast.className = `toast ${type}`;

                let icon = "check-circle";
                if (type === "warning") icon = "alert-triangle";
                if (type === "danger") icon = "x-circle";
                if (type === "info") icon = "info";

                toast.innerHTML = `
                <div class="toast-icon">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="toast-body">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <div class="toast-close">&times;</div>
            `;

                container.appendChild(toast);

                if (window.lucide) {
                    lucide.createIcons();
                }

                // Auto-remove toast
                const autoRemove = setTimeout(() => {
                    dismissToast(toast);
                }, 4500);

                // Manual dismiss
                toast.querySelector(".toast-close").addEventListener("click", () => {
                    clearTimeout(autoRemove);
                    dismissToast(toast);
                });
            }

            function dismissToast(toast) {
                toast.style.opacity = "0";
                toast.style.transform = "translateY(10px)";
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }

            function filterByCategory(cat) {
                const currentPage = '<?= $page ?>';
                if (currentPage === 'inventory' || currentPage === 'marketplace') {
                    window.location.href = 'index.php?page=' + currentPage + '&cat=' + cat;
                }
            }

            // ==========================================================================
            // MODAL DYNAMICS & EVENT LISTENERS
            // ==========================================================================
            function setupModalListeners() {
                const modal = document.getElementById("add-item-modal");
                const openBtn = document.getElementById("open-add-item-modal");
                const closeBtn = document.getElementById("close-add-item-modal");
                const cancelBtn = document.getElementById("cancel-add-item");
                const form = document.getElementById("add-item-form");

                if (!modal || !openBtn) return;

                const categorySelect = document.getElementById("item-category");
                const marketToggle = document.getElementById("list-on-market");

                // Open Modal
                openBtn.addEventListener("click", () => {
                    form.reset();
                    document.getElementById("meat-conditional-fields").classList.remove("active");
                    document.getElementById("item-bought-date").required = false;
                    document.getElementById("market-details-fields").classList.remove("active");
                    updateFormAIEstimate();
                    modal.classList.add("active");
                });

                // Close Modal actions
                const closeModal = () => modal.classList.remove("active");
                closeBtn.addEventListener("click", closeModal);
                cancelBtn.addEventListener("click", closeModal);

                // Conditional Meat bought date logic
                categorySelect.addEventListener("change", function () {
                    const meatFields = document.getElementById("meat-conditional-fields");
                    const boughtInput = document.getElementById("item-bought-date");

                    if (this.value === "Meat") {
                        meatFields.classList.add("active");
                        boughtInput.required = true;
                        // Set max date as today
                        boughtInput.max = "2026-05-20";
                    } else {
                        meatFields.classList.remove("active");
                        boughtInput.required = false;
                        boughtInput.value = "";
                    }
                    updateFormAIEstimate();
                });

                // Conditional Marketplace fields logic
                marketToggle.addEventListener("change", function () {
                    const marketFields = document.getElementById("market-details-fields");
                    if (this.checked) {
                        marketFields.classList.add("active");
                        document.getElementById("market-price").required = true;
                    } else {
                        marketFields.classList.remove("active");
                        document.getElementById("market-price").required = false;
                    }
                });

                // Listen to form input changes to trigger real-time AI estimation updates
                document.getElementById("item-name").addEventListener("input", updateFormAIEstimate);
                document.getElementById("item-qty").addEventListener("input", updateFormAIEstimate);
                document.getElementById("item-expiry").addEventListener("change", updateFormAIEstimate);
                document.getElementById("item-bought-date").addEventListener("change", updateFormAIEstimate);
            }

            // Client-side parser to estimate kg weights from input
            function parseQtyToKg(qtyStr) {
                const val = parseFloat(qtyStr);
                if (isNaN(val)) return 1.0;
                if (qtyStr.toLowerCase().includes("kg") || qtyStr.toLowerCase().includes("kilogram")) {
                    return val;
                }
                if (qtyStr.toLowerCase().includes("liter") || qtyStr.toLowerCase().includes("l")) {
                    return val;
                }
                return val * 0.15; // Assume 150g average weight per piece/unit
            }

            // Real-time AI estimator calculations inside modal
            function updateFormAIEstimate() {
                const category = document.getElementById("item-category").value;
                const expiryDateStr = document.getElementById("item-expiry").value;
                const boughtDateStr = document.getElementById("item-bought-date").value;
                const qtyStr = document.getElementById("item-qty").value;
                const textBox = document.getElementById("ai-estimator-text");

                if (!textBox) return;

                if (!category) {
                    textBox.innerHTML = "Fill in the category and details. Sustaina's AI will forecast the item's safety timeline and conservation value.";
                    return;
                }

                let estimateMsg = "";

                // Carbon potential calculation
                const qtyVal = parseQtyToKg(qtyStr);
                const co2Val = (qtyVal * (CO2_FACTORS[category] || 4.5)).toFixed(1);

                if (category === "Meat") {
                    if (!boughtDateStr || !expiryDateStr) {
                        estimateMsg = `<strong>High Value Protein:</strong> Saving this item will prevent an estimated <strong>${co2Val} kg CO2eq</strong> emissions. Please enter Purchased & Expiry Dates to get a freshness shelf-life safety curve.`;
                    } else {
                        const today = new Date("2026-05-20");
                        const bought = new Date(boughtDateStr);
                        const expiry = new Date(expiryDateStr);

                        const totalDays = Math.ceil((expiry - bought) / (1000 * 60 * 60 * 24));
                        const ageDays = Math.ceil((today - bought) / (1000 * 60 * 60 * 24));
                        const remainingDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

                        if (remainingDays < 0) {
                            estimateMsg = `<span style="color:var(--danger);"><strong>AI Safety Alert:</strong> This meat has already reached its expiration date and poses bacterial hazard. Do not serve.</span>`;
                        } else {
                            const healthIndex = Math.max(0, Math.round(((totalDays - ageDays) / totalDays) * 100));
                            let color = "var(--primary)";
                            if (healthIndex < 40) color = "var(--warning)";
                            if (healthIndex < 15) color = "var(--danger)";

                            estimateMsg = `
                            <strong>AI Safety Model:</strong> Raw meat holds a <strong>${healthIndex}% fresh-curve index</strong>. 
                            Has been stored for ${ageDays} days. Safe shelf-life remaining: <strong>${remainingDays} days</strong>.
                            <br><span style="color:${color}; font-weight:700;">Rec: Store strictly at < 2Â°C or cook within 48h. Wasting this wastes ${co2Val}kg of carbon equivalent.</span>
                        `;
                        }
                    }
                } else {
                    // Other items
                    if (!expiryDateStr) {
                        estimateMsg = `Saving this ${category} item offsets approx <strong>${co2Val} kg CO2eq</strong>. Enter expiry date to generate preservation guide.`;
                    } else {
                        const expiry = new Date(expiryDateStr);
                        const today = new Date("2026-05-20");
                        const remaining = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

                        if (remaining < 0) {
                            estimateMsg = `<span style="color:var(--danger);"><strong>Wasted:</strong> Exceeded recommended shelf life. Rec: Compost.</span>`;
                        } else if (remaining <= 2) {
                            estimateMsg = `
                            <strong>Preservation alert:</strong> ${remaining} days left. 
                            <br><strong>Tip:</strong> SautÃ© and store or dehydrate. Prevent <strong>${co2Val} kg CO2</strong> loss by listing on marketplace today.
                        `;
                        } else {
                            estimateMsg = `
                            <strong>Stable Shelf-Life:</strong> Healthy timeline of ${remaining} days. 
                            Store in ventilated glass container. Prevents <strong>${co2Val} kg CO2</strong> from food fill.
                        `;
                        }
                    }
                }

                textBox.innerHTML = estimateMsg;
            }

            // Helper to format date elegantly
            function formatDate(dateStr) {
                if (!dateStr) return "";
                const date = new Date(dateStr);
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
            }

            // Quick listing helper from AI cards
            window.quickMarketListPrompt = function (id) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions.php';

                const actInput = document.createElement('input');
                actInput.type = 'hidden';
                actInput.name = 'action';
                actInput.value = 'quick_list';
                form.appendChild(actInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);

                const priceInput = document.createElement('input');
                priceInput.type = 'hidden';
                priceInput.name = 'price';
                priceInput.value = '0.00';
                form.appendChild(priceInput);

                document.body.appendChild(form);
                form.submit();
            };

            // ==========================================================================
            // CHARTS AND ANALYTICS (LIGHT ORGANIC STYLE)
            // ==========================================================================
            function initChart() {
                const ctx = document.getElementById('foodWasteChart');
                if (!ctx) return;

                if (chartInstance) {
                    chartInstance.destroy();
                }

                const ctx2d = ctx.getContext('2d');

                let savedData = [12, 18, 25, 34, 42, 49];
                let wastedData = [28, 24, 19, 15, 9, 4];

                const chartDataEl = document.getElementById('chart-data');
                if (chartDataEl) {
                    try {
                        const parsed = JSON.parse(chartDataEl.innerText);
                        if (parsed.saved) savedData = parsed.saved;
                        if (parsed.wasted) wastedData = parsed.wasted;
                    } catch (e) {
                        console.error("Failed to parse PHP chart coordinates.", e);
                    }
                }

                const gradientSaved = ctx2d.createLinearGradient(0, 0, 0, 180);
                gradientSaved.addColorStop(0, 'rgba(34, 63, 53, 0.2)');
                gradientSaved.addColorStop(1, 'rgba(34, 63, 53, 0.0)');

                const gradientWasted = ctx2d.createLinearGradient(0, 0, 0, 180);
                gradientWasted.addColorStop(0, 'rgba(217, 119, 6, 0.15)');
                gradientWasted.addColorStop(1, 'rgba(217, 119, 6, 0.0)');

                chartInstance = new Chart(ctx2d, {
                    type: 'line',
                    data: {
                        labels: ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May (YTD)'],
                        datasets: [
                            {
                                label: 'Surplus Saved (kg)',
                                data: savedData,
                                borderColor: '#223f35',
                                backgroundColor: gradientSaved,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#1c332c',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4
                            },
                            {
                                label: 'Discarded Waste (kg)',
                                data: wastedData,
                                borderColor: '#d97706',
                                backgroundColor: gradientWasted,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#b45309',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#40514a',
                                    font: {
                                        family: 'Plus Jakarta Sans',
                                        size: 11,
                                        weight: '600'
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: '#cbd3cb'
                                },
                                ticks: {
                                    color: '#798680',
                                    font: {
                                        family: 'Plus Jakarta Sans',
                                        size: 10,
                                        weight: '500'
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    color: '#cbd3cb'
                                },
                                ticks: {
                                    color: '#798680',
                                    font: {
                                        family: 'Plus Jakarta Sans',
                                        size: 10,
                                        weight: '500'
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ==========================================================================
            // SEARCH FILTERS
            // ==========================================================================
            function setupSearchFilter() {
                const searchInput = document.getElementById("global-search");
                if (!searchInput) return;

                searchInput.addEventListener("input", function () {
                    const query = this.value.toLowerCase().trim();

                    // Filter table rows
                    const tableRows = document.querySelectorAll("tbody tr");
                    tableRows.forEach(row => {
                        if (row.cells.length === 1 && row.cells[0].colSpan > 1) return;

                        const nameEl = row.querySelector(".food-name");
                        const catEl = row.querySelector(".badge");

                        const text = (nameEl ? nameEl.innerText : "") + " " + (catEl ? catEl.innerText : "");
                        if (text.toLowerCase().includes(query)) {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    });

                    // Filter marketplace grid cards
                    const marketCards = document.querySelectorAll(".market-card");
                    marketCards.forEach(card => {
                        const titleEl = card.querySelector(".market-item-title");
                        const descEl = card.querySelector(".market-description");
                        const sellerEl = card.querySelector(".market-seller-row");
                        const badgeEl = card.querySelector(".market-card-badge");

                        const text = (titleEl ? titleEl.innerText : "") + " " +
                            (descEl ? descEl.innerText : "") + " " +
                            (sellerEl ? sellerEl.innerText : "") + " " +
                            (badgeEl ? badgeEl.innerText : "");

                        if (text.toLowerCase().includes(query)) {
                            card.style.display = "";
                        } else {
                            card.style.display = "none";
                        }
                    });
                });
            }

            // ==========================================================================
            // AI ASSISTANT CHAT ENGINE
            // ==========================================================================
            function setupChatListeners() {
                const sendBtn = document.getElementById("chat-send-btn");
                const chatInput = document.getElementById("chat-input");

                if (!sendBtn || !chatInput) return;

                sendBtn.addEventListener("click", handleChatSubmit);
                chatInput.addEventListener("keypress", function (e) {
                    if (e.key === "Enter") handleChatSubmit();
                });
            }

            function handleChatSubmit() {
                const input = document.getElementById("chat-input");
                const text = input.value.trim();
                if (!text) return;

                appendChatMessage("user", text);
                input.value = "";

                showLoadingResponse();

                setTimeout(() => {
                    removeLoadingResponse();
                    processAIResponse(text);
                }, 1000);
            }

            function appendChatMessage(sender, text) {
                const history = document.getElementById("chat-history");
                if (!history) return;

                const msg = document.createElement("div");
                msg.className = `chat-msg ${sender}`;

                const senderName = sender === "user" ? "You" : "AI Copilot";

                msg.innerHTML = `
                <span class="chat-msg-sender">${senderName}</span>
                <div>${text}</div>
            `;

                history.appendChild(msg);
                history.scrollTop = history.scrollHeight;
            }

            function showLoadingResponse() {
                const history = document.getElementById("chat-history");
                if (!history) return;

                const msg = document.createElement("div");
                msg.className = "chat-msg assistant";
                msg.id = "chat-loading-placeholder";
                msg.innerHTML = `
                <span class="chat-msg-sender">AI Copilot</span>
                <div>AI is calculating safety curves...</div>
            `;
                history.appendChild(msg);
                history.scrollTop = history.scrollHeight;
            }

            function removeLoadingResponse() {
                const loader = document.getElementById("chat-loading-placeholder");
                if (loader) loader.remove();
            }

            function processAIResponse(query) {
                let reply = "";
                const lowerQuery = query.toLowerCase();

                const nearestItemRow = document.querySelector("tbody tr");
                let nearestItemName = "none";
                let nearestItemExpiry = "N/A";
                if (nearestItemRow) {
                    const nameEl = nearestItemRow.querySelector(".food-name");
                    const expEl = nearestItemRow.querySelector(".badge-danger, .badge-warning, .badge-fresh");
                    if (nameEl) nearestItemName = nameEl.innerText;
                    if (expEl) nearestItemExpiry = expEl.innerText;
                }

                if (lowerQuery.includes("recipe") || lowerQuery.includes("cook") || lowerQuery.includes("meal")) {
                    reply = `Based on your active inventory ingredients (like spinach, milk, and strawberries), here is an optimized recipe to prevent food waste:<br><br>
                <strong>ðŸ³ Proposed Recipe: Spinach & Cheese Breakfast Omelette</strong><br>
                â€¢ <em>Ingredients used:</em> Spinach, Milk, Eggs, Cheese<br>
                â€¢ <em>Instructions:</em> Whisk eggs with a splash of milk. SautÃ© spinach in butter until wilted. Pour eggs over, cook until firm, top with cheese and fold.<br><br>
                <em>Saving this recipe prevents approximately 1.5 kg of CO2 equivalent.</em>`;
                }
                else if (lowerQuery.includes("meat") || lowerQuery.includes("bought") || lowerQuery.includes("chicken") || lowerQuery.includes("beef")) {
                    reply = `<strong>AI Raw Meat Safety Guidelines:</strong><br>
                1. Always record the 'Date Bought' as required for meats in your inventory. Raw poultry/beef is safe for 3-5 days in the fridge (0-4Â°C).<br>
                2. If you cannot cook the raw meat before its expiry, post it on the **Surplus Marketplace** immediately or freeze it to stop bacterial multiplying. When freezing, label it with the remaining shelf-life days so you know when you defrost it.`;
                }
                else if (lowerQuery.includes("save") || lowerQuery.includes("carbon") || lowerQuery.includes("co2")) {
                    reply = `By successfully preventing waste on your tracked items, you prevent carbon emissions from landfill decomposition. Animal proteins (Meats) represent the single largest carbon source, so prioritizing them is key. For example, saving 1kg of beef prevents <strong>27.0 kg of CO2 equivalent</strong> from being wasted.`;
                }
                else if (lowerQuery.includes("how to tell") || lowerQuery.includes("milk") || lowerQuery.includes("spoil")) {
                    reply = `<strong>AI Freshness Diagnostics for Dairy:</strong><br>
                â€¢ <strong>Visual check:</strong> Pour milk into a clear glass. Check for clumping, texture curdles, or separation.<br>
                â€¢ <strong>Smell check:</strong> Sour odors indicate high lactic acid from bacteria. If it smells clean but is near date, it is likely safe to cook or pasteurize again in pancakes or baking.<br>
                â€¢ <em>Note:</em> Raw milk spoils faster than pasteurized or UHT milk.`;
                }
                else {
                    reply = `I've analyzed your query regarding "${query}". Sustaina AI advises monitoring your nearest expiry item, which is <strong>${nearestItemName}</strong> (${nearestItemExpiry}). Let me know if you would like me to draft a marketplace post with a calculated discount for it.`;
                }

                appendChatMessage("assistant", reply);
            }

            // External simulator callers
            window.simulateAIChat = function (promptText) {
                if (!window.location.search.includes("page=ai-assistant")) {
                    window.location.href = "index.php?page=ai-assistant&chat_trigger=" + encodeURIComponent(promptText);
                    return;
                }
                const input = document.getElementById("chat-input");
                if (input) {
                    input.value = promptText;
                    handleChatSubmit();
                }
            };

            window.simulateAIScan = function () {
                showToast("AI Inventory Scan", "Scanned all food safety tables. Deep learning model shows 100% data integrity.", "success");
            };

            function checkChatTrigger() {
                const urlParams = new URLSearchParams(window.location.search);
                const chatTrigger = urlParams.get('chat_trigger');
                if (chatTrigger && window.location.search.includes("page=ai-assistant")) {
                    setTimeout(() => {
                        const input = document.getElementById("chat-input");
                        if (input) {
                            input.value = chatTrigger;
                            handleChatSubmit();
                        }
                    }, 300);
                }
            }

            // Start application
            window.addEventListener("DOMContentLoaded", () => {
                initApp();
                checkChatTrigger();
            });
        </script>

        <!-- Trigger Session Toast Notifications -->
        <?php if (isset($_SESSION['toast'])): ?>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    showToast(
                        "<?= addslashes($_SESSION['toast']['title']) ?>",
                        "<?= addslashes($_SESSION['toast']['message']) ?>",
                        "<?= $_SESSION['toast']['type'] ?>"
                    );
                });
            </script>
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
</body>

</html>
