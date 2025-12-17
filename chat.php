<?php
require __DIR__ . '/bootstrap.php';
ensureLoggedIn();

$pageTitle = 'Chat - Student Notes Hub';
$userId = $_SESSION['auth_user_id'];
$username = $_SESSION['username'] ?? 'User';
$userAvatar = $_SESSION['avatar_url'] ?? '';

include __DIR__ . '/components/header.php';
?>

<style>
    * {
        box-sizing: border-box;
    }

    /* Dark Mode Support */
    [data-theme="dark"] .chat-wrapper {
        background: var(--bg-primary);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    [data-theme="dark"] .chat-sidebar {
        background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        border-right-color: var(--border-color);
    }

    [data-theme="dark"] .sidebar-header,
    [data-theme="dark"] .tab-container,
    [data-theme="dark"] .chat-list,
    [data-theme="dark"] .chat-main,
    [data-theme="dark"] .chat-header,
    [data-theme="dark"] .input-container,
    [data-theme="dark"] .modal-content {
        background: var(--bg-primary);
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .sidebar-header h2 {
        color: var(--primary-color);
    }

    [data-theme="dark"] .tab-btn {
        background: var(--bg-secondary);
        border-color: var(--border-color);
        color: var(--text-secondary);
    }

    [data-theme="dark"] .tab-btn:hover {
        background: var(--bg-primary);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .tab-btn.active {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .action-btn-secondary {
        background: var(--bg-secondary);
        border-color: var(--border-color);
        color: var(--text-primary);
    }

    [data-theme="dark"] .action-btn-secondary:hover {
        background: var(--bg-primary);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    [data-theme="dark"] .chat-item {
        background: transparent;
    }

    [data-theme="dark"] .chat-item:hover {
        background: var(--bg-secondary);
    }

    [data-theme="dark"] .chat-item.active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.1) 100%);
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .chat-name {
        color: var(--text-primary);
    }

    [data-theme="dark"] .chat-preview {
        color: var(--text-secondary);
    }

    [data-theme="dark"] .chat-time {
        color: var(--text-muted);
    }

    [data-theme="dark"] .empty-state {
        background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        color: var(--text-secondary);
    }

    [data-theme="dark"] .empty-state h3 {
        color: var(--text-primary);
    }

    [data-theme="dark"] .messages-container {
        background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
    }

    [data-theme="dark"] .message-content {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    [data-theme="dark"] .message.sent .message-content {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
    }

    [data-theme="dark"] .message-sender {
        color: var(--primary-color);
    }

    [data-theme="dark"] .message.sent .message-sender {
        color: rgba(255, 255, 255, 0.95);
    }

    [data-theme="dark"] .message-time {
        color: var(--text-muted);
    }

    [data-theme="dark"] .message.sent .message-time {
        color: rgba(255, 255, 255, 0.85);
    }

    [data-theme="dark"] .message-input {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .message-input:focus {
        background: var(--bg-primary);
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .message-input::placeholder {
        color: var(--text-muted);
    }

    [data-theme="dark"] .file-upload-btn {
        background: var(--bg-secondary);
        border-color: var(--border-color);
        color: var(--text-primary);
    }

    [data-theme="dark"] .file-upload-btn:hover {
        background: var(--bg-primary);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    [data-theme="dark"] .file-preview {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
    }

    [data-theme="dark"] .file-preview-name {
        color: var(--text-primary);
    }

    [data-theme="dark"] .form-input {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .form-input:focus {
        background: var(--bg-primary);
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .form-label {
        color: var(--text-primary);
    }

    [data-theme="dark"] .user-list {
        background: var(--bg-secondary);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .user-item {
        background: transparent;
    }

    [data-theme="dark"] .user-item:hover,
    [data-theme="dark"] .member-item:hover {
        background: var(--bg-primary);
    }

    [data-theme="dark"] .member-item {
        background: var(--bg-secondary);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .member-item:hover {
        border-color: var(--primary-color);
    }

    [data-theme="dark"] .member-role {
        background: var(--bg-primary);
        border-color: var(--border-color);
        color: var(--text-secondary);
    }

    [data-theme="dark"] .modal {
        background: rgba(0, 0, 0, 0.8);
    }

    [data-theme="dark"] .modal-header {
        border-bottom-color: var(--border-color);
    }

    [data-theme="dark"] .modal-header h3 {
        color: var(--text-primary);
    }

    [data-theme="dark"] .modal-close {
        background: var(--bg-secondary);
        color: var(--text-secondary);
    }

    [data-theme="dark"] .modal-close:hover {
        background: var(--error-color);
        color: white;
    }

    [data-theme="dark"] .error-message {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: var(--error-color);
    }

    [data-theme="dark"] .chat-header {
        border-bottom-color: var(--border-color);
    }

    [data-theme="dark"] .chat-header-name {
        color: var(--text-primary);
    }

    [data-theme="dark"] .input-container {
        border-top-color: var(--border-color);
    }

    .chat-wrapper {
        display: flex;
        height: calc(100vh - var(--navbar-height) - 2rem);
        max-width: 1600px;
        margin: 1rem auto;
        background: var(--bg-primary);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Sidebar */
    .chat-sidebar {
        width: 360px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-primary);
    }

    .sidebar-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 1.25rem 0;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .sidebar-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .action-btn {
        flex: 1;
        min-width: 140px;
        padding: 0.875rem 1rem;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9375rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    .action-btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
    }

    .action-btn-primary:hover {
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
    }

    .action-btn-secondary {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border: 2px solid var(--border-color);
    }

    .action-btn-secondary:hover {
        background: var(--bg-light);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .action-btn i {
        font-size: 1rem;
    }

    .tab-container {
        display: flex;
        background: var(--bg-primary);
        border-bottom: 2px solid var(--border-color);
        gap: 0.5rem;
        padding: 0.75rem;
    }

    .tab-btn {
        flex: 1;
        padding: 0.875rem 1.25rem;
        background: var(--bg-secondary);
        border: 2px solid var(--border-color);
        border-radius: 12px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--text-secondary);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .tab-btn:hover {
        background: var(--bg-light);
        color: var(--primary-color);
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(99, 102, 241, 0.15);
    }

    .tab-btn.active {
        color: white;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        border-color: var(--primary-color);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    .tab-btn i {
        font-size: 1.1rem;
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
        background: var(--bg-primary);
    }

    .chat-item {
        padding: 1rem;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 2px solid transparent;
    }

    .chat-item:hover {
        background: var(--bg-light);
        transform: translateX(4px);
    }

    .chat-item.active {
        background: linear-gradient(135deg, var(--primary-light) 0%, rgba(99, 102, 241, 0.1) 100%);
        border-color: var(--primary-color);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }

    .chat-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        flex-shrink: 0;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .chat-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .chat-info {
        flex: 1;
        min-width: 0;
    }

    .chat-name {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-primary);
    }

    .chat-preview {
        font-size: 0.875rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }

    .chat-time {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .chat-badge {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border-radius: 12px;
        min-width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0 0.5rem;
        box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
    }

    /* Main Chat Area */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--bg-primary);
        position: relative;
    }

    #chatArea {
        display: none;
        flex: 1;
        flex-direction: column;
        min-height: 0;
    }

    #chatArea[style*="flex"],
    #chatArea.show {
        display: flex !important;
    }

    .chat-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-primary);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .chat-header-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .chat-header-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .chat-header-name {
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--text-primary);
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.03) 0%, transparent 50%);
    }

    .message {
        display: flex;
        gap: 0.75rem;
        max-width: 70%;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.sent {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .message-content {
        background: var(--bg-primary);
        padding: 0.875rem 1.125rem;
        border-radius: 18px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    .message-file {
        margin-top: 0.5rem;
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }

    [data-theme="dark"] .message-file {
        background: rgba(255, 255, 255, 0.1);
    }

    .message.sent .message-file {
        background: rgba(255, 255, 255, 0.2);
    }

    .message-file:hover {
        background: rgba(0, 0, 0, 0.1);
        transform: translateX(2px);
    }

    [data-theme="dark"] .message-file:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .message-file-icon {
        font-size: 1.5rem;
        color: var(--primary-color);
    }

    .message-file-info {
        flex: 1;
        min-width: 0;
    }

    .message-file-name {
        font-weight: 600;
        font-size: 0.875rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .message-file-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        margin-top: 0.5rem;
        cursor: pointer;
    }

    .file-upload-btn {
        padding: 0.875rem;
        background: var(--bg-secondary);
        border: 2px solid var(--border-color);
        border-radius: 24px;
        cursor: pointer;
        color: var(--text-primary);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .file-upload-btn:hover {
        background: var(--bg-light);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .file-input {
        display: none;
    }

    .file-preview {
        padding: 0.5rem 1rem;
        background: var(--bg-light);
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .file-preview-name {
        flex: 1;
        font-size: 0.875rem;
        color: var(--text-primary);
    }

    .file-preview-remove {
        background: none;
        border: none;
        color: var(--error-color);
        cursor: pointer;
        font-size: 1.25rem;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .message.sent .message-content {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .message-sender {
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.375rem;
        color: var(--primary-color);
    }

    .message.sent .message-sender {
        color: rgba(255, 255, 255, 0.95);
    }

    .message-text {
        word-wrap: break-word;
        line-height: 1.6;
        font-size: 0.9375rem;
    }

    .message-time {
        font-size: 0.6875rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
        opacity: 0.7;
    }

    .message.sent .message-time {
        color: rgba(255, 255, 255, 0.85);
    }

    .input-container {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: var(--bg-primary);
        align-items: flex-end;
    }

    .input-row {
        display: flex;
        gap: 0.75rem;
        width: 100%;
        align-items: flex-end;
    }

    .message-input {
        flex: 1;
        padding: 0.875rem 1.25rem;
        border: 2px solid var(--border-color);
        border-radius: 24px;
        font-family: inherit;
        font-size: 0.9375rem;
        resize: none;
        max-height: 120px;
        transition: all 0.2s;
        background: var(--bg-secondary);
    }

    .message-input:focus {
        outline: none;
        border-color: var(--primary-color);
        background: var(--bg-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .send-btn {
        padding: 0.875rem 1.75rem;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 24px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .send-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }

    .send-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .send-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        text-align: center;
        padding: 3rem;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }

    .empty-icon {
        font-size: 5rem;
        margin-bottom: 1.5rem;
        opacity: 0.2;
        color: var(--primary-color);
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .empty-state p {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .empty-state-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 1rem;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        animation: fadeIn 0.2s;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        max-width: 520px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
    }

    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-primary);
    }

    .modal-close {
        background: var(--bg-light);
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-secondary);
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: var(--error-color);
        color: white;
        transform: rotate(90deg);
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        font-family: inherit;
        font-size: 0.9375rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .user-list {
        max-height: 320px;
        overflow-y: auto;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 0.5rem;
        margin-top: 0.5rem;
        background: var(--bg-secondary);
    }

    .user-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 0.25rem;
    }

    .user-item:hover {
        background: white;
        transform: translateX(4px);
    }

    .user-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    .user-item img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .members-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: 320px;
        overflow-y: auto;
        margin-top: 0.5rem;
    }

    .member-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: var(--bg-light);
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all 0.2s;
    }

    .member-item:hover {
        border-color: var(--primary-color);
        background: white;
    }

    .member-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .member-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .member-role {
        font-size: 0.8125rem;
        color: var(--text-secondary);
        padding: 0.375rem 0.75rem;
        background: white;
        border-radius: 20px;
        font-weight: 600;
        border: 1px solid var(--border-color);
    }

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

    .error-message {
        padding: 1.25rem;
        background: #fee;
        border: 2px solid #fcc;
        border-radius: 12px;
        color: var(--error-color);
        margin: 1rem;
        font-weight: 500;
    }

    /* Scrollbar styling */
    .chat-list::-webkit-scrollbar,
    .messages-container::-webkit-scrollbar,
    .user-list::-webkit-scrollbar,
    .members-list::-webkit-scrollbar {
        width: 6px;
    }

    .chat-list::-webkit-scrollbar-track,
    .messages-container::-webkit-scrollbar-track,
    .user-list::-webkit-scrollbar-track,
    .members-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-list::-webkit-scrollbar-thumb,
    .messages-container::-webkit-scrollbar-thumb,
    .user-list::-webkit-scrollbar-thumb,
    .members-list::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 3px;
    }

    .chat-list::-webkit-scrollbar-thumb:hover,
    .messages-container::-webkit-scrollbar-thumb:hover,
    .user-list::-webkit-scrollbar-thumb:hover,
    .members-list::-webkit-scrollbar-thumb:hover {
        background: var(--text-muted);
    }

    @media (max-width: 768px) {
        .chat-wrapper {
            flex-direction: column;
            height: calc(100vh - var(--navbar-height));
            border-radius: 0;
            margin: 0;
        }

        .chat-sidebar {
            width: 100%;
            height: 45%;
        }

        .chat-main {
            height: 55%;
        }

        .sidebar-actions {
            flex-direction: column;
        }

        .action-btn {
            width: 100%;
            min-width: auto;
        }

        .tab-container {
            padding: 0.5rem;
            gap: 0.375rem;
        }

        .tab-btn {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }

        .tab-btn span {
            display: none;
        }

        .tab-btn i {
            font-size: 1.25rem;
        }
    }
    
    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .slide-up {
        animation: slideUp 0.6s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Prevent re-animation */
    .animated {
        animation: none !important;
    }
    
    .chat-item.animated,
    .message.animated {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<div class="chat-wrapper">
    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-comments"></i> Chat</h2>
            <div class="sidebar-actions">
                <button class="action-btn action-btn-secondary" id="newMessageBtn" style="display: none;">
                    <i class="fas fa-envelope"></i>
                    <span>New Message</span>
                </button>
                <button class="action-btn action-btn-primary" id="newGroupBtn">
                    <i class="fas fa-users"></i>
                    <span>Create Group</span>
                </button>
            </div>
        </div>

        <div class="tab-container">
            <button class="tab-btn active" data-tab="conversations">
                <i class="fas fa-comments"></i>
                <span>Messages</span>
            </button>
            <button class="tab-btn" data-tab="groups">
                <i class="fas fa-users"></i>
                <span>Groups</span>
            </button>
        </div>

        <div class="chat-list" id="chatList">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
        <div class="empty-state" id="emptyState">
            <div class="empty-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h3>Welcome to Chat</h3>
            <p>Select a conversation or group from the sidebar to start chatting, or create a new one</p>
            <div class="empty-state-actions">
                <button class="action-btn action-btn-secondary" onclick="document.getElementById('newMessageBtn')?.click()" id="emptyNewMessageBtn" style="display: none;">
                    <i class="fas fa-envelope"></i>
                    <span>Start Conversation</span>
                </button>
                <button class="action-btn action-btn-primary" onclick="document.getElementById('newGroupBtn')?.click()">
                    <i class="fas fa-users"></i>
                    <span>Create Group</span>
                </button>
            </div>
        </div>

        <div id="chatArea" style="display: none;">
            <div class="chat-header">
                <div class="chat-header-left">
                    <div class="chat-header-avatar" id="chatHeaderAvatar"></div>
                    <div class="chat-header-name" id="chatHeaderName"></div>
                </div>
                <div id="chatHeaderActions"></div>
            </div>

            <div class="messages-container" id="messagesContainer"></div>

            <div class="input-container">
                <div id="filePreviewContainer"></div>
                <div class="input-row">
                    <input type="file" id="fileInput" class="file-input" accept="*/*" title="Attach any file">
                    <button class="file-upload-btn" id="fileUploadBtn" title="Attach File (Images, Documents, Videos, Audio, Archives)">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type a message..."
                        rows="1"
                    ></textarea>
                    <button class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal" id="newMessageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>New Message</h3>
            <button class="modal-close" id="closeNewMessageModal">&times;</button>
        </div>
        <div class="form-group">
            <label class="form-label">Search Users</label>
            <input type="text" class="form-input" id="newMessageSearch" placeholder="Type to search...">
            <div class="user-list" id="newMessageUserList"></div>
        </div>
    </div>
</div>

<div class="modal" id="createGroupModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Group</h3>
            <button class="modal-close" id="closeCreateGroupModal">&times;</button>
        </div>
        <form id="createGroupForm">
            <div class="form-group">
                <label class="form-label">Group Name</label>
                <input type="text" class="form-input" id="groupName" required placeholder="Enter group name">
            </div>
            <div class="form-group">
                <label class="form-label">Add Members</label>
                <input type="text" class="form-input" id="memberSearch" placeholder="Search users...">
                <div class="user-list" id="userList"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Group</button>
        </form>
    </div>
</div>

<div class="modal" id="groupInfoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Group Info</h3>
            <button class="modal-close" id="closeGroupInfoModal">&times;</button>
        </div>
        <div id="groupInfoContent"></div>
    </div>
</div>

<script>
class ChatApp {
    constructor() {
        this.currentUserId = <?= $userId ?>;
        this.currentTab = 'conversations';
        this.currentChatId = null;
        this.currentChatType = null;
        this.selectedUsers = [];
        this.messagePollInterval = null;
        this.chatListUpdateInterval = null;
        this.searchTimeouts = {};
        this.lastMessageId = null;
        this.isLoadingMessages = false;
        this.isUserScrolling = false;
        this.pendingFile = null;
        this.lastChatListHash = null;
        this.isUpdatingChatList = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadChatList();
    }

    setupEventListeners() {
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab || e.target.closest('.tab-btn')?.dataset.tab;
                if (tab) this.switchTab(tab);
            });
        });

        // New message button
        document.getElementById('newMessageBtn')?.addEventListener('click', () => {
            this.showNewMessageModal();
        });

        // New group button
        document.getElementById('newGroupBtn')?.addEventListener('click', () => {
            this.showCreateGroupModal();
        });

        // Send message
        document.getElementById('sendBtn')?.addEventListener('click', () => {
            this.sendMessage();
        });

        document.getElementById('messageInput')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize textarea
        document.getElementById('messageInput')?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // File upload
        document.getElementById('fileUploadBtn')?.addEventListener('click', () => {
            document.getElementById('fileInput')?.click();
        });

        document.getElementById('fileInput')?.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                this.handleFileSelect(e.target.files[0]);
            }
        });

        // Modal close buttons
        document.getElementById('closeNewMessageModal')?.addEventListener('click', () => {
            this.closeNewMessageModal();
        });
        document.getElementById('closeCreateGroupModal')?.addEventListener('click', () => {
            this.closeCreateGroupModal();
        });
        document.getElementById('closeGroupInfoModal')?.addEventListener('click', () => {
            this.closeGroupInfoModal();
        });

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Search inputs
        document.getElementById('newMessageSearch')?.addEventListener('input', (e) => {
            this.searchUsersForMessage(e.target.value);
        });
        document.getElementById('memberSearch')?.addEventListener('input', (e) => {
            this.searchUsers(e.target.value);
        });

        // Create group form
        document.getElementById('createGroupForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createGroup();
        });
    }

    switchTab(tab) {
        if (!tab) return;
        this.currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        const newMsgBtn = document.getElementById('newMessageBtn');
        if (newMsgBtn) {
            newMsgBtn.style.display = tab === 'conversations' ? 'flex' : 'none';
        }
        const emptyNewMsgBtn = document.getElementById('emptyNewMessageBtn');
        if (emptyNewMsgBtn) {
            emptyNewMsgBtn.style.display = tab === 'conversations' ? 'flex' : 'none';
        }
        this.loadChatList();
    }

    async loadChatList() {
        const chatList = document.getElementById('chatList');
        const wasLoading = chatList.querySelector('.loading');

        try {
            const endpoint = this.currentTab === 'conversations' 
                ? 'api/get-conversations.php' 
                : 'api/get-groups.php';
            
            const response = await fetch(endpoint);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to load');
            }

            const items = this.currentTab === 'conversations' ? data.conversations : data.groups;

            // Create hash to check if data actually changed
            const currentHash = JSON.stringify(items.map(item => ({
                id: item.id,
                unread: item.unread_count,
                last_message: item.last_message,
                last_time: item.last_message_time
            })));

            // Only update if data changed or was loading
            if (currentHash === this.lastChatListHash && !wasLoading) {
                return;
            }

            this.lastChatListHash = currentHash;

            if (items.length === 0) {
                const emptyMessage = this.currentTab === 'conversations' 
                    ? 'No conversations yet. Start a new message to begin chatting!'
                    : 'No groups yet. Create a group to collaborate with others!';
                const emptyAction = this.currentTab === 'conversations'
                    ? '<button class="action-btn action-btn-primary" onclick="chatApp.showNewMessageModal()" style="margin-top: 1rem;"><i class="fas fa-envelope"></i> <span>Start Conversation</span></button>'
                    : '<button class="action-btn action-btn-primary" onclick="chatApp.showCreateGroupModal()" style="margin-top: 1rem;"><i class="fas fa-users"></i> <span>Create Group</span></button>';
                chatList.innerHTML = `
                    <div class="empty-state" style="padding: 2rem;">
                        <div class="empty-icon" style="font-size: 3rem;">
                            <i class="fas fa-${this.currentTab === 'conversations' ? 'comments' : 'users'}"></i>
                        </div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No ${this.currentTab} yet</h3>
                        <p style="font-size: 0.9375rem; margin-bottom: 1rem;">${emptyMessage}</p>
                        ${emptyAction}
                    </div>
                `;
                return;
            }

            // Preserve active state
            const activeId = chatList.querySelector('.chat-item.active')?.dataset.id;
            
            // Smart update: only re-render if structure changed, otherwise update in place
            const existingItems = chatList.querySelectorAll('.chat-item');
            const existingIds = Array.from(existingItems).map(el => el.dataset.id);
            const newIds = items.map(item => String(item.id));
            
            // Check if we need full re-render (items added/removed/reordered)
            const needsFullRender = existingIds.length !== newIds.length || 
                                   JSON.stringify(existingIds) !== JSON.stringify(newIds);
            
            if (needsFullRender || wasLoading) {
                // Full re-render
                chatList.innerHTML = items.map(item => this.renderChatItem(item)).join('');
                
                // Restore active state
                if (activeId) {
                    const activeItem = chatList.querySelector(`[data-id="${activeId}"]`);
                    if (activeItem) {
                        activeItem.classList.add('active');
                    }
                }
                
                // Add click listeners
                chatList.querySelectorAll('.chat-item').forEach((item, index) => {
                    item.addEventListener('click', () => {
                        if (this.currentTab === 'conversations') {
                            this.openConversation(items[index]);
                        } else {
                            this.openGroup(items[index]);
                        }
                    });
                });
            } else {
                // Update existing items in place (no flickering)
                items.forEach((item, index) => {
                    const existingItem = chatList.querySelector(`[data-id="${item.id}"]`);
                    if (existingItem) {
                        // Only update if content changed
                        const preview = existingItem.querySelector('.chat-preview');
                        const time = existingItem.querySelector('.chat-time');
                        const badge = existingItem.querySelector('.chat-badge');
                        const badgeContainer = existingItem.querySelector('.chat-meta');
                        
                        if (preview && preview.textContent !== (item.last_message || 'No messages yet')) {
                            preview.textContent = item.last_message || 'No messages yet';
                        }
                        
                        if (time && item.last_message_time) {
                            const newTime = this.formatTime(item.last_message_time);
                            if (time.textContent !== newTime) {
                                time.textContent = newTime;
                            }
                        }
                        
                        // Update badge
                        if (item.unread_count > 0) {
                            if (badge) {
                                if (badge.textContent !== String(item.unread_count)) {
                                    badge.textContent = item.unread_count;
                                }
                            } else if (badgeContainer) {
                                const newBadge = document.createElement('div');
                                newBadge.className = 'chat-badge';
                                newBadge.textContent = item.unread_count;
                                badgeContainer.appendChild(newBadge);
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                });
            }

        } catch (error) {
            console.error('Error loading chat list:', error);
            if (wasLoading || chatList.children.length === 0) {
                chatList.innerHTML = `
                    <div class="error-message">
                        Error loading chats: ${error.message}
                        <button class="btn btn-primary btn-sm mt-2" onclick="chatApp.loadChatList()">Retry</button>
                    </div>
                `;
            }
        }
    }

    renderChatItem(item) {
        const isConversation = this.currentTab === 'conversations';
        const name = isConversation ? item.other_username : item.name;
        const avatar = isConversation ? item.other_avatar_url : null;
        const preview = item.last_message || 'No messages yet';
        const time = item.last_message_time ? this.formatTime(item.last_message_time) : '';
        const badge = item.unread_count > 0 ? `<div class="chat-badge">${item.unread_count}</div>` : '';

        const avatarHtml = avatar && avatar !== 'https://via.placeholder.com/150' && avatar.trim() !== ''
            ? `<img src="${this.escapeHtml(avatar)}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-${isConversation ? 'user' : 'users'}\\'></i>'">`
            : `<i class="fas fa-${isConversation ? 'user' : 'users'}"></i>`;

        return `
            <div class="chat-item" data-id="${item.id}">
                <div class="chat-avatar">
                    ${avatarHtml}
                </div>
                <div class="chat-info">
                    <div class="chat-name">${this.escapeHtml(name)}</div>
                    <div class="chat-preview">${this.escapeHtml(preview)}</div>
                </div>
                <div class="chat-meta">
                    ${time ? `<div class="chat-time">${time}</div>` : ''}
                    ${badge}
                </div>
            </div>
        `;
    }

    async openConversation(conv) {
        this.currentChatId = conv.id;
        this.currentChatType = 'conversation';
        this.lastMessageId = null; // Reset for new conversation
        
        const emptyState = document.getElementById('emptyState');
        const chatArea = document.getElementById('chatArea');
        
        if (emptyState) emptyState.style.display = 'none';
        if (chatArea) {
            chatArea.style.display = 'flex';
            chatArea.classList.add('show');
        }
        
        const avatar = conv.other_avatar_url && conv.other_avatar_url !== 'https://via.placeholder.com/150' && conv.other_avatar_url.trim() !== '';
        const avatarHtml = avatar
            ? `<img src="${this.escapeHtml(conv.other_avatar_url)}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user\\'></i>'">`
            : '<i class="fas fa-user"></i>';
        
        document.getElementById('chatHeaderAvatar').innerHTML = avatarHtml;
        document.getElementById('chatHeaderName').textContent = conv.other_username;
        
        const profileBtn = document.createElement('button');
        profileBtn.className = 'btn btn-secondary btn-sm';
        profileBtn.innerHTML = '<i class="fas fa-user"></i> Profile';
        profileBtn.addEventListener('click', () => {
            window.location.href = `profile_users.php?user_id=${conv.other_user_id}`;
        });
        document.getElementById('chatHeaderActions').innerHTML = '';
        document.getElementById('chatHeaderActions').appendChild(profileBtn);

        // Update active state
        document.querySelectorAll('.chat-item').forEach(item => {
            item.classList.toggle('active', item.dataset.id == conv.id);
        });

        await this.loadMessages(true); // Force full reload for new conversation
        this.startPolling();
    }

    async openGroup(group) {
        this.currentChatId = group.id;
        this.currentChatType = 'group';
        this.lastMessageId = null; // Reset for new group
        
        const emptyState = document.getElementById('emptyState');
        const chatArea = document.getElementById('chatArea');
        
        if (emptyState) emptyState.style.display = 'none';
        if (chatArea) {
            chatArea.style.display = 'flex';
            chatArea.classList.add('show');
        }
        
        document.getElementById('chatHeaderAvatar').innerHTML = '<i class="fas fa-users"></i>';
        document.getElementById('chatHeaderName').textContent = group.name;
        
        const infoBtn = document.createElement('button');
        infoBtn.className = 'btn btn-secondary btn-sm';
        infoBtn.innerHTML = '<i class="fas fa-info-circle"></i> Info';
        infoBtn.addEventListener('click', () => this.showGroupInfo(group.id));
        document.getElementById('chatHeaderActions').innerHTML = '';
        document.getElementById('chatHeaderActions').appendChild(infoBtn);

        // Update active state
        document.querySelectorAll('.chat-item').forEach(item => {
            item.classList.toggle('active', item.dataset.id == group.id);
        });

        await this.loadMessages(true); // Force full reload for new group
        this.startPolling();
    }

    async loadMessages(forceReload = false) {
        if (this.isLoadingMessages) return;
        
        const container = document.getElementById('messagesContainer');
        if (!container) return;

        // Check if user is near bottom (within 100px)
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
        
        // If not forcing reload and we have messages, check for new ones only
        if (!forceReload && this.lastMessageId && container.children.length > 0) {
            try {
                const endpoint = this.currentChatType === 'conversation' 
                    ? 'api/get-messages.php' 
                    : 'api/get-group-messages.php';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        id: this.currentChatId,
                        group_id: this.currentChatId,
                        after_id: this.lastMessageId
                    })
                });
                
                const data = await response.json();

                if (data.success && data.messages && data.messages.length > 0) {
                    // Append only new messages
                    const fragment = document.createDocumentFragment();
                    data.messages.forEach(msg => {
                        const msgDiv = document.createElement('div');
                        msgDiv.innerHTML = this.renderMessage(msg);
                        fragment.appendChild(msgDiv.firstElementChild);
                        this.lastMessageId = Math.max(this.lastMessageId, parseInt(msg.id));
                    });
                    
                    container.appendChild(fragment);
                    
                    // Only auto-scroll if user was near bottom
                    if (isNearBottom) {
                        container.scrollTop = container.scrollHeight;
                    }
                    return;
                }
            } catch (error) {
                console.error('Error checking for new messages:', error);
                // Fall through to full reload on error
            }
        }

        // Full reload
        this.isLoadingMessages = true;
        const wasEmpty = container.children.length === 0;
        
        if (wasEmpty) {
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i></div>';
        }

        try {
            const endpoint = this.currentChatType === 'conversation' 
                ? 'api/get-messages.php' 
                : 'api/get-group-messages.php';
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: this.currentChatId,
                    group_id: this.currentChatId 
                })
            });
            
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to load messages');
            }

            if (!data.messages || data.messages.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No messages yet. Start the conversation!</p></div>';
                this.lastMessageId = null;
                this.isLoadingMessages = false;
                return;
            }

            // Store the last message ID
            this.lastMessageId = Math.max(...data.messages.map(msg => parseInt(msg.id)));
            
            // Update chat list to refresh badges (messages are now marked as read)
            // Use a small delay to avoid flickering
            setTimeout(() => {
                this.loadChatList();
            }, 300);
            
            // Only replace if messages actually changed
            const currentMessages = container.querySelectorAll('.message');
            const currentMessageIds = Array.from(currentMessages).map(el => {
                const msgId = el.dataset.messageId;
                return msgId ? parseInt(msgId) : null;
            }).filter(id => id !== null).sort((a, b) => a - b);
            
            const newMessageIds = data.messages.map(msg => parseInt(msg.id)).sort((a, b) => a - b);
            const messagesChanged = currentMessageIds.length !== newMessageIds.length || 
                                  JSON.stringify(currentMessageIds) !== JSON.stringify(newMessageIds);

            if (messagesChanged || wasEmpty) {
                container.innerHTML = data.messages.map(msg => this.renderMessage(msg)).join('');
                
                // Scroll to bottom only if it was empty or user was near bottom
                if (wasEmpty || isNearBottom) {
                    // Use setTimeout to ensure DOM is updated
                    setTimeout(() => {
                        container.scrollTop = container.scrollHeight;
                    }, 0);
                }
            }

        } catch (error) {
            console.error('Error loading messages:', error);
            if (container.children.length === 0 || container.querySelector('.loading')) {
                container.innerHTML = `
                    <div class="error-message">
                        Error loading messages: ${error.message}
                        <button class="btn btn-primary btn-sm mt-2" onclick="chatApp.loadMessages(true)">Retry</button>
                    </div>
                `;
            }
        } finally {
            this.isLoadingMessages = false;
        }
    }

    renderMessage(msg) {
        const isSent = msg.sender_id == this.currentUserId;
        const showSender = this.currentChatType === 'group' && !isSent;
        const avatar = msg.avatar_url && msg.avatar_url !== 'https://via.placeholder.com/150' && msg.avatar_url.trim() !== '';
        const hasFile = msg.file_url && msg.file_url.trim() !== '';
        const isImage = hasFile && msg.file_type && msg.file_type.startsWith('image/');

        let fileHtml = '';
        if (hasFile) {
            if (isImage) {
                fileHtml = `
                    <a href="${this.escapeHtml(msg.file_url)}" target="_blank" class="message-file">
                        <div class="message-file-info">
                            <div class="message-file-name">${this.escapeHtml(msg.file_name || 'Image')}</div>
                        </div>
                    </a>
                    <img src="${this.escapeHtml(msg.file_url)}" alt="${this.escapeHtml(msg.file_name || 'Image')}" class="message-file-preview" onclick="window.open('${this.escapeHtml(msg.file_url)}', '_blank')">
                `;
            } else {
                const fileIcon = this.getFileIcon(msg.file_type);
                fileHtml = `
                    <a href="${this.escapeHtml(msg.file_url)}" target="_blank" class="message-file" download>
                        <i class="fas ${fileIcon} message-file-icon"></i>
                        <div class="message-file-info">
                            <div class="message-file-name">${this.escapeHtml(msg.file_name || 'File')}</div>
                        </div>
                        <i class="fas fa-download"></i>
                    </a>
                `;
            }
        }

        return `
            <div class="message ${isSent ? 'sent' : ''}" data-message-id="${msg.id}">
                ${!isSent && avatar ? `<img src="${this.escapeHtml(msg.avatar_url)}" class="message-avatar" onerror="this.style.display='none'">` : ''}
                <div class="message-content">
                    ${showSender ? `<div class="message-sender">${this.escapeHtml(msg.username)}</div>` : ''}
                    ${msg.message ? `<div class="message-text">${this.escapeHtml(msg.message)}</div>` : ''}
                    ${fileHtml}
                    <div class="message-time">${this.formatTime(msg.created_at)}</div>
                </div>
            </div>
        `;
    }

    getFileIcon(fileType) {
        if (!fileType) return 'fa-file';
        if (fileType.startsWith('image/')) return 'fa-file-image';
        if (fileType === 'application/pdf') return 'fa-file-pdf';
        if (fileType.includes('word') || fileType.includes('document')) return 'fa-file-word';
        if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fa-file-excel';
        if (fileType.includes('zip') || fileType.includes('compressed')) return 'fa-file-archive';
        if (fileType.includes('text')) return 'fa-file-alt';
        return 'fa-file';
    }

    handleFileSelect(file) {
        this.pendingFile = file;
        const container = document.getElementById('filePreviewContainer');
        container.innerHTML = `
            <div class="file-preview">
                <i class="fas ${this.getFileIcon(file.type)}"></i>
                <span class="file-preview-name">${this.escapeHtml(file.name)}</span>
                <button class="file-preview-remove" onclick="chatApp.removeFile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }

    removeFile() {
        this.pendingFile = null;
        document.getElementById('filePreviewContainer').innerHTML = '';
        document.getElementById('fileInput').value = '';
    }

    async sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        // Allow sending file-only messages (no text required)
        if (!this.pendingFile && !message) return;
        if (!this.currentChatId) return;

        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;

        try {
            let fileUrl = null;
            let fileName = null;
            let fileType = null;

            // Upload file if present
            if (this.pendingFile) {
                const formData = new FormData();
                formData.append('file', this.pendingFile);
                if (this.currentChatType === 'conversation') {
                    formData.append('conversation_id', this.currentChatId);
                } else {
                    formData.append('group_id', this.currentChatId);
                }

                const uploadResponse = await fetch('api/upload-chat-file.php', {
                    method: 'POST',
                    body: formData
                });

                const uploadData = await uploadResponse.json();
                if (!uploadData.success) {
                    alert('Failed to upload file: ' + (uploadData.error || 'Unknown error'));
                    sendBtn.disabled = false;
                    return;
                }

                fileUrl = uploadData.file_url;
                fileName = uploadData.file_name;
                fileType = uploadData.file_type;
            }

            // Send message
            const endpoint = this.currentChatType === 'conversation' 
                ? 'api/send-message.php' 
                : 'api/send-group-message.php';
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: this.currentChatId,
                    group_id: this.currentChatId,
                    message: message,
                    file_url: fileUrl,
                    file_name: fileName,
                    file_type: fileType
                })
            });
            
            const data = await response.json();

            if (data.success) {
                input.value = '';
                input.style.height = 'auto';
                this.removeFile();
                await this.loadMessages(true); // Force reload after sending
                this.loadChatList();
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Error sending message');
        } finally {
            sendBtn.disabled = false;
        }
    }

    startPolling() {
        if (this.messagePollInterval) {
            clearInterval(this.messagePollInterval);
        }
        if (this.chatListUpdateInterval) {
            clearInterval(this.chatListUpdateInterval);
        }
        
        // Poll for new messages every 3 seconds
        this.messagePollInterval = setInterval(() => {
            if (this.currentChatId && !this.isLoadingMessages) {
                // Only check for new messages (not full reload)
                this.loadMessages(false);
            }
        }, 3000);
        
        // Update chat list less frequently to reduce flickering (every 15 seconds)
        // Only update if there's an active chat (to avoid unnecessary updates)
        this.chatListUpdateInterval = setInterval(() => {
            if (this.currentChatId) {
                // Use a flag to prevent multiple simultaneous updates
                if (!this.isUpdatingChatList) {
                    this.isUpdatingChatList = true;
                    this.loadChatList().finally(() => {
                        this.isUpdatingChatList = false;
                    });
                }
            }
        }, 15000);
    }

    // Group Management
    showNewMessageModal() {
        document.getElementById('newMessageModal').classList.add('active');
        document.getElementById('newMessageUserList').innerHTML = '';
        document.getElementById('newMessageSearch').value = '';
    }

    closeNewMessageModal() {
        document.getElementById('newMessageModal').classList.remove('active');
    }

    async searchUsersForMessage(query) {
        clearTimeout(this.searchTimeouts.message);
        this.searchTimeouts.message = setTimeout(async () => {
            if (query.length < 2) {
                document.getElementById('newMessageUserList').innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`api/get-users.php?search=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                if (data.success) {
                    const html = data.users.map(user => {
                        const username = this.escapeHtml(user.username);
                        const avatar = user.avatar_url && user.avatar_url !== 'https://via.placeholder.com/150' && user.avatar_url.trim() !== '';
                        return `
                            <div class="user-item" data-user-id="${user.id}" data-username="${username}" data-avatar="${user.avatar_url || ''}">
                                ${avatar ? `<img src="${this.escapeHtml(user.avatar_url)}" onerror="this.style.display='none'">` : '<div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-user"></i></div>'}
                                <span>${username}</span>
                            </div>
                        `;
                    }).join('');
                    document.getElementById('newMessageUserList').innerHTML = html;
                    
                    // Add click listeners
                    document.querySelectorAll('#newMessageUserList .user-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const userId = parseInt(item.dataset.userId);
                            const username = item.dataset.username;
                            const avatar = item.dataset.avatar;
                            this.startConversation(userId, username, avatar);
                        });
                    });
                }
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }, 300);
    }

    async startConversation(userId, username, avatar) {
        try {
            const response = await fetch('api/start-conversation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeNewMessageModal();
                await this.loadChatList();
                // Find and open the conversation
                const conv = { id: data.conversation_id, other_user_id: userId, other_username: username, other_avatar_url: avatar };
                this.openConversation(conv);
            } else {
                alert('Failed to start conversation');
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
            alert('Error starting conversation');
        }
    }

    showCreateGroupModal() {
        document.getElementById('createGroupModal').classList.add('active');
        this.selectedUsers = [];
        document.getElementById('userList').innerHTML = '';
        document.getElementById('groupName').value = '';
        document.getElementById('memberSearch').value = '';
    }

    closeCreateGroupModal() {
        document.getElementById('createGroupModal').classList.remove('active');
    }

    async searchUsers(query) {
        clearTimeout(this.searchTimeouts.group);
        this.searchTimeouts.group = setTimeout(async () => {
            if (query.length < 2) {
                document.getElementById('userList').innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`api/get-users.php?search=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                if (data.success) {
                    const html = data.users.map(user => {
                        const checked = this.selectedUsers.includes(user.id);
                        const avatar = user.avatar_url && user.avatar_url !== 'https://via.placeholder.com/150' && user.avatar_url.trim() !== '';
                        return `
                            <div class="user-item" data-user-id="${user.id}">
                                <input type="checkbox" value="${user.id}" ${checked ? 'checked' : ''}>
                                ${avatar ? `<img src="${this.escapeHtml(user.avatar_url)}" onerror="this.style.display='none'">` : '<div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-user"></i></div>'}
                                <span>${this.escapeHtml(user.username)}</span>
                            </div>
                        `;
                    }).join('');
                    document.getElementById('userList').innerHTML = html;
                    
                    // Add checkbox listeners
                    document.querySelectorAll('#userList input[type="checkbox"]').forEach(checkbox => {
                        checkbox.addEventListener('change', (e) => {
                            const userId = parseInt(e.target.value);
                            this.toggleUser(userId, e.target.checked);
                        });
                    });
                }
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }, 300);
    }

    toggleUser(userId, checked) {
        if (checked) {
            if (!this.selectedUsers.includes(userId)) {
                this.selectedUsers.push(userId);
            }
        } else {
            this.selectedUsers = this.selectedUsers.filter(id => id !== userId);
        }
    }

    async createGroup() {
        const name = document.getElementById('groupName').value.trim();
        
        if (!name || this.selectedUsers.length === 0) {
            alert('Please enter a group name and select at least one member');
            return;
        }
        
        try {
            const response = await fetch('api/create-group.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    members: this.selectedUsers
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeCreateGroupModal();
                this.switchTab('groups');
                await this.loadChatList();
                // Open the new group
                setTimeout(() => {
                    const group = { id: data.group_id, name: name };
                    this.openGroup(group);
                }, 500);
            } else {
                alert('Failed to create group: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error creating group:', error);
            alert('Error creating group');
        }
    }

    async showGroupInfo(groupId) {
        document.getElementById('groupInfoModal').classList.add('active');
        const content = document.getElementById('groupInfoContent');
        content.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i></div>';
        
        try {
            const response = await fetch(`api/get-group-info.php?group_id=${groupId}`);
            const data = await response.json();
            
            if (data.success) {
                const canManage = data.my_role === 'admin' || data.my_role === 'moderator';
                content.innerHTML = `
                    <div class="form-group">
                        <label class="form-label">Group Name</label>
                        <div style="font-size: 1.25rem; font-weight: 600;">${this.escapeHtml(data.group.name)}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Members (${data.members.length})</label>
                        <div class="members-list">
                            ${data.members.map(member => {
                                const avatar = member.avatar_url && member.avatar_url !== 'https://via.placeholder.com/150' && member.avatar_url.trim() !== '';
                                return `
                                <div class="member-item">
                                    <div class="member-info">
                                        ${avatar ? `<img src="${this.escapeHtml(member.avatar_url)}" class="member-avatar" onerror="this.style.display='none'">` : '<div class="member-avatar" style="background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-user"></i></div>'}
                                        <div>
                                            <div style="font-weight: 600;">${this.escapeHtml(member.username)}</div>
                                            <div class="member-role">${member.role}</div>
                                        </div>
                                    </div>
                                    ${canManage && member.id != this.currentUserId && member.role !== 'admin' ? `
                                        <button class="btn btn-danger btn-sm" data-action="remove" data-group-id="${groupId}" data-member-id="${member.id}">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    ` : ''}
                                    ${member.id == this.currentUserId ? `
                                        <button class="btn btn-danger btn-sm" data-action="remove" data-group-id="${groupId}" data-member-id="${member.id}">
                                            <i class="fas fa-sign-out-alt"></i> Leave
                                        </button>
                                    ` : ''}
                                </div>
                            `;
                            }).join('')}
                        </div>
                    </div>
                    ${canManage ? `
                        <div class="form-group">
                            <label class="form-label">Add Members</label>
                            <input type="text" class="form-input" id="addMemberSearch" placeholder="Search users...">
                            <div class="user-list" id="addMemberList"></div>
                        </div>
                    ` : ''}
                `;

                if (canManage) {
                    document.getElementById('addMemberSearch')?.addEventListener('input', (e) => {
                        this.searchUsersForGroup(e.target.value, groupId);
                    });
                    
                    // Add remove member listeners
                    content.querySelectorAll('[data-action="remove"]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const groupId = parseInt(btn.dataset.groupId);
                            const memberId = parseInt(btn.dataset.memberId);
                            this.removeMember(groupId, memberId);
                        });
                    });
                }
            }
        } catch (error) {
            console.error('Error loading group info:', error);
            content.innerHTML = '<div class="error-message">Error loading group info</div>';
        }
    }

    closeGroupInfoModal() {
        document.getElementById('groupInfoModal').classList.remove('active');
    }

    async searchUsersForGroup(query, groupId) {
        clearTimeout(this.searchTimeouts.groupMember);
        this.searchTimeouts.groupMember = setTimeout(async () => {
            if (query.length < 2) {
                document.getElementById('addMemberList').innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`api/get-users.php?search=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                const groupInfoResponse = await fetch(`api/get-group-info.php?group_id=${groupId}`);
                const groupInfo = await groupInfoResponse.json();
                const memberIds = groupInfo.success ? groupInfo.members.map(m => m.id) : [];
                
                if (data.success) {
                    const html = data.users
                        .filter(user => !memberIds.includes(user.id))
                        .map(user => {
                            const avatar = user.avatar_url && user.avatar_url !== 'https://via.placeholder.com/150' && user.avatar_url.trim() !== '';
                            return `
                            <div class="user-item" data-user-id="${user.id}" data-group-id="${groupId}">
                                ${avatar ? `<img src="${this.escapeHtml(user.avatar_url)}" onerror="this.style.display='none'">` : '<div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-user"></i></div>'}
                                <span>${this.escapeHtml(user.username)}</span>
                                <button class="btn btn-primary btn-sm" style="margin-left: auto;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        `;
                        }).join('');
                    document.getElementById('addMemberList').innerHTML = html;
                    
                    // Add click listeners
                    document.querySelectorAll('#addMemberList .user-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const userId = parseInt(item.dataset.userId);
                            const groupId = parseInt(item.dataset.groupId);
                            this.addMemberToGroup(groupId, userId);
                        });
                    });
                }
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }, 300);
    }

    async addMemberToGroup(groupId, userId) {
        try {
            const response = await fetch('api/add-group-members.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    group_id: groupId,
                    member_ids: [userId]
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showGroupInfo(groupId);
                document.getElementById('addMemberSearch').value = '';
            } else {
                alert('Failed to add member');
            }
        } catch (error) {
            console.error('Error adding member:', error);
            alert('Error adding member');
        }
    }

    async removeMember(groupId, memberId) {
        if (!confirm('Are you sure?')) return;
        
        try {
            const response = await fetch('api/remove-group-member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    group_id: groupId,
                    member_id: memberId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (memberId == this.currentUserId) {
                    this.closeGroupInfoModal();
                    const emptyState = document.getElementById('emptyState');
                    const chatArea = document.getElementById('chatArea');
                    if (emptyState) emptyState.style.display = 'flex';
                    if (chatArea) {
                        chatArea.style.display = 'none';
                        chatArea.classList.remove('show');
                    }
                    this.currentChatId = null;
                    this.loadChatList();
                } else {
                    this.showGroupInfo(groupId);
                }
            } else {
                alert('Failed to remove member');
            }
        } catch (error) {
            console.error('Error removing member:', error);
            alert('Error removing member');
        }
    }

    // Utility functions
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatTime(timeString) {
        if (!timeString) return '';
        const date = new Date(timeString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }
}

// Initialize app
const chatApp = new ChatApp();

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
        }
    });
}, observerOptions);

// Animate chat items and messages on load/scroll
document.addEventListener('DOMContentLoaded', function() {
    let chatItemsAnimated = new Set();
    let messagesAnimated = new Set();
    
    // Function to animate chat items (only new ones)
    function animateChatItems() {
        const chatItems = document.querySelectorAll('.chat-item:not(.animated)');
        chatItems.forEach((item) => {
            const itemId = item.dataset.id;
            if (!chatItemsAnimated.has(itemId)) {
                item.classList.add('slide-up', 'animated');
                chatItemsAnimated.add(itemId);
            }
        });
    }
    
    // Function to animate messages (only new ones)
    function animateMessages() {
        const messages = document.querySelectorAll('.message:not(.animated)');
        messages.forEach((msg) => {
            const msgId = msg.dataset.messageId;
            if (msgId && !messagesAnimated.has(msgId)) {
                msg.classList.add('slide-up', 'animated');
                messagesAnimated.add(msgId);
            }
        });
    }
    
    // Initial animation
    setTimeout(() => {
        animateChatItems();
        animateMessages();
    }, 100);
    
    // Re-animate when chat list is updated (only new items)
    const originalLoadChatList = chatApp.loadChatList;
    chatApp.loadChatList = async function() {
        await originalLoadChatList.call(this);
        setTimeout(animateChatItems, 50);
    };
    
    // Re-animate when messages are loaded (only new messages)
    const originalLoadMessages = chatApp.loadMessages;
    chatApp.loadMessages = async function(forceReload) {
        await originalLoadMessages.call(this, forceReload);
        if (forceReload) {
            // Reset animated set on force reload
            messagesAnimated.clear();
        }
        setTimeout(animateMessages, 50);
    };
    
    // Observe chat list container for new items (debounced)
    let chatListTimeout;
    const chatListObserver = new MutationObserver(() => {
        clearTimeout(chatListTimeout);
        chatListTimeout = setTimeout(animateChatItems, 100);
    });
    
    const chatList = document.getElementById('chatList');
    if (chatList) {
        chatListObserver.observe(chatList, { childList: true });
    }
    
    // Observe messages container for new messages (debounced)
    let messagesTimeout;
    const messagesObserver = new MutationObserver(() => {
        clearTimeout(messagesTimeout);
        messagesTimeout = setTimeout(animateMessages, 100);
    });
    
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesObserver.observe(messagesContainer, { childList: true });
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
