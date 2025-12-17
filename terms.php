<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Terms of Service - Student Notes Hub';
include __DIR__ . '/components/header.php';
?>

<div class="container" style="max-width: 900px; margin: 3rem auto; padding: 2rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color);">
        <i class="fas fa-file-contract"></i> Terms of Service
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Last updated: <?= date('F j, Y') ?></p>
    
    <div style="background: white; border-radius: var(--border-radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-top: 2rem;">
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">1. Acceptance of Terms</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                By accessing and using Student Notes Hub, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to these terms, please do not use our service.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">2. User Accounts</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                When you create an account, you agree to:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Provide accurate and complete information</li>
                <li>Maintain the security of your password</li>
                <li>Accept responsibility for all activities under your account</li>
                <li>Notify us immediately of any unauthorized use</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">3. User Content</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                You retain ownership of content you upload. By uploading content, you grant us a license to:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Store and display your content on our platform</li>
                <li>Make your content available to other users</li>
                <li>Use your content for platform operation and improvement</li>
            </ul>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-top: 1rem;">
                You are responsible for ensuring your content does not violate any laws or infringe on others' rights.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">4. Prohibited Activities</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                You agree not to:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Upload copyrighted material without permission</li>
                <li>Upload offensive, harmful, or illegal content</li>
                <li>Impersonate others or provide false information</li>
                <li>Interfere with the platform's operation</li>
                <li>Use automated systems to access the platform</li>
                <li>Harass, abuse, or harm other users</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">5. Intellectual Property</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                The Student Notes Hub platform, including its design, features, and functionality, is owned by us and protected by copyright, trademark, and other laws. You may not copy, modify, or create derivative works without our permission.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">6. Termination</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                We reserve the right to suspend or terminate your account at any time for violations of these terms. You may also delete your account at any time through your profile settings.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">7. Disclaimer of Warranties</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                The platform is provided "as is" without warranties of any kind. We do not guarantee that the service will be uninterrupted, secure, or error-free.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">8. Limitation of Liability</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                To the maximum extent permitted by law, we shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the platform.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">9. Changes to Terms</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                We reserve the right to modify these terms at any time. We will notify users of significant changes. Continued use of the platform after changes constitutes acceptance of the new terms.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">10. Contact Information</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                For questions about these Terms of Service, please contact us:
            </p>
            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: var(--border-radius); margin-top: 1rem;">
                <p style="margin-bottom: 0.5rem;">
                    <i class="fas fa-phone"></i> <strong>Phone:</strong> <a href="tel:+96897474150" style="color: var(--primary-color);">+968 97474150</a>
                </p>
                <p style="margin-bottom: 0;">
                    <i class="fas fa-envelope"></i> <strong>Email:</strong> <a href="mailto:thr3cl@gmail.com" style="color: var(--primary-color);">thr3cl@gmail.com</a>
                </p>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>



