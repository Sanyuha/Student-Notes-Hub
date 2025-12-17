<?php
// Unified footer component for Student Notes Hub
// This file should be included at the bottom of every PHP page

// Get current year for copyright
$currentYear = date('Y');
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- About Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-graduation-cap"></i>
                        Student Notes Hub
                    </h3>
                    <p>A platform for students to share knowledge, learn together, and excel in their studies. Join our community of learners and educators.</p>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="notes.php">Browse Notes</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Info -->
                <div class="footer-section">
                    <h3>Info</h3>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="report.php">Report Issue</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li>
                            <a href="tel:+96897474150">
                                <i class="fas fa-phone"></i>
                                +968 97474150
                            </a>
                        </li>
                        <li>
                            <a href="mailto:thr3cl@gmail.com">
                                <i class="fas fa-envelope"></i>
                                thr3cl@gmail.com
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= $currentYear ?> Student Notes Hub. All rights reserved. 
                   Built with <i class="fas fa-heart" style="color: #ef4444;"></i> for students, by students.</p>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" onclick="scrollToTop()" title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <style>
        /* Footer Styles */
        .footer {
            background: var(--bg-dark);
            color: var(--text-light);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }
        
        [data-theme="dark"] .footer {
            background: var(--bg-dark);
            border-top: 1px solid var(--border-color);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: var(--primary-light);
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .footer-section p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: var(--text-muted);
            transition: color var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-links a:hover {
            color: var(--primary-light);
        }
        
        .footer-links a::before {
            content: '→';
            opacity: 0;
            transform: translateX(-5px);
            transition: all var(--transition-fast);
        }
        
        .footer-links a:hover::before {
            opacity: 1;
            transform: translateX(0);
        }
        
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .footer-bottom i.fa-heart {
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-normal);
            z-index: 1000;
        }
        
        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Responsive Footer */
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer-section {
                text-align: center;
            }
            
            .footer-section h3 {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
        }
        
        @media (max-width: 480px) {
            .footer {
                padding: 2rem 0 1rem;
            }
            
            .footer-content {
                gap: 1rem;
            }
            
            .back-to-top {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
    
    <script>
        // Back to top functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show/hide back to top button based on scroll position
        function handleScroll() {
            const backToTopBtn = document.getElementById('backToTop');
            const scrollThreshold = 300;
            
            if (window.scrollY > scrollThreshold) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        }
        
        // Add scroll event listener
        window.addEventListener('scroll', handleScroll);
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            handleScroll(); // Check initial scroll position
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add loading states for external links
        document.querySelectorAll('a[href^="http"], a[href$=".pdf"]').forEach(link => {
            link.addEventListener('click', function() {
                if (this.hostname !== window.location.hostname) {
                    this.style.opacity = '0.7';
                    this.innerHTML += ' <i class="fas fa-spinner fa-spin"></i>';
                }
            });
        });
    </script>
<?php 
// Reset variables to avoid conflicts
unset($currentYear);
?>