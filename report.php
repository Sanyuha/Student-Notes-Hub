<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Report Issue - Student Notes Hub';
include __DIR__ . '/components/header.php';
?>

<div class="container" style="max-width: 700px; margin: 3rem auto; padding: 2rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color);">
        <i class="fas fa-exclamation-triangle"></i> Report an Issue
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
        Found a bug, have a suggestion, or need to report inappropriate content? We're here to help!
    </p>
    
    <div style="background: white; border-radius: var(--border-radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-top: 2rem;">
        <form id="reportForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div>
                <label for="reportType" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                    Issue Type <span style="color: var(--error-color);">*</span>
                </label>
                <select id="reportType" name="reportType" required 
                        style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 1rem;">
                    <option value="">Select an issue type...</option>
                    <option value="bug">Bug Report</option>
                    <option value="inappropriate">Inappropriate Content</option>
                    <option value="spam">Spam or Scam</option>
                    <option value="copyright">Copyright Violation</option>
                    <option value="harassment">Harassment</option>
                    <option value="suggestion">Feature Suggestion</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div>
                <label for="subject" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                    Subject <span style="color: var(--error-color);">*</span>
                </label>
                <input type="text" id="subject" name="subject" required 
                       placeholder="Brief description of the issue"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 1rem;">
            </div>
            
            <div>
                <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                    Description <span style="color: var(--error-color);">*</span>
                </label>
                <textarea id="description" name="description" required rows="6"
                          placeholder="Please provide as much detail as possible..."
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 1rem; font-family: inherit; resize: vertical;"></textarea>
            </div>
            
            <div>
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                    Your Email <span style="color: var(--error-color);">*</span>
                </label>
                <input type="email" id="email" name="email" required 
                       value="<?= $isLoggedIn ? htmlspecialchars($_SESSION['email'] ?? '') : '' ?>"
                       placeholder="your.email@example.com"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 1rem;">
            </div>
            
            <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
                <i class="fas fa-paper-plane"></i> Submit Report
            </button>
        </form>
        
        <div id="reportMessage" style="margin-top: 1.5rem; display: none; padding: 1rem; border-radius: var(--border-radius);"></div>
    </div>
    
    <div style="background: var(--bg-light); border-radius: var(--border-radius-lg); padding: 2rem; margin-top: 2rem; text-align: center;">
        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Need Immediate Assistance?</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            Contact our support team directly:
        </p>
        <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <a href="tel:+96897474150" class="btn btn-outline">
                <i class="fas fa-phone"></i> +968 97474150
            </a>
            <a href="mailto:thr3cl@gmail.com" class="btn btn-outline">
                <i class="fas fa-envelope"></i> thr3cl@gmail.com
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('reportForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        type: document.getElementById('reportType').value,
        subject: document.getElementById('subject').value,
        description: document.getElementById('description').value,
        email: document.getElementById('email').value
    };
    
    const messageDiv = document.getElementById('reportMessage');
    messageDiv.style.display = 'block';
    messageDiv.className = 'notification notification-info';
    messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting report...';
    
    try {
        const response = await fetch('api/submit-report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            messageDiv.className = 'notification notification-success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + (result.message || 'Report submitted successfully! We will review it shortly.');
            document.getElementById('reportForm').reset();
        } else {
            messageDiv.className = 'notification notification-error';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.error || 'Failed to submit report. Please try again.');
        }
    } catch (error) {
        messageDiv.className = 'notification notification-error';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again or contact support directly.';
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>



