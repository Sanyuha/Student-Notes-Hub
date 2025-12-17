<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Help Center - Student Notes Hub';
include __DIR__ . '/components/header.php';
?>

<div class="container" style="max-width: 900px; margin: 3rem auto; padding: 2rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color);">
        <i class="fas fa-question-circle"></i> Help Center
    </h1>
    
    <div style="background: white; border-radius: var(--border-radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-top: 2rem;">
        <h2 style="color: var(--text-primary); margin-bottom: 1.5rem;">Getting Started</h2>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-book"></i> How to Upload Notes
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                To upload notes, go to your profile page and click the "Upload Note" button. Fill in the required information including title, description, category, and upload your file. Make sure your file is in PDF format and under 10MB.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-search"></i> How to Search for Notes
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Use the search bar in the navigation menu to search for notes by title, description, or author. You can also browse notes by category on the Categories page.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-comments"></i> How to Use Chat
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Click on the Chat link in the navigation menu to access your messages. You can send direct messages to other users or create group chats. To start a conversation, search for a user and click "Message".
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-user-plus"></i> Following Users
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Visit any user's profile page and click the "Follow" button to follow them. You'll receive notifications when they upload new notes.
            </p>
        </div>
        
        <h2 style="color: var(--text-primary); margin-top: 3rem; margin-bottom: 1.5rem;">Account Management</h2>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-key"></i> Forgot Password
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                If you've forgotten your password, click "Forgot password?" on the login page. Enter your email address and we'll send you a reset link.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-user-edit"></i> Updating Your Profile
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Go to your profile page and click "Edit Profile" to update your information, change your avatar, or modify your bio.
            </p>
        </div>
        
        <h2 style="color: var(--text-primary); margin-top: 3rem; margin-bottom: 1.5rem;">Need More Help?</h2>
        <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1.5rem;">
            If you need additional assistance, please contact our support team:
        </p>
        <div style="background: var(--bg-light); padding: 1.5rem; border-radius: var(--border-radius);">
            <p style="margin-bottom: 0.5rem;">
                <i class="fas fa-phone"></i> <strong>Phone:</strong> <a href="tel:+96897474150" style="color: var(--primary-color);">+968 97474150</a>
            </p>
            <p style="margin-bottom: 0;">
                <i class="fas fa-envelope"></i> <strong>Email:</strong> <a href="mailto:thr3cl@gmail.com" style="color: var(--primary-color);">thr3cl@gmail.com</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>



