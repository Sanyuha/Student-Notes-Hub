<?php
// Unified header component for Student Notes Hub
// This file should be included at the top of every PHP page

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default page title if not defined
if (!isset($pageTitle)) {
    $pageTitle = 'Student Notes Hub';
}

// Determine current page for active states
$currentPage = basename($_SERVER['PHP_SELF']);

// Get user info if logged in
$isLoggedIn = isset($_SESSION['auth_user_id']);
$userName   = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : '';
$userAvatar = $isLoggedIn ? ($_SESSION['avatar_url'] ?? 'https://via.placeholder.com/40 ') : '';

// Get categories for navigation if needed
$navCategories = [];
if (class_exists('PDO') && isset($pdo)) {
    try {
        $navCategories = $pdo->query('SELECT slug, name, icon FROM categories ORDER BY name')
                              ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Silent fail - categories won't be shown in nav
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Student Notes Hub - Share knowledge, learn together, and excel in your studies">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" type="image/png" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%236366f1'/%3E%3Cstop offset='100%25' style='stop-color:%234f46e5'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23g)'/%3E%3Cg transform='translate(50,50)'%3E%3Cpath d='M -30 -15 L 30 -15 L 25 -5 L -25 -5 Z' fill='white' opacity='0.9'/%3E%3Cellipse cx='0' cy='-15' rx='30' ry='8' fill='white' opacity='0.8'/%3E%3Cline x1='25' y1='-5' x2='25' y2='5' stroke='white' stroke-width='2' opacity='0.9'/%3E%3Ccircle cx='25' cy='5' r='2' fill='white' opacity='0.9'/%3E%3C/g%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="favicon.svg">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300 ;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
    
    <!-- Prevent Flash of Light Mode -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    <!-- Core CSS Variables -->
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --text-light: #f9fafb;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-dark: #111827;
            --bg-light: #f3f4f6;
            
            --border-color: #e5e7eb;
            --border-light: #f3f4f6;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
            
            --container-max-width: 1200px;
            --navbar-height: 70px;
        }
        
        /* Dark Mode Variables */
        [data-theme="dark"] {
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --text-light: #f9fafb;
            
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-dark: #0f172a;
            --bg-light: #374151;
            
            --border-color: #374151;
            --border-light: #4b5563;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.6);
        }
        
        /* Smooth transition for theme changes */
        html, body, * {
            transition: background-color var(--transition-normal), 
                        color var(--transition-normal),
                        border-color var(--transition-normal) !important;
        }
        
        /* Exclude certain elements from transitions */
        img, svg, video, iframe, canvas {
            transition: none !important;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            padding-top: var(--navbar-height);
        }
        
        a {
            text-decoration: none;
            color: inherit;
            transition: color var(--transition-fast);
        }
        
        a:hover {
            color: var(--primary-color);
        }
        
        img {
            max-width: 100%;
            display: block;
        }
        
        ul, ol {
            list-style: none;
        }
        
        .container {
            width: 90%;
            max-width: var(--container-max-width);
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            font-family: inherit;
        }
        
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }
        
        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--bg-primary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-danger {
            background: var(--error-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Navigation Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: var(--navbar-height);
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(31, 41, 55, 0.95);
            border-bottom-color: var(--border-color);
        }
        
        .nav-container {
            max-width: var(--container-max-width);
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--text-secondary);
            padding: 0.5rem 0;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-link.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color);
            border-radius: 1px;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-bar {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input {
            padding: 0.5rem 1rem;
            padding-right: 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            width: 200px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all var(--transition-normal);
        }
        
        [data-theme="dark"] .search-input {
            background: var(--bg-light);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .search-input::placeholder {
            color: var(--text-muted);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            width: 250px;
        }
        
        .search-btn {
            position: absolute;
            right: 0.5rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .search-btn:hover {
            color: var(--primary-color);
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .theme-toggle:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        [data-theme="dark"] .theme-toggle {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .theme-toggle:hover {
            background: var(--bg-light);
            color: var(--primary-light);
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .user-avatar-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            border: 2px solid var(--border-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--error-color);
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            line-height: 1.4;
        }
        
        .notification-menu {
            padding: 0;
        }
        
        /* Hide scrollbar but keep scrolling functionality */
        .notification-menu::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        .notification-menu {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: background-color var(--transition-fast);
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }
        
        .notification-item:hover {
            background: var(--bg-light);
        }
        
        .notification-item.unread {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .notification-item.unread:hover {
            background: rgba(99, 102, 241, 0.1);
        }
        
        .notification-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .notification-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-message {
            color: var(--text-primary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }
        
        .notification-time {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        .notification-icon {
            color: var(--primary-color);
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.25rem;
        }
        
        .no-notifications {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
        }
        
        .user-name {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color var(--transition-fast);
            position: relative;
        }
        
        .dropdown-toggle:hover {
            background: var(--bg-light);
        }
        
        #notificationDropdown .dropdown-toggle {
            font-size: 1.25rem;
            color: var(--text-secondary);
        }
        
        #notificationDropdown .dropdown-toggle:hover {
            color: var(--primary-color);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            min-width: 180px;
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all var(--transition-normal);
        }
        
        [data-theme="dark"] .dropdown-menu {
            background: var(--bg-primary);
            border-color: var(--border-color);
        }
        
        .dropdown-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            transition: background-color var(--transition-fast);
        }
        
        .dropdown-item:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        .dropdown-divider {
            height: 1px;
            background: var(--border-color);
            margin: 0.5rem 0;
        }
        
        /* Mobile Navigation */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color var(--transition-fast);
        }
        
        .mobile-menu-toggle:hover {
            background: var(--bg-light);
        }
        
        /* Responsive Design */
        .mobile-search-toggle {
            display: none;
        }
        
        .mobile-search-toggle:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 0.75rem;
                justify-content: space-between;
                gap: 0.5rem;
            }
            
            .nav-logo {
                font-size: 1.25rem;
                flex: 0 0 auto;
                position: relative;
                left: auto;
                transform: none;
                z-index: 1;
            }
            
            .nav-logo span {
                display: none;
            }
            
            .nav-menu {
                display: none !important;
                position: fixed;
                top: var(--navbar-height);
                left: 0;
                right: 0;
                bottom: 0;
                background: var(--bg-primary);
                border-top: 1px solid var(--border-color);
                box-shadow: var(--shadow-lg);
                flex-direction: column;
                padding: 1.5rem 1rem;
                gap: 0.5rem;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 1000;
                width: 100vw;
                max-width: 100vw;
                height: calc(100vh - var(--navbar-height));
                min-height: calc(100vh - var(--navbar-height));
            }
            
            [data-theme="dark"] .nav-menu {
                background: var(--bg-primary);
                border-top-color: var(--border-color);
            }
            
            .nav-menu.active {
                display: flex !important;
            }
            
            .nav-link {
                padding: 1rem 1.5rem;
                border-radius: var(--border-radius);
                font-size: 1rem;
                display: flex !important;
                align-items: center;
                gap: 0.75rem;
                width: 100%;
                max-width: 100%;
                transition: all var(--transition-fast);
                color: var(--text-primary) !important;
                text-decoration: none;
                white-space: nowrap;
                box-sizing: border-box;
            }
            
            .nav-link:hover,
            .nav-link:active,
            .nav-link:focus {
                background: var(--bg-light);
                color: var(--primary-color);
            }
            
            .nav-link i {
                width: 20px;
                text-align: center;
                font-size: 1.125rem;
            }
            
            .nav-link i {
                width: 20px;
                text-align: center;
                font-size: 1.125rem;
            }
            
            .nav-link i {
                width: 20px;
                text-align: center;
            }
            
            .mobile-menu-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                z-index: 1001;
                position: relative;
            }
            
            .nav-actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                justify-content: flex-end;
                flex: 1;
                min-width: 0;
            }
            
            .search-bar {
                display: none;
            }
            
            .mobile-search-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
            }
            
            .mobile-menu-toggle {
                margin-left: 0.5rem;
                flex-shrink: 0;
            }
            
            .theme-toggle {
                width: 36px;
                height: 36px;
                font-size: 1.125rem;
            }
            
            .dropdown-toggle {
                padding: 0.5rem;
            }
            
            .user-avatar,
            .user-avatar-placeholder {
                width: 28px;
                height: 28px;
            }
            
            .dropdown-menu {
                right: 0;
                left: auto;
                min-width: 200px;
                max-width: calc(100vw - 2rem);
            }
            
            .notification-menu {
                right: 0;
                left: auto;
                min-width: 300px;
                max-width: calc(100vw - 2rem);
            }
            
            #notificationContainer {
                top: 80px;
                right: 10px;
                left: 10px;
                max-width: calc(100vw - 20px);
            }
            
            .notification {
                padding: 0.875rem 1.25rem;
                font-size: 0.9375rem;
            }
        }
        
        @media (max-width: 480px) {
            .nav-container {
                padding: 0 0.5rem;
                justify-content: flex-start;
                gap: 0.75rem;
            }
            
            .nav-logo {
                font-size: 1.125rem;
                flex: 0 0 auto;
                position: relative;
                left: auto;
                transform: none;
                z-index: 1;
            }
            
            .user-name {
                display: none;
            }
            
            .nav-actions {
                gap: 0.25rem;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex: 1;
                min-width: 0;
            }
            
            .mobile-menu-toggle {
                margin-left: 0.5rem;
                flex-shrink: 0;
            }
            
            .theme-toggle {
                width: 32px;
                height: 32px;
                font-size: 1rem;
                padding: 0.375rem;
            }
            
            .dropdown-toggle {
                padding: 0.375rem;
            }
            
            .user-avatar,
            .user-avatar-placeholder {
                width: 24px;
                height: 24px;
            }
            
            .dropdown-menu {
                min-width: 180px;
                max-width: calc(100vw - 1rem);
            }
            
            .notification-menu {
                min-width: 280px;
                max-width: calc(100vw - 1rem);
            }
            
            #notificationContainer {
                top: 75px;
                right: 5px;
                left: 5px;
                max-width: calc(100vw - 10px);
            }
            
            .notification {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
            
            .notification-item {
                padding: 0.875rem;
            }
            
            .notification-avatar,
            .notification-avatar-placeholder {
                width: 36px;
                height: 36px;
            }
        }
        
        /* Loading and Notification Styles */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-width: 400px;
            pointer-events: none;
        }
        
        .notification {
            position: relative;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease;
            box-shadow: var(--shadow-lg);
            pointer-events: auto;
            margin-bottom: 0;
        }
        
        .notification-success { background: var(--success-color); }
        .notification-error { background: var(--error-color); }
        .notification-warning { background: var(--warning-color); }
        .notification-info { background: var(--info-color); }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .notification.removing {
            animation: slideOut 0.3s ease forwards;
        }
        
        /* Utility Classes */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-5 { margin-top: 1.25rem; }
        
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-5 { margin-bottom: 1.25rem; }
        
        .hidden { display: none !important; }
        .visible { display: block; }
        
        /* Mobile search overlay improvements */
        @media (max-width: 768px) {
            #searchOverlay {
                padding: 0.75rem;
            }
            
            #searchOverlay .search-input {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
            
            #searchOverlay .btn {
                padding: 0.75rem 1rem;
                min-width: 44px;
                height: 44px;
            }
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Notes Hub</span>
            </a>
            
            <div class="nav-menu" id="navMenu">
                <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="notes.php" class="nav-link <?= $currentPage === 'notes.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Notes
                </a>
                <a href="categories.php" class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <?php if ($isLoggedIn): ?>
                <a href="chat.php" class="nav-link <?= $currentPage === 'chat.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Chat
                </a>
                <?php endif; ?>
            </div>
            
            <div class="nav-actions">
                <!-- Dark Mode Toggle -->
                <button type="button" class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                
                <!-- Search Bar -->
                <div class="search-bar" style="position: relative;">
                    <input type="text" class="search-input" placeholder="Search notes by title..." id="searchInput" oninput="performInstantSearch(this.value)">
                    <button type="button" class="search-btn" onclick="performSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                    <div id="instantSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow-lg); z-index: 1000; max-height: 400px; overflow-y: auto; margin-top: 0.5rem;"></div>
                </div>
                
                <!-- Mobile Search Button -->
                <button type="button" class="mobile-search-toggle" id="mobileSearchToggle" onclick="showMobileSearch()" style="display: none; background: none; border: none; color: var(--text-secondary); font-size: 1.25rem; cursor: pointer; padding: 0.5rem; border-radius: var(--border-radius); transition: all var(--transition-fast);">
                    <i class="fas fa-search"></i>
                </button>
                
                <?php if ($isLoggedIn): ?>
                    <!-- Notifications -->
                    <div class="dropdown" id="notificationDropdown">
                        <div class="dropdown-toggle" onclick="toggleNotificationDropdown()" style="position: relative;">
                            <i class="fas fa-bell"></i>
                            <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                        </div>
                        <div class="dropdown-menu notification-menu" id="notificationDropdownMenu" style="min-width: 320px; max-width: 400px; max-height: 500px; overflow-y: auto; overflow-x: hidden;">
                            <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">Notifications</h3>
                                <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 0.875rem; padding: 0.25rem 0.5rem;">
                                    Mark all read
                                </button>
                            </div>
                            <div id="notificationsList" style="min-height: 100px; display: block; color: var(--text-muted);">
                                <div style="display: flex; align-items: center; justify-content: center; padding: 2rem;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="dropdown" id="userDropdown">
                        <div class="dropdown-toggle" onclick="toggleUserDropdown()">
                            <?php if (!empty($userAvatar) && filter_var($userAvatar, FILTER_VALIDATE_URL) && strpos($userAvatar, 'placeholder') === false): ?>
                                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="user-avatar-placeholder" style="display: none;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login/Register Buttons -->
                    <a href="login.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(event)" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Search Overlay (for mobile search) -->
    <div id="searchOverlay" class="hidden" style="
        position: fixed;
        top: var(--navbar-height);
        left: 0;
        right: 0;
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
        z-index: 999;
        box-shadow: var(--shadow-md);
    ">
        <div style="display: flex; gap: 0.5rem; align-items: center; max-width: 100%;">
            <input type="text" class="search-input" placeholder="Search notes by title..." id="mobileSearchInput" style="flex: 1; width: auto; min-width: 0;">
            <button type="button" class="btn btn-primary btn-sm" onclick="performMobileSearch()" style="flex-shrink: 0;">
                <i class="fas fa-search"></i>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeSearchOverlay()" style="flex-shrink: 0;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <script>
        // Dark Mode Management
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;
            
            document.documentElement.setAttribute('data-theme', theme);
            updateThemeIcon(theme);
        }
        
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }
        
        function updateThemeIcon(theme) {
            const themeIcon = document.getElementById('themeIcon');
            if (themeIcon) {
                themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            
            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (localStorage.getItem('theme') === 'auto' || !localStorage.getItem('theme')) {
                    document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
                    updateThemeIcon(e.matches ? 'dark' : 'light');
                }
            });
        });
        
        // Navigation JavaScript
        function toggleMobileMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const navMenu = document.getElementById('navMenu');
            const body = document.body;
            if (navMenu) {
                const isActive = navMenu.classList.contains('active');
                if (isActive) {
                    navMenu.classList.remove('active');
                    body.style.overflow = '';
                } else {
                    navMenu.classList.add('active');
                    body.style.overflow = 'hidden';
                }
            }
        }
        
        function toggleUserDropdown() {
            const dropdownMenu = document.getElementById('userDropdownMenu');
            const notificationMenu = document.getElementById('notificationDropdownMenu');
            dropdownMenu.classList.toggle('active');
            notificationMenu.classList.remove('active');
        }
        
        function toggleNotificationDropdown() {
            const dropdownMenu = document.getElementById('notificationDropdownMenu');
            const userMenu = document.getElementById('userDropdownMenu');
            dropdownMenu.classList.toggle('active');
            userMenu.classList.remove('active');
            
            if (dropdownMenu.classList.contains('active')) {
                loadNotifications();
            }
        }
        
        let notificationPollInterval = null;
        
        function loadNotifications() {
            fetch('api/get-notifications.php?limit=20')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge(data.unread_count);
                        renderNotifications(data.notifications);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('notificationsList').innerHTML = 
                        '<div class="no-notifications">Error loading notifications</div>';
                });
        }
        
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notificationBadge');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsList');
            
            if (notifications.length === 0) {
                container.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i><p>No notifications yet</p></div>';
                return;
            }
            
            // Clear container and set display to block to stack vertically
            container.style.display = 'block';
            container.innerHTML = notifications.map(notif => {
                const avatar = notif.from_avatar && notif.from_avatar !== 'https://via.placeholder.com/150' && notif.from_avatar.includes('http')
                    ? `<img src="${escapeHtml(notif.from_avatar)}" alt="${escapeHtml(notif.from_username)}" class="notification-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="notification-avatar-placeholder" style="display: none;"><i class="fas fa-user"></i></div>`
                    : `<div class="notification-avatar-placeholder"><i class="fas fa-user"></i></div>`;
                
                let link = '#';
                if (notif.type === 'message' && notif.from_id) {
                    link = 'chat.php';
                } else if (notif.type === 'like' && notif.note_id) {
                    link = `note-detail.php?id=${notif.note_id}`;
                } else if (notif.type === 'follow' && notif.from_id) {
                    link = `profile_users.php?id=${notif.from_id}`;
                }
                
                return `
                    <div class="notification-item ${notif.is_read ? '' : 'unread'}" onclick="handleNotificationClick(${notif.id}, '${link}')">
                        <div class="notification-icon">
                            <i class="fas ${notif.icon}"></i>
                        </div>
                        ${avatar}
                        <div class="notification-content">
                            <div class="notification-message">${escapeHtml(notif.message)}</div>
                            <div class="notification-time">${escapeHtml(notif.time_ago)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function handleNotificationClick(notificationId, link) {
            // Mark as read
            fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: notificationId })
            })
            .then(() => {
                // Reload notifications to remove read chat notifications
                loadNotifications();
            });
            
            // Close dropdown
            document.getElementById('notificationDropdownMenu').classList.remove('active');
            
            // Navigate
            if (link && link !== '#') {
                window.location.href = link;
            }
        }
        
        function markAllNotificationsRead() {
            fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload notifications to remove read chat notifications
                    loadNotifications();
                }
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Poll for new notifications every 30 seconds
        <?php if ($isLoggedIn): ?>
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            notificationPollInterval = setInterval(loadNotifications, 30000);
        });
        <?php endif; ?>
        
        let instantSearchTimeout = null;
        
        function performInstantSearch(query) {
            const resultsDiv = document.getElementById('instantSearchResults');
            if (!resultsDiv) return;
            
            // Clear previous timeout
            if (instantSearchTimeout) {
                clearTimeout(instantSearchTimeout);
            }
            
            const trimmedQuery = query.trim();
            
            // Hide results if query is too short
            if (trimmedQuery.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            
            // Debounce search
            instantSearchTimeout = setTimeout(() => {
                fetch('api/fulltext-search.php?q=' + encodeURIComponent(trimmedQuery) + '&limit=5')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Search request failed');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (data.results && data.results.length > 0) {
                                resultsDiv.innerHTML = data.results.map(result => `
                                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                                         onmouseover="this.style.background='var(--bg-light)'" 
                                         onmouseout="this.style.background=''" 
                                         onclick="window.location.href='${result.url}'">
                                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${escapeHtml(result.title)}</div>
                                        <div style="font-size: 0.875rem; color: var(--text-secondary);">${escapeHtml(result.description)}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                            <i class="fas fa-tag"></i> ${escapeHtml(result.category)} | 
                                            <i class="fas fa-user"></i> ${escapeHtml(result.author)}
                                        </div>
                                    </div>
                                `).join('') + `
                                    <div style="padding: 0.75rem 1rem; text-align: center; border-top: 1px solid var(--border-color);">
                                        <a href="search.php?q=${encodeURIComponent(trimmedQuery)}" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                            View all results →
                                        </a>
                                    </div>
                                `;
                                resultsDiv.style.display = 'block';
                            } else {
                                resultsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted);">No results found</div>';
                                resultsDiv.style.display = 'block';
                            }
                        } else {
                            // API returned success: false, hide results
                            resultsDiv.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        resultsDiv.style.display = 'none';
                    });
            }, 300); // 300ms debounce
        }
        
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm) {
                window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
            }
        }
        
        function performMobileSearch() {
            const searchTerm = document.getElementById('mobileSearchInput').value.trim();
            if (searchTerm) {
                window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
            }
        }
        
        function closeSearchOverlay() {
            document.getElementById('searchOverlay').classList.add('hidden');
        }
        
        // Search on Enter key
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        document.getElementById('mobileSearchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performMobileSearch();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const userDropdown = document.getElementById('userDropdown');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationDropdownMenu = document.getElementById('notificationDropdownMenu');
            const searchBar = document.querySelector('.search-bar');
            const resultsDiv = document.getElementById('instantSearchResults');
            
            if (userDropdown && !userDropdown.contains(e.target)) {
                userDropdownMenu.classList.remove('active');
            }
            
            if (notificationDropdown && !notificationDropdown.contains(e.target)) {
                notificationDropdownMenu.classList.remove('active');
            }
            
            // Hide instant search results when clicking outside
            if (searchBar && resultsDiv && !searchBar.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
        
        // Mobile menu close when clicking outside
        document.addEventListener('click', function(e) {
            const navMenu = document.getElementById('navMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (navMenu && toggleBtn && !navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
                navMenu.classList.remove('active');
            }
        });
        
        // Show mobile search overlay
        function showMobileSearch() {
            document.getElementById('searchOverlay').classList.remove('hidden');
            document.getElementById('mobileSearchInput').focus();
        }
        
        // Auto-close mobile menu on navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navMenu').classList.remove('active');
            });
        });
    </script>
</body>
</html>