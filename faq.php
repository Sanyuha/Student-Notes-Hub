<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'FAQ - Student Notes Hub';
include __DIR__ . '/components/header.php';
?>

<div class="container" style="max-width: 900px; margin: 3rem auto; padding: 2rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color);">
        <i class="fas fa-question-circle"></i> Frequently Asked Questions
    </h1>
    
    <div style="background: white; border-radius: var(--border-radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-top: 2rem;">
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> What file formats are supported?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Currently, we support all types of files. The maximum file size is 10MB per note.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> Can I delete my uploaded notes?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Yes, you can delete any note you've uploaded by going to your profile page, finding the note, and clicking the delete button.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> How do I report inappropriate content?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                You can report any note by clicking the "Report" button on the note detail page. Our moderation team will review the report and take appropriate action.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> Is my personal information safe?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Yes, we take your privacy seriously. Please review our <a href="privacy.php" style="color: var(--primary-color);">Privacy Policy</a> for more information on how we handle your data.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> Can I edit my notes after uploading?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Currently, you cannot edit notes after uploading. If you need to make changes, you'll need to delete the old note and upload a new version.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> How do I create a group chat?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                In the Chat page, click on the "Groups" tab, then click "Create Group". Enter a group name and add members. You can add or remove members at any time.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> What happens if I forget my password?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Click "Forgot password?" on the login page and enter your email address. We'll send you a password reset link that expires in 1 hour.
            </p>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">
                <i class="fas fa-question"></i> Can I download notes without an account?
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Yes, you can browse and download notes without creating an account. However, creating an account allows you to upload notes, follow users, and participate in discussions.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>



