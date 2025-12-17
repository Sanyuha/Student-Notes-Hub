<?php
// Contact page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Contact Us - Student Notes Hub';
$messageSent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Store contact message in database
            try {
                $pdo->prepare('
                    CREATE TABLE IF NOT EXISTS contact_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        subject VARCHAR(500) NOT NULL,
                        message TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ')->execute();
                
                $stmt = $pdo->prepare('
                    INSERT INTO contact_messages (name, email, subject, message)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$name, $email, $subject, $message]);
                
                $messageSent = true;
            } catch (Exception $e) {
                $error = 'Sorry, there was an error sending your message. Please try again later.';
            }
        }
    }
}

$_SESSION['csrf'] = csrf();
require __DIR__ . '/components/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <h1><i class="fas fa-envelope"></i> Contact Us</h1>
            <p>We'd love to hear from you. Get in touch with our team</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-wrapper">
                <!-- Contact Info -->
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <p>Have a question or feedback? We're here to help! Reach out to us through any of the following methods.</p>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="method-content">
                                <h3>Phone</h3>
                                <a href="tel:+96897474150">+968 97474150</a>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="method-content">
                                <h3>Email</h3>
                                <a href="mailto:thr3cl@gmail.com">thr3cl@gmail.com</a>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="method-content">
                                <h3>Response Time</h3>
                                <p>We typically respond within 24-48 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-wrapper">
                    <?php if ($messageSent): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <h3>Message Sent Successfully!</h3>
                            <p>Thank you for contacting us. We'll get back to you as soon as possible.</p>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <p><?= htmlspecialchars($error) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="contact-form">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="contact_form" value="1">
                            
                            <div class="form-group">
                                <label for="name">Your Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Your Email *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       placeholder="your.email@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <input type="text" id="subject" name="subject" required
                                       value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                                       placeholder="What is this regarding?">
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" rows="6" required
                                          placeholder="Tell us more about your inquiry..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    .page-hero {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 4rem 0;
        text-align: center;
    }
    
    .page-hero h1 {
        font-size: clamp(2rem, 5vw, 3rem);
        margin-bottom: 1rem;
        font-weight: 700;
    }
    
    .page-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
    }
    
    .contact-section {
        padding: 4rem 0;
        background: var(--bg-primary);
    }
    
    .contact-wrapper {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 3rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .contact-info h2 {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
        font-weight: 700;
    }
    
    .contact-info > p {
        color: var(--text-secondary);
        line-height: 1.8;
        margin-bottom: 2rem;
        font-size: 1.125rem;
    }
    
    .contact-methods {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .contact-method {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius-lg);
        transition: all var(--transition-normal);
    }
    
    .contact-method:hover {
        transform: translateX(5px);
        box-shadow: var(--shadow-md);
    }
    
    .method-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .method-content h3 {
        font-size: 1.125rem;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
        font-weight: 600;
    }
    
    .method-content a {
        color: var(--primary-color);
        font-weight: 500;
        text-decoration: none;
        transition: color var(--transition-fast);
    }
    
    .method-content a:hover {
        color: var(--primary-dark);
    }
    
    .method-content p {
        color: var(--text-secondary);
        margin: 0;
    }
    
    .contact-form-wrapper {
        background: var(--bg-secondary);
        padding: 2.5rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
    }
    
    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .form-group label {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .form-group input,
    .form-group textarea {
        padding: 0.875rem;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 1rem;
        font-family: inherit;
        background: var(--bg-primary);
        color: var(--text-primary);
        transition: all var(--transition-normal);
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 150px;
    }
    
    .success-message,
    .error-message {
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        text-align: center;
    }
    
    .success-message {
        background: rgba(16, 185, 129, 0.1);
        border: 2px solid var(--success-color);
        color: var(--success-color);
    }
    
    .error-message {
        background: rgba(239, 68, 68, 0.1);
        border: 2px solid var(--error-color);
        color: var(--error-color);
        margin-bottom: 1.5rem;
    }
    
    .success-message i,
    .error-message i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .success-message h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    /* Dark Mode */
    [data-theme="dark"] .contact-method {
        background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .contact-form-wrapper {
        background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .form-group input,
    [data-theme="dark"] .form-group textarea {
        background: var(--bg-primary);
        border-color: var(--border-color);
    }
    
    @media (max-width: 968px) {
        .contact-wrapper {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        .contact-form-wrapper {
            padding: 1.5rem;
        }
    }
</style>

<?php require __DIR__ . '/components/footer.php'; ?>


