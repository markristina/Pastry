<?php
session_start();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>" class="logo">
                    <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                    <span>La Maria Bakehouse</span>
                </a>
                <div class="nav-links">
                    <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>">Home</a>
                    <a href="menu.php">Menu</a>
                    <a href="about.php">About</a>
                    <a href="contact.php" class="active">Contact Us</a>
                    <?php if ($isLoggedIn): ?>
                        <?php if (($_SESSION['user']['role'] ?? 'customer') === 'admin'): ?>
                            <a href="admin.php" class="btn btn-sm">Admin</a>
                        <?php endif; ?>
                        <a href="cart.php" class="btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cartCount > 0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
                        <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                        <a href="logout.php" class="btn btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                        <a href="signup.php" class="btn btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Contact Page Hero -->
    <section class="hero hero-contact">
        <div class="container">
            <div class="hero-content fade-up">
                <p class="hero-eyebrow">Contact Us</p>
                <h1>Get in Touch</h1>
                <p class="hero-subtitle">Visit us, call, or send a message. We’re here for orders, questions, and feedback.</p>
            </div>
        </div>
    </section>

    <!-- Contact Content - Contact information only -->
    <section class="contact contact-page">
        <div class="container">
            <div class="contact-container fade-up">
                <div class="contact-info">
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="contact-info-text">
                            <h3>Visit Us</h3>
                            <p>123 Bakery Lane, R. Castillo, Davao City</p>
                            <p>(inside Bloomfield Market)</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-phone-alt"></i>
                        <div class="contact-info-text">
                            <h3>Call Us</h3>
                            <p>+63 917 555 1234</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-info-text">
                            <h3>Email Us</h3>
                            <p><a href="mailto:hello@lamariabakehouse.com">hello@lamariabakehouse.com</a></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-clock"></i>
                        <div class="contact-info-text">
                            <h3>Hours</h3>
                            <p>Tuesday – Sunday</p>
                            <p>7:00 AM – 8:00 PM</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-shopping-bag"></i>
                        <div class="contact-info-text">
                            <h3>Ordering</h3>
                            <p>Same-day pickup, scheduled delivery within Davao, and nationwide frozen-ready boxes. Order online or call us.</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <h3 class="contact-form-title">Send us a message</h3>
                    <form id="contactForm" onsubmit="return handleContact(event)">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>" class="logo">
                        <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                        <span>La Maria Bakehouse</span>
                    </a>
                    <p>Bringing sweetness to your life, one pastry at a time.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="mailto:hello@lamariabakehouse.com" aria-label="Email"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="menu.php">Menu</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Contact</h4>
                    <ul>
                        <li>123 Bakery Lane, Davao City</li>
                        <li>+63 917 555 1234</li>
                        <li><a href="mailto:hello@lamariabakehouse.com">hello@lamariabakehouse.com</a></li>
                    </ul>
                </div>
                <div class="footer-column newsletter">
                    <h4>Stay in the loop</h4>
                    <form id="newsletterForm" onsubmit="return handleNewsletter(event)">
                        <input type="email" id="newsletterEmail" placeholder="Email address" required>
                        <button type="submit" class="btn btn-sm">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 La Maria Bakehouse. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
