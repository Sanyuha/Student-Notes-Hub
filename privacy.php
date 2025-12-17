<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = 'Privacy Policy - Student Notes Hub';
include __DIR__ . '/components/header.php';
?>

<div class="container" style="max-width: 900px; margin: 3rem auto; padding: 2rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color);">
        <i class="fas fa-shield-alt"></i> Privacy Policy
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Last updated: <?= date('F j, Y') ?></p>
    
    <div style="background: white; border-radius: var(--border-radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-top: 2rem;">
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">1. Information We Collect</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                We collect information that you provide directly to us, including:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Account information (username, email address, password)</li>
                <li>Profile information (avatar, bio)</li>
                <li>Content you upload (notes, comments, messages)</li>
                <li>Usage data and interactions with our platform</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">2. How We Use Your Information</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                We use the information we collect to:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Provide, maintain, and improve our services</li>
                <li>Process transactions and send related information</li>
                <li>Send technical notices and support messages</li>
                <li>Respond to your comments and questions</li>
                <li>Monitor and analyze trends and usage</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">3. Information Sharing</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem; margin-top: 1rem;">
                <li>With your consent</li>
                <li>To comply with legal obligations</li>
                <li>To protect our rights and safety</li>
                <li>With service providers who assist us in operating our platform</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">4. Data Security</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.
            </p>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">5. Your Rights</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem;">
                You have the right to:
            </p>
            <ul style="color: var(--text-secondary); line-height: 1.8; margin-left: 2rem;">
                <li>Access and update your personal information</li>
                <li>Delete your account and associated data</li>
                <li>Opt-out of certain communications</li>
                <li>Request a copy of your data</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">6. Contact Us</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                If you have questions about this Privacy Policy, please contact us:
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



